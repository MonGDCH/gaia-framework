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
     * 注册插件
     *
     * @return void
     */
    public static function register();

    /**
     * 安装插件
     *
     * @return void
     */
    public static function install();

    /**
     * 卸载插件
     *
     * @return void
     */
    public static function uninstall();
}
