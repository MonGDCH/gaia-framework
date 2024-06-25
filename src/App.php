<?php

declare(strict_types=1);

namespace gaia;

use mon\env\Env;
use mon\util\File;
use mon\env\Config;
use mon\log\Logger;
use mon\util\Event;
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
    const VERSION = '1.2.0';

    /**
     * 应用名
     *
     * @var string
     */
    protected static $name = '';

    /**
     * 控制台实例
     *
     * @var Console
     */
    protected static $console = null;

    /**
     * 应用初始化
     *
     * @param string $app   应用名
     * @return Console
     */
    public static function init(string $app = ''): Console
    {
        // 初始化配置
        static::$name = $app;
        static::initialize($app);
        // 获取控制台实例
        $console = static::console();
        // 设置标题
        $console->setTitle('');

        // 注册内置指令
        $path = __DIR__ . '/command';
        $namespance = 'gaia\\command';
        $console->load($path, $namespance);

        // 注册自定义指令
        if (defined('COMMAND_PATH') && is_dir(COMMAND_PATH)) {
            $namespance = 'support\\command';
            $console->load(COMMAND_PATH, $namespance);
        }

        Event::instance()->trigger('app_start');

        return $console;
    }

    /**
     * 初始化基础配置
     *
     * @param string $app   应用名
     * @return void
     */
    public static function initialize(string $app = '')
    {
        // 加载配置
        defined('ENV_PATH') && file_exists(ENV_PATH) && Env::load(ENV_PATH);
        defined('CONFIG_PATH') && Config::instance()->loadDir(CONFIG_PATH);
        // 定义时区
        date_default_timezone_set(Config::instance()->get('app.timezone', 'PRC'));
        // 初始化日志服务
        Logger::instance()->registerChannel(Config::instance()->get('log', []));
        // 注册workerman配置
        static::initWorker(Config::instance()->get('app.worker', []), $app);
        // 预定义应用钩子
        Event::instance()->handler('handler');
        Event::instance()->register(Config::instance()->get('app.hooks', []));
        // 应用初始化钩子
        Event::instance()->trigger('app_init');
    }

    /**
     * 注册workerman配置
     *
     * @param array $config     配置信息
     * @param string $dirName   workerman生成文件保存目录
     * @return void
     */
    public static function initWorker(array $config, string $dirName = '')
    {
        $fileDir = RUNTIME_PATH . '/gaia/workerman/' . ($dirName ? ($dirName . '/') : '');
        File::instance()->createDir($fileDir);
        // 默认的最大可接受数据包大小
        TcpConnection::$defaultMaxPackageSize = $config['max_package_size'] ?? 10 * 1024 * 1024;
        // 存储主进程PID的文件
        Worker::$pidFile = $fileDir . ($config['pid_file'] ?? 'gaia.pid');
        // 存储关闭服务标准输出的文件，默认 /dev/null。daemonize运行模式下echo的内容才会记录到文件中
        Worker::$stdoutFile = $fileDir . ($config['stdout_file'] ?? 'stdout.log');
        // workerman日志记录文件
        Worker::$logFile = $fileDir . ($config['log_file'] ?? 'workerman.log');
        // 存储主进程状态信息的文件，运行 status 指令后，内容会写入该文件
        Worker::$statusFile = $fileDir . ($config['status_file'] ?? 'gaia.status');
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
     * 获取应用名
     *
     * @return string
     */
    public static function name(): string
    {
        return static::$name;
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
