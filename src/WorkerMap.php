<?php

declare(strict_types=1);

namespace gaia;

use mon\util\File;
use mon\util\Instance;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

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
     * 所有worker的进程信息，只在linux环境下有效
     * 格式：['worker name' => ['worker id' => 'worker pid', 'worker id 2' => 'worker pid 2']]
     * 
     * @var array
     */
    protected $data = [];

    /**
     * 数据持久化文件
     *
     * @var string
     */
    protected $data_dir = './runtime/gaia/map';

    /**
     * 私有构造方法
     */
    protected function __construct()
    {
        // 初始化
        $this->init();
        // 加载持久化数据
        $this->data = $this->parseWorkerMap();
    }

    /**
     * 初始化
     *
     * @return WorkerMap
     */
    public function init(): WorkerMap
    {
        // 数据持久化文件
        if (defined('RUNTIME_PATH')) {
            $this->data_dir = RUNTIME_PATH . '/gaia/map';
        }
        if (!is_dir($this->data_dir)) {
            File::instance()->createDir($this->data_dir);
        }

        return $this;
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
            return $this->data;
        }

        return $this->data[$name] ?? [];
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
        $this->data[$name][$id] = $pid;
        // 持久化保存
        if ($save) {
            $fileName = $name . '_' . $id;
            $path = $this->data_dir . DIRECTORY_SEPARATOR . $fileName;
            File::instance()->createFile($pid, $path, false);
        }

        return $this;
    }

    /**
     * 删除worker信息
     *
     * @param string $name  进程名
     * @param integer $id   进程ID
     * @param boolean $save 数据是否持久化保存
     * @return boolean
     */
    public function removeWorkerMap(string $name, int $id, bool $save = true): bool
    {
        unset($this->data[$name][$id]);
        if ($save) {
            $fileName = $name . '_' . $id;
            $path = $this->data_dir . DIRECTORY_SEPARATOR . $fileName;
            return File::instance()->removeFile($path);
        }

        return true;
    }

    /**
     * 清空worker进程信息
     *
     * @return boolean
     */
    public function clearWorkerMap(): bool
    {
        $this->data = [];
        $iterator = new RecursiveDirectoryIterator($this->data_dir, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
        $iterator = new RecursiveIteratorIterator($iterator);
        /** @var RecursiveDirectoryIterator $iterator */
        foreach ($iterator as $file) {
            File::instance()->removeFile((string)$file);
        }

        return true;
    }

    /**
     * 解析workermap文件，获取信息
     *
     * @return array
     */
    protected function parseWorkerMap(): array
    {
        $result = [];
        $iterator = new RecursiveDirectoryIterator($this->data_dir, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
        $iterator = new RecursiveIteratorIterator($iterator);
        /** @var RecursiveDirectoryIterator $iterator */
        foreach ($iterator as $file) {
            $fileName = $file->getFilename();
            [$name, $id] = explode('_', $fileName, 2);
            $pid = File::instance()->read((string)$file);
            $result[$name][$id] = intval($pid);
        }

        return $result;
    }
}
