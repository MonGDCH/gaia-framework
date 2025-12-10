<?php

declare(strict_types=1);

namespace gaia;

use Throwable;
use mon\env\Env;
use mon\util\OS;
use mon\util\File;
use mon\util\Event;
use mon\env\Config;
use mon\log\Logger;
use Workerman\Worker;
use mon\util\Instance;
use mon\util\Container;
use gaia\process\Monitor;
use InvalidArgumentException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use gaia\interfaces\ProcessInterface;

/**
 * 进程管理
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Gaia
{
    use Instance;

    /**
     * 进程参数
     *
     * @var array
     */
    protected $property_map = [
        // 进程数
        'count',
        // 进程用户
        'user',
        // 进程用户组
        'group',
        // 是否允许进程重载
        'reloadable',
        // 是否允许端口复用
        'reusePort',
        // 通信协议
        'transport',
        // 连接的协议类
        'protocol',
        // 事件循环类
        'eventLoop'
    ];

    /**
     * 回调处理参数
     *
     * @var array
     */
    protected $callback_map = [
        // 建立连接时
        'onConnect',
        // 接收消息时
        'onMessage',
        // 断开连接时
        'onClose',
        // 连接发生错误时
        'onError',
        // 数据缓冲区满载时
        'onBufferFull',
        // 应用层发送缓冲区数据全部发送完成时
        'onBufferDrain',
        // websocket链接时
        'onWebSocketConnect'
    ];

    /**
     * 加载进程，运行程序
     *
     * @see 该方法已废弃，启动进程请使用bin目录指定启动的进程进行操作
     * @param string $path  进程加载文件路径
     * @param string $namespace 命名空间
     * @param boolean $monitor  是否启动监听进程
     * @return void
     */
    public function run(string $path = '', string $namespace = '\process', bool $monitor = true)
    {
        // 加载进程
        $path = empty($path) ? (defined('PROCESS_PATH') ? PROCESS_PATH : './process') : $path;
        $process = [];
        $dir_iterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
        $iterator = new RecursiveIteratorIterator($dir_iterator);
        /** @var RecursiveDirectoryIterator $iterator */
        foreach ($iterator as $file) {
            // 获取进程对象名
            $dirname = dirname(str_replace($path, '', $file->getPathname()));
            $beforName = str_replace(DIRECTORY_SEPARATOR, '\\', $dirname);
            $beforNamespace = $beforName == '\\' ? '' : $beforName;
            $className = $namespace . $beforNamespace . '\\' . $file->getBasename('.php');
            if (!is_subclass_of($className, ProcessInterface::class)) {
                continue;
            }
            // 获取进程名
            $name = str_replace(DIRECTORY_SEPARATOR, '.', $iterator->getSubPath()) . '.' . $file->getBasename('.php');
            $name = strtolower(ltrim($name, '.'));
            $process[$name] = $className;
        }

        $this->runProcess($process, $monitor, App::name() ?: 'gaia');
    }

    /**
     * 启动进程
     *
     * @param array $process    启动进程列表 [['name' => 'process'], ...]
     * @param boolean $monitor  是否启动监听进程
     * @param string $dirName   win环境下生成进程文件保存二级目录
     * @return void
     */
    public function runProcess(array $process, bool $monitor = true, string $dirName = '')
    {
        $dirName = $dirName ?: App::name();
        // 应用运行钩子
        Event::instance()->trigger('app_run');
        // 加载进程
        $process_files = [];
        foreach ($process as $name => $className) {
            if (!is_subclass_of($className, ProcessInterface::class)) {
                throw new InvalidArgumentException('Process: ' . $className . ' must be subclass of ' . ProcessInterface::class);
            }

            // 转换进程名
            $name = strval($name);
            // 获取进程配置
            $config = $className::getProcessConfig();
            // 运行
            if (!OS::isWindows()) {
                // linux环境
                $this->bootstrap($name, $config, $className);
            } else {
                // windows环境
                $saveName = str_replace('.', '_', $name);
                $process_files[] = $this->createProcessFile($name, $className, $saveName, $dirName);
            }
        }

        // 非phar环境，运行内置monitor进程，启动进程
        $name = 'monitor';
        if (!OS::isWindows()) {
            // linux环境
            if ($monitor) {
                $this->bootstrap($name, Monitor::getProcessConfig(), Monitor::class);
            }
            Worker::runAll();
        } else {
            // windows环境
            if ($monitor) {
                $process_files[] = $this->createProcessFile($name, Monitor::class, $name, $dirName);
            }
            $this->runWin($process_files, $dirName);
        }
    }

    /**
     * windows环境运行
     *
     * @param array $files
     * @return void
     */
    public function runWin(array $files, string $dirName)
    {
        // 兼容处理windows下start服务重启问题
        if (defined('COMPATIBLE_MOD') && COMPATIBLE_MOD) {
            $startFile = $this->createWinStartFile($dirName);
            array_unshift($files, $startFile);
        }

        $resource = $this->open_process($files);
        // windows环境需要重新创建监听服务
        $monitor = new Monitor();
        // 监听重新加载程序
        while (1) {
            sleep(1);
            if ($monitor->checkAllFilesChange()) {
                $status = proc_get_status($resource);
                $pid = $status['pid'];
                shell_exec("taskkill /F /T /PID $pid");
                proc_close($resource);
                $resource = $this->open_process($files);
            }
        }
    }

    /**
     * 运行一个进程
     *
     * @param string $name      进程名
     * @param array $config     配置信息
     * @param string $handler   业务回调对象名，优先使用config中的handler
     * @return void
     */
    public function bootstrap(string $name, array $config, string $handler = '')
    {
        // 进程启动钩子
        Event::instance()->trigger('process_init', $name, $config);
        // 创建worker
        $worker = new Worker($config['listen'] ?? null, $config['context'] ?? []);
        // 定义worker
        $worker->name = $name;
        foreach ($this->property_map as $property) {
            if (isset($config[$property])) {
                $worker->$property = $config[$property];
            }
        }
        // 进程启动
        $worker->onWorkerStart = function ($worker) use ($config, $handler) {
            try {
                // 执行自定义全局workerStart初始化业务
                $this->start($worker);
                // 绑定业务回调
                $handler = $config['handler'] ?? $handler;
                if ($handler) {
                    if (!class_exists($handler)) {
                        echo "process error: class {$handler} not exists\r\n";
                        return;
                    }

                    $instance = Container::instance()->make($handler, $config['constructor'] ?? []);
                    $this->bindWorker($worker, $instance);
                }
            } catch (Throwable $e) {
                Event::instance()->trigger('process_error', ['worker' => $worker, 'error' => $e]);
                throw $e;
            }
        };
    }

    /**
     * 执行全局初始化业务
     *
     * @param Worker $worker
     * @return void
     */
    protected function start(Worker $worker)
    {
        // 加载配置文件
        defined('ENV_PATH') && file_exists(ENV_PATH) && Env::load(ENV_PATH);
        defined('CONFIG_PATH') && Config::instance()->loadDir(CONFIG_PATH);
        // 定义时区
        date_default_timezone_set(Config::instance()->get('app.timezone', 'PRC'));
        // 初始化日志服务
        Logger::instance()->registerChannel(Config::instance()->get('log', []));
        // 执行初始化钩子
        Event::instance()->trigger('process_start', $worker);
    }

    /**
     * 绑定worker回调
     *
     * @param Worker $worker    worker实例
     * @param mixed $handler    回调实例
     * @return void
     */
    protected function bindWorker(Worker $worker, object $handler)
    {
        // 绑定事件回调
        foreach ($this->callback_map as $name) {
            if (method_exists($handler, $name) && is_callable([$handler, $name])) {
                $worker->$name = [$handler, $name];
            }
        }
        // 执行workerStart回调
        if (method_exists($handler, 'onWorkerStart') && is_callable([$handler, 'onWorkerStart'])) {
            $handler->onWorkerStart($worker);
        }

        // 进程关闭
        if (method_exists($handler, 'onWorkerStop') && is_callable([$handler, 'onWorkerStop'])) {
            $handler->onWorkerStop($worker);
        }
    }

    /**
     * 打开进程
     *
     * @param array $files  文件列表
     * @return resource
     */
    public function open_process(array $files)
    {
        $cmd = '"' . PHP_BINARY . '" ' . implode(' ', $files);
        $descriptorspec = [STDIN, STDOUT, STDOUT];
        $resource = proc_open($cmd, $descriptorspec, $pipes, null, null, ['bypass_shell' => true]);
        if (!$resource) {
            exit("Can not execute $cmd\r\n");
        }
        return $resource;
    }

    /**
     * 创建进程启动文件
     *
     * @param string $name  进程名
     * @param string $handlerName  回调名
     * @param string $saveName  保存文件名，默认为进程名
     * @param string $dirName   保存文件二级目录名
     * @return string
     */
    public function createProcessFile(string $name, string $handlerName, string $saveName = '', string $dirName = ''): string
    {
        $app = App::name();
        $config = "$handlerName::getProcessConfig()";
        $handler = "$handlerName::class";
        $autoloadFile = $dirName ? '/../../../../vendor/autoload.php' : '/../../../vendor/autoload.php';
        $tmp = <<<EOF
<?php

require_once __DIR__ . '$autoloadFile';

// 打开错误提示
ini_set('display_errors', 'on');
error_reporting(E_ALL);

// 重置opcache
if (is_callable('opcache_reset')) {
    opcache_reset();
}

// Gaia初始化
\gaia\App::initialize('$app');

// Gaia插件注册
\support\Plugin::register();

// 创建启动进程
\gaia\Gaia::instance()->bootstrap('$name', $config, $handler);

// 启动程序
\Workerman\Worker::runAll();

EOF;

        $saveName = $saveName ?: $name;
        $savePath = '/gaia/windows' . ($dirName ? "/{$dirName}/" : '/') . 'start_' . $saveName . '.php';
        $fileName = (defined('RUNTIME_PATH') ? RUNTIME_PATH : './runtime') . $savePath;
        File::createFile($tmp, $fileName, false);
        return $fileName;
    }

    /**
     * 创建windows环境下启动文件
     *
     * @param string $dirName   二级目录名
     * @return string
     */
    protected function createWinStartFile(string $dirName = ''): string
    {
        $app = App::name();
        $autoloadFile = $dirName ? '/../../../../vendor/autoload.php' : '/../../../vendor/autoload.php';
        $tmp = <<<EOF
<?php

require_once __DIR__ . '$autoloadFile';

// 打开错误提示
ini_set('display_errors', 'on');
error_reporting(E_ALL);

// 重置opcache
if (is_callable('opcache_reset')) {
    opcache_reset();
}

// Gaia初始化
\gaia\App::initialize('$app');

// 启动程序
\Workerman\Worker::runAll();
        
EOF;
        $savePath = '/gaia/windows' . ($dirName ? "/{$dirName}/" : '/') . 'start.php';
        $fileName = (defined('RUNTIME_PATH') ? RUNTIME_PATH : './runtime') . $savePath;
        File::createFile($tmp, $fileName, false);
        return $fileName;
    }
}
