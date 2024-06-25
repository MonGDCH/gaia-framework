<?php

declare(strict_types=1);

namespace gaia;

/**
 * 进程接口方法实现Trait
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
trait ProcessTrait
{
    /**
     * 获取监听的协议scheme
     *
     * @return string
     */
    public static function getListenScheme(): string
    {
        static $scheme = null;
        if (!is_null($scheme)) {
            return $scheme;
        }

        $listen = static::getListen();
        if (empty($listen)) {
            return '';
        }
        $parseListen = explode('://', $listen, 2);
        if (count($parseListen) != 2) {
            return '';
        }

        $scheme = $parseListen[0];
        return $scheme;
    }

    /**
     * 获取协议host
     *
     * @return string
     */
    public static function getListenHost(): string
    {
        static $host = null;
        if (!is_null($host)) {
            return $host;
        }

        $listen = static::getListen();
        if (empty($listen)) {
            return '';
        }
        $parseListen = explode('://', $listen, 2);
        if (count($parseListen) != 2) {
            return '';
        }

        $parseHost = explode(':', $parseListen[1], 2);
        $host = $parseHost[0];
        return $host;
    }

    /**
     * 获取协议port
     *
     * @return integer
     */
    public static function getListenPort(): int
    {
        static $port = null;
        if (!is_null($port)) {
            return $port;
        }
        $listen = static::getListen();
        if (empty($listen)) {
            return -1;
        }
        $parseListen = explode('://', $listen, 2);
        if (count($parseListen) != 2) {
            return -1;
        }
        $parseHost = explode(':', $parseListen[1], 2);
        if (count($parseHost) != 2) {
            return -1;
        }

        $port = intval($parseHost[1]);
        return $port;
    }

    /**
     * 获取服务监听的协议端口
     *
     * @return string
     */
    public static function getListen(): string
    {
        return static::getProcessConfig()['listen'] ?? '';
    }
}
