<?php

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
        $listen = static::getListen();
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
        $listen = static::getListen();
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
        $listen = static::getListen();
        if (empty($listen)) {
            return '';
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
