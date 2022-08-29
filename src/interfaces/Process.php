<?php

declare(strict_types=1);

namespace gaia\interfaces;

/**
 * 进程实现接口
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface Process
{
    /**
     * 获取进程配置
     *
     * @return array
     */
    public static function getProcessConfig(): array;
}
