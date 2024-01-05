<?php

declare(strict_types=1);

namespace gaia;

use mon\env\Env;
use mon\env\Config;
use mon\log\Logger;
use Workerman\Worker;
use mon\console\App as Console;
use Workerman\Connection\TcpConnection;

/**
 * 初始化gaia
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.1.2
 */
class App
{
    /**
     * 版本号
     * 
     * @var string
     */
    const VERSION = '1.1.2';

    /**
     * 控制台实例
     *
     * @var Console
     */
    protected static $console = null;

    /**
     * 应用初始化
     *
     * @return Console
     */
    public static function init(): Console
    {
        // 初始化配置
        static::initialize();
        // 获取控制台实例
        $console = static::console();
        // 设置标题
        $console->setTitle('
      _______       ___       __       ___              ___      .______   .______   
     /  _____|     /   \     |  |     /   \            /   \     |   _  \  |   _  \  
    |  |   __     /  ^  \    |  |    /  ^  \          /  ^  \    |  |_)  | |  |_)  | 
    |  |  |_ |   /  /_\  \   |  |   /  /_\  \        /  /_\  \   |   ___/  |   ___/  
    |  |___| |  /  _____  \  |  |  /  _____  \      /  _____  \  |  |      |  |      
     \_______| /__/     \__\ |__| /__/     \__\    /__/     \__\ |__|      |__|      
');

        // 注册指令
        $path = __DIR__ . '/command';
        $namespance = 'gaia\\command';
        $console->load($path, $namespance);

        return $console;
    }

    /**
     * 初始化基础配置
     *
     * @return void
     */
    public static function initialize()
    {
        // 加载配置
        defined('ENV_PATH') && file_exists(ENV_PATH) && Env::load(ENV_PATH);
        defined('CONFIG_PATH') && Config::instance()->loadDir(CONFIG_PATH);
        // 定义时区
        date_default_timezone_set(Config::instance()->get('app.timezone', 'PRC'));
        // 初始化日志服务
        Logger::instance()->registerChannel(Config::instance()->get('log', []));
        // 注册workerman配置
        static::initWorker(Config::instance()->get('app.worker', []));
    }

    /**
     * 注册workerman配置
     *
     * @param array $config
     * @return void
     */
    public static function initWorker(array $config)
    {
        // 默认的最大可接受数据包大小
        TcpConnection::$defaultMaxPackageSize = $config['max_package_size'] ?? 10 * 1024 * 1024;
        // 存储主进程PID的文件
        Worker::$pidFile = $config['pid_file'] ?? RUNTIME_PATH . '/gaia.pid';
        // 存储关闭服务标准输出的文件，默认 /dev/null。daemonize运行模式下echo的内容才会记录到文件中
        Worker::$stdoutFile = $config['stdout_file'] ?? RUNTIME_PATH . '/stdout.log';
        // workerman日志记录文件
        Worker::$logFile = $config['log_file'] ?? RUNTIME_PATH . '/workerman.log';
        // 存储主进程状态信息的文件，运行 status 指令后，内容会写入该文件
        Worker::$statusFile = $config['status_file'] ??  RUNTIME_PATH . '/gaia.status';
        // workerman事件循环使用对象，默认 \Workerman\Events\Select。一般不需要修改，空则可以
        Worker::$eventLoopClass = $config['event_loop_class'] ?? '';
        // 发送停止命令后，多少秒内程序没有停止，则强制停止
        Worker::$stopTimeout = $config['stop_timeout'] ?? 2;
        // 重置opcache缓存
        Worker::$onMasterReload = function () {
            if (function_exists('opcache_get_status')) {
                $status = opcache_get_status();
                if ($status && isset($status['scripts'])) {
                    foreach (array_keys($status['scripts']) as $file) {
                        opcache_invalidate($file, true);
                    }
                }
            }
        };
    }

    /**
     * 获取控制台实例
     *
     * @return Console
     */
    public static function console(): Console
    {
        if (!static::$console) {
            static::$console = new Console();
        }

        return static::$console;
    }

    /**
     * 当前环境是否未windows运行环境
     *
     * @return boolean
     */
    public static function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\';
    }

    /**
     * 获取服务器CPU内核数
     *
     * @return integer
     */
    public static function cpuCount(): int
    {
        // Windows 不支持进程数设置
        if (self::isWindows()) {
            return 1;
        }
        static $count = 0;
        if ($count == 0 && is_callable('shell_exec')) {
            if (strtolower(PHP_OS) === 'darwin') {
                $count = (int)shell_exec('sysctl -n machdep.cpu.core_count');
            } else {
                $count = (int)shell_exec('nproc');
            }
        }
        return $count > 0 ? $count : 4;
    }
}
