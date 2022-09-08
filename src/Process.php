<?php

declare(strict_types=1);

namespace gaia;

use gaia\interfaces\Process as InterfacesProcess;

/**
 * 进程服务基类
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
abstract class Process implements InterfacesProcess
{
    /**
     * 启用进程
     *
     * @var boolean
     */
    protected static $enable = true;

    /**
     * 进程配置
     *
     * @var array
     */
    protected static $processConfig = [];

    /**
     * 是否启用进程
     *
     * @return boolean
     */
    public static function enable(): bool
    {
        return static::$enable;
    }

    /**
     * 获取进程配置
     *
     * @return array
     */
    public static function getProcessConfig(): array
    {
        return static::$processConfig;
    }
}
