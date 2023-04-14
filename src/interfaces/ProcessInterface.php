<?php

declare(strict_types=1);

namespace gaia\interfaces;

/**
 * 进程接口
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface ProcessInterface
{
    /**
     * 是否启用进程
     *
     * @return boolean
     */
    public static function enable(): bool;

    /**
     * 获取进程配置
     *
     * @return array
     */
    public static function getProcessConfig(): array;

    /**
     * 获取服务监听的协议端口
     *
     * @return string
     */
    public static function getListen(): string;

    /**
     * 获取监听的协议scheme
     *
     * @return string
     */
    public static function getListenScheme(): string;

    /**
     * 获取协议host
     *
     * @return string
     */
    public static function getListenHost(): string;

    /**
     * 获取协议port
     *
     * @return integer
     */
    public static function getListenPort(): int;
}
