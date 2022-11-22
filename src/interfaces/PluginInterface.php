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
     * 是否启用插件
     *
     * @return boolean
     */
    public static function enable(): bool;

    /**
     * 初始化插件
     *
     * @param array $config 插件配置信息
     * @return void
     */
    public static function init(array $config);
}
