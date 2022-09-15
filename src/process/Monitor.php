<?php

declare(strict_types=1);

namespace gaia\process;

use SplFileInfo;
use gaia\Process;
use mon\env\Config;
use Workerman\Timer;
use Workerman\Worker;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * 应用监管服务
 * 
 * @see 该服务修改自 webman/monitor
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Monitor extends Process
{
    /**
     * 进程配置
     *
     * @var array
     */
    protected static $processConfig = [
        // 关闭进程重载
        'reloadable' => false
    ];

    /**
     * 监听文件目录
     *
     * @var array
     */
    protected $_paths = [];

    /**
     * 监听文件后缀名
     *
     * @var array
     */
    protected $_extensions = [];

    /**
     * 构造方法
     */
    public function __construct()
    {
        // 函数支持
        $disable_functions = explode(',', ini_get('disable_functions'));
        if (in_array('exec', $disable_functions, true)) {
            echo "\nMonitor file change turned off because exec() has been disabled by disable_functions setting in " . PHP_CONFIG_FILE_PATH . "/php.ini\n";
        }

        $this->_paths = Config::instance()->get('app.monitor.paths', []);
        $this->_extensions = Config::instance()->get('app.monitor.exts', []);
    }

    /**
     * 进程启动
     *
     * @param Worker $worker
     * @return void
     */
    public function onWorkerStart(Worker $worker): void
    {
        // 监听文件更新
        if (!Worker::$daemonize) {
            Timer::add(1, function () {
                $this->checkAllFilesChange();
            });
        }
        // linux环境，监听系统内存
        if (DIRECTORY_SEPARATOR === '/') {
            $memory_limit = $this->getMemoryLimit();
            Timer::add(60, [$this, 'checkMemory'], [$memory_limit]);
        }
    }

    /**
     * 验证文件所有文件是否修改
     *
     * @return boolean
     */
    public function checkAllFilesChange(): bool
    {
        foreach ($this->_paths as $path) {
            if ($this->checkFilesChange($path)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 校验指定文件目录是否修改
     *
     * @param string $monitor_dir 校验的文件目录
     * @return mixed
     */
    public function checkFilesChange(string $monitor_dir)
    {
        static $last_mtime, $too_many_files_check;
        if (!$last_mtime) {
            $last_mtime = time();
        }
        clearstatcache();
        if (!is_dir($monitor_dir)) {
            // 文件处理
            if (!is_file($monitor_dir)) {
                return;
            }
            $iterator = [new SplFileInfo($monitor_dir)];
        } else {
            // 遍历文件目录
            $dir_iterator = new RecursiveDirectoryIterator($monitor_dir, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
            $iterator = new RecursiveIteratorIterator($dir_iterator);
        }
        $count = 0;
        foreach ($iterator as $file) {
            $count++;
            if ($file->isDir()) {
                continue;
            }
            // 校验修改时间
            if ($last_mtime < $file->getMTime() && in_array($file->getExtension(), $this->_extensions, true)) {
                $var = 0;
                exec('"' . PHP_BINARY . '" -l ' . $file, $out, $var);
                if ($var) {
                    $last_mtime = $file->getMTime();
                    continue;
                }
                $last_mtime = $file->getMTime();
                echo $file . " update and reload\n";
                // linux环境，向主进程发送SIGUSR1信号，重新加载程序
                if (DIRECTORY_SEPARATOR === '/') {
                    posix_kill(posix_getppid(), SIGUSR1);
                } else {
                    return true;
                }
                break;
            }
        }
        if (!$too_many_files_check && $count > 1000) {
            echo "Monitor: There are too many files ($count files) in $monitor_dir which makes file monitoring very slow\n";
            $too_many_files_check = 1;
        }
    }

    /**
     * 监听内存变化
     *
     * @param integer $memory_limit
     * @return void
     */
    public function checkMemory(int $memory_limit): void
    {
        $ppid = posix_getppid();
        $children_file = "/proc/$ppid/task/$ppid/children";
        if (!is_file($children_file) || !($children = file_get_contents($children_file))) {
            return;
        }
        foreach (explode(' ', $children) as $pid) {
            $pid = (int)$pid;
            $status_file = "/proc/$pid/status";
            if (!is_file($status_file) || !($status = file_get_contents($status_file))) {
                continue;
            }
            $mem = 0;
            if (preg_match('/VmRSS\s*?:\s*?(\d+?)\s*?kB/', $status, $match)) {
                $mem = $match[1];
            }
            $mem = (int)($mem / 1024);
            if ($mem >= $memory_limit) {
                posix_kill($pid, SIGINT);
            }
        }
    }

    /**
     * 获取系统内存限制
     *
     * @return integer
     */
    protected function getMemoryLimit(): int
    {
        $memory_limit = ini_get('memory_limit');
        $use_php_ini = true;
        if ($memory_limit == -1) {
            return 0;
        }
        $unit = $memory_limit[strlen($memory_limit) - 1];
        if ($unit == 'G') {
            $memory_limit = 1024 * (int)$memory_limit;
        } else if ($unit == 'M') {
            $memory_limit = (int)$memory_limit;
        } else if ($unit == 'K') {
            $memory_limit = (int)($memory_limit / 1024);
        } else {
            $memory_limit = (int)($memory_limit / (1024 * 1024));
        }
        if ($memory_limit < 30) {
            $memory_limit = 30;
        }
        if ($use_php_ini) {
            $memory_limit = (int)(0.8 * $memory_limit);
        }
        return $memory_limit;
    }
}
