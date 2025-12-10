<?php

declare(strict_types=1);

namespace gaia\interfaces;

/**
 * 插件接口
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface PluginInterface
{
    /**
     * 启动插件
     *
     * @return void
     */
    public static function start();
}
