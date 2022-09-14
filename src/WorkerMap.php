<?php

declare(strict_types=1);

namespace gaia;

use mon\util\File;
use mon\util\Instance;

/**
 * worker进程信息
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class WorkerMap
{
    use Instance;

    /**
     * 数据持久化文件
     *
     * @var string
     */
    protected $worker_file = './runtime/gaia.workermap';

    /**
     * 所有worker的进程信息，只在linux环境下有效
     * 格式：['worker name' => ['worker id' => 'worker pid', 'worker id 2' => 'worker pid 2']]
     * 
     * @var array
     */
    protected $worker_map = [];

    /**
     * 私有构造方法
     */
    protected function __construct()
    {
        // 数据持久化文件
        if (defined('RUNTIME_PATH')) {
            $this->worker_file = RUNTIME_PATH . '/gaia.workermap';
        }
        // 加载持久化数据
        $this->worker_map = $this->parseWorkerMap();
    }

    /**
     * 所有worker的进程信息，只在linux环境下有效
     *
     * @param string $name  进程名
     * @return array
     */
    public function getWorkerMap(string $name = ''): array
    {
        if (empty($name)) {
            return $this->worker_map;
        }

        return $this->worker_map[$name] ?? [];
    }

    /**
     * 设置worker信息
     *
     * @param string $name  进程名
     * @param integer $id   进程ID
     * @param integer $pid  进程PID
     * @param boolean $save 数据是否持久化保存
     * @return WorkerMap
     */
    public function setWorkerMap(string $name, int $id, int $pid, bool $save = true): WorkerMap
    {
        // 设置worker信息
        $this->worker_map[$name][$id] = $pid;
        // 持久化保存
        if ($save) {
            $arr = [$name, $id, $pid];
            $line = implode(',', $arr) . PHP_EOL;
            File::instance()->createFile($line, $this->worker_file);
        }

        return $this;
    }

    /**
     * 清空worker进程信息
     *
     * @return boolean
     */
    public function clearWorkerMap(): bool
    {
        $this->worker_map = [];
        return File::instance()->removeFile($this->worker_file);
    }

    /**
     * 解析workermap文件，获取信息
     *
     * @return array
     */
    protected function parseWorkerMap(): array
    {
        $result = [];
        if (!file_exists($this->worker_file)) {
            // 文件不存在，直接放回空数组
            return $result;
        }
        // 读取文件，解析信息
        $fileContent = file($this->worker_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ((array)$fileContent as $line) {
            [$name, $id, $pid] = explode(',', $line, 3);
            $result[$name][$id] = $pid;
        }

        return $result;
    }
}
