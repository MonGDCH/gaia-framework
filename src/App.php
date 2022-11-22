<?php

declare(strict_types=1);

namespace gaia;

use mon\log\Logger;
use mon\env\Config;
use Workerman\Worker;
use mon\console\App as Console;
use Workerman\Connection\TcpConnection;

/**
 * 初始化gaia
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class App
{
    /**
     * 版本号
     * 
     * @var string
     */
    const VERSION = '1.0.6';

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
        // 加载配置
        defined('CONFIG_PATH') && Config::instance()->loadDir(CONFIG_PATH);
        // 定义时区
        date_default_timezone_set(Config::instance()->get('app.timezone', 'PRC'));
        // 初始化日志服务
        Logger::instance()->registerChannel(Config::instance()->get('log', []));
        // 绑定控制台实例
        $console = static::console();
        // 注册workerman配置
        static::initWorker(Config::instance()->get('app.worker', []));

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
     * 注册workerman配置
     *
     * @param array $config
     * @return void
     */
    protected static function initWorker(array $config)
    {
        // 默认的最大可接受数据包大小
        TcpConnection::$defaultMaxPackageSize = $config['max_package_size'] ?? 10 * 1024 * 1024;
        // 存储主进程PID的文件
        Worker::$pidFile = $config['pid_file'] ?? RUNTIME_PATH . '/gaia.pid';
        // 存储标准输出的文件，默认 /dev/null。daemonize运行模式下echo的内容才会记录到文件中
        Worker::$stdoutFile = $config['log_file'] ?? RUNTIME_PATH . '/stdout.log';
        // workerman日志记录文件
        Worker::$logFile = $config['status_file'] ?? RUNTIME_PATH . '/workerman.log';
        // 存储主进程状态信息的文件，运行 status 指令后，内容会写入该文件
        Worker::$statusFile = RUNTIME_PATH . '/gaia.status';
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
}
