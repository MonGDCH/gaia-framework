<?php

declare(strict_types=1);

namespace gaia\interfaces;

/**
 * 进程实现接口
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
}
