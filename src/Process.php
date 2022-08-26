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
     * 进程配置
     *
     * @var array
     */
    protected static $processConfig = [];

    /**
     * 进程回调
     *
     * @var string
     */
    protected static $processHandler = __CLASS__;

    /**
     * 获取进程配置
     *
     * @return array
     */
    public static function getProcessConfig(): array
    {
        return static::$processConfig;
    }

    /**
     * 获取进程回调
     *
     * @return string
     */
    public static function getProcessHandler(): string
    {
        return static::$processHandler;
    }
}
