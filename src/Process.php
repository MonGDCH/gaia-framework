<?php

declare(strict_types=1);

namespace gaia;

use gaia\interfaces\ProcessInterface;

/**
 * 进程服务基类
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
abstract class Process implements ProcessInterface
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

    /**
     * 获取监听的协议scheme
     *
     * @return string
     */
    public static function getListenScheme(): string
    {
        $listen = static::$processConfig['listen'] ?? '';
        if (empty($listen)) {
            return '';
        }
        $parseListen = explode('://', $listen, 2);
        if (count($parseListen) != 2) {
            return '';
        }
        return $parseListen[0];
    }

    /**
     * 获取协议host
     *
     * @return string
     */
    public static function getListenHost(): string
    {
        $listen = static::$processConfig['listen'] ?? '';
        if (empty($listen)) {
            return '';
        }
        $parseListen = explode('://', $listen, 2);
        if (count($parseListen) != 2) {
            return '';
        }
        $parseHost = explode(':', $parseListen[1], 2);
        return $parseHost[0];
    }

    /**
     * 获取协议port
     *
     * @return integer
     */
    public static function getListenPort(): int
    {
        $listen = static::$processConfig['listen'] ?? '';
        if (empty($listen)) {
            return null;
        }
        $parseListen = explode('://', $listen, 2);
        if (count($parseListen) != 2) {
            return null;
        }
        $parseHost = explode(':', $parseListen[1], 2);
        if (count($parseHost) != 2) {
            return null;
        }

        return intval($parseHost[1]);
    }
}
