<?php

declare(strict_types=1);

namespace gaia;

use Closure;
use mon\util\File;
use mon\env\Config;
use Workerman\Worker;
use mon\util\Instance;
use mon\util\Container;
use gaia\process\Monitor;
use gaia\interfaces\Bootstrap;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

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
    protected $property_map = ['count', 'user', 'group', 'reloadable', 'reusePort', 'transport', 'protocol'];

    /**
     * 回调处理参数
     *
     * @var array
     */
    protected $callback_map = ['onConnect', 'onMessage', 'onClose', 'onError', 'onBufferFull', 'onBufferDrain', 'onWorkerStop', 'onWebSocketConnect'];

    /**
     * 加载进程，运行程序
     *
     * @param string $path
     * @param string $namespace
     * @return void
     */
    public function runProcess(string $path, string $namespace = 'process')
    {
        $dir_iterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
        $iterator = new RecursiveIteratorIterator($dir_iterator);
        /** @var RecursiveDirectoryIterator $iterator */
        foreach ($iterator as $file) {
            // 获取进程对象名
            $dirname = dirname(str_replace($path, '', $file->getPathname()));
            $beforName = str_replace(DIRECTORY_SEPARATOR, '\\', $dirname);
            $beforNamespace = $beforName == '\\' ? '' : $beforName;
            $className = $namespace . $beforNamespace . '\\' . $file->getBasename('.php');
            if (!is_subclass_of($className, '\\gaia\\interfaces\\Process')) {
                continue;
            }
            // 获取进程配置
            $config = $className::getProcessConfig();
            // 获取进程名
            $name = str_replace(DIRECTORY_SEPARATOR, '.', $iterator->getSubPath()) . '.' . $file->getBasename('.php');
            $name = strtolower(ltrim($name, '.'));

            // 运行
            if (DIRECTORY_SEPARATOR === '/') {
                // linux环境
                $config['handler'] = $className::getProcessHandler();
                $this->start($name, $config);
            } else {
                // windows环境
                $process_files[] = $this->createProcessClassFile($name, $className);
            }
        }

        if (DIRECTORY_SEPARATOR === '/') {
            // linux环境
            Worker::runAll();
        } else {
            // windows环境
            $this->runWin($process_files);
        }
    }

    /**
     * 运行程序，自动识别运行环境
     *
     * @return void
     */
    public function run()
    {
        $process_files = [];
        foreach (Config::instance()->get('process', []) as $name => $config) {
            if (DIRECTORY_SEPARATOR === '/') {
                // linux环境
                $this->start($name, $config);
            } else {
                // windows环境
                $process_files[] = $this->createProcessConfigFile($name, 'process.' . $name);
            }
        }
        if (DIRECTORY_SEPARATOR === '/') {
            // linux环境
            Worker::runAll();
        } else {
            // windows环境
            $this->runWin($process_files);
        }
    }

    /**
     * windows环境允许
     *
     * @param array $files
     * @return void
     */
    public function runWin(array $files)
    {
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
     * @param string $name  进程名
     * @param array $config 配置信息
     * @param string $handler  业务回调对象名，优先使用config中的handler
     * @return void
     */
    public function start(string $name, array $config, string $handler = '')
    {
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
            // 执行自定义全局workerStart初始化业务
            $this->startBootstrap($worker);

            // 绑定业务回调
            $handler = $config['handler'] ?? $handler;
            if ($handler) {
                if (!class_exists($handler)) {
                    echo "process error: class {$handler} not exists\r\n";
                    return;
                }

                $instance = Container::instance()->make($handler, $config['constructor'] ?? [], true);
                $this->bindWorker($worker, $instance);
            }
        };
    }

    /**
     * 执行全局初始化业务
     *
     * @param Worker $worker
     * @return void
     */
    protected function startBootstrap(Worker $worker)
    {
        $bootstraps = Config::instance()->get('bootstrap', []);
        foreach ($bootstraps as $bootstrap) {
            if (!class_exists($bootstrap)) {
                echo "Warning: Class $bootstrap setting in config/bootstrap.php not found\r\n";
                continue;
            }
            if (!is_subclass_of($bootstrap, Bootstrap::class)) {
                echo "Warning: Class $bootstrap not implements " . Bootstrap::class . "\r\n";
                continue;
            }

            /** @var Bootstrap $bootstrap */
            $bootstrap::start($worker);
        }
    }

    /**
     * 绑定worker回调
     *
     * @param Worker $worker    worker实例
     * @param mixed $handler    回调实例
     * @return void
     */
    protected function bindWorker(Worker $worker, $handler)
    {
        // 绑定事件回调
        foreach ($this->callback_map as $name) {
            if (method_exists($handler, $name)) {
                $worker->$name = [$handler, $name];
            }
        }
        // 执行workerStart回调
        if (method_exists($handler, 'onWorkerStart')) {
            $handler->onWorkerStart($worker);
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
        $cmd = PHP_BINARY . ' ' . implode(' ', $files);
        $descriptorspec = [STDIN, STDOUT, STDOUT];
        $resource = proc_open($cmd, $descriptorspec, $pipes);
        if (!$resource) {
            exit("Can not execute $cmd\r\n");
        }
        return $resource;
    }

    /**
     * 创建进程启动文件
     *
     * @param string $name
     * @param string $config_name
     * @return string
     */
    public function createProcessConfigFile(string $name, string $config_name): string
    {
        $config = "Config::instance()->get('{$config_name}', [])";
        $tmp = <<<EOF
<?php
require_once __DIR__ . '/../../support/bootstrap.php';

use gaia\Gaia;
use mon\\env\Config;
use Workerman\Worker;

// 打开错误提示
ini_set('display_errors', 'on');
error_reporting(E_ALL);

// 重置opcache
if (is_callable('opcache_reset')) {
    opcache_reset();
}

// 创建启动进程
Gaia::instance()->start('$name', $config);

// 启动程序
Worker::runAll();

EOF;

        $fileName = (defined('RUNTIME_PATH') ? RUNTIME_PATH : './runtime') . '/windows/start_config_' . $name . '.php';
        File::instance()->createFile($tmp, $fileName, false);
        return $fileName;
    }

    /**
     * 创建进程启动文件
     *
     * @param string $name
     * @param string $config_name
     * @return string
     */
    public function createProcessClassFile(string $name, string $config_name)
    {
        $config = "$config_name::getProcessConfig()";
        $handler = "$config_name::getProcessHandler()";
        $tmp = <<<EOF
<?php
require_once __DIR__ . '/../../support/bootstrap.php';

use gaia\Gaia;
use mon\\env\Config;
use Workerman\Worker;

// 打开错误提示
ini_set('display_errors', 'on');
error_reporting(E_ALL);

// 重置opcache
if (is_callable('opcache_reset')) {
    opcache_reset();
}

// 创建启动进程
Gaia::instance()->start('$name', $config, $handler);

// 启动程序
Worker::runAll();

EOF;

        $fileName = (defined('RUNTIME_PATH') ? RUNTIME_PATH : './runtime') . '/windows/start_class_' . $name . '.php';
        File::instance()->createFile($tmp, $fileName, false);
        return $fileName;
    }
}
