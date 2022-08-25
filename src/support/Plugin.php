<?php

namespace support;

/**
 * 插件安装驱动，兼容webman插件
 * 
 * @see 修改自webman/plugin
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Plugin
{
    /**
     * 安装
     *
     * @param mixed $event
     * @return void
     */
    public static function install($event)
    {
        $namespace = static::getNamespace($event);
        if (is_null($namespace)) {
            return;
        }
        $install_function = "\\{$namespace}Install::install";
        if (static::checkPlugin($namespace) && is_callable($install_function)) {
            $install_function();
        }
    }

    /**
     * 更新
     *
     * @param mixed $event
     * @return void
     */
    public static function update($event)
    {
        static::install($event);
    }

    /**
     * 卸载
     *
     * @param mixed $event
     * @return void
     */
    public static function uninstall($event)
    {
        $namespace = static::getNamespace($event);
        if (is_null($namespace)) {
            return;
        }

        $uninstall_function = "\\{$namespace}Install::uninstall";
        if (static::checkPlugin($namespace) && is_callable($uninstall_function)) {
            $uninstall_function();
        }
    }

    /**
     * 是否为webman或者gaia的插件
     *
     * @param string $namespace
     * @return boolean
     */
    protected static function checkPlugin($namespace)
    {
        $webman = "\\{$namespace}Install::WEBMAN_PLUGIN";
        $gaia =  "\\{$namespace}Install::GAIA_PLUGIN";

        return defined($webman) || defined($gaia);
    }

    /**
     * 获取命名空间
     *
     * @param mixed $event
     * @return string|null
     */
    protected static function getNamespace($event)
    {
        $operation = $event->getOperation();
        $autoload = method_exists($operation, 'getPackage') ? $operation->getPackage()->getAutoload() : $operation->getTargetPackage()->getAutoload();
        if (!isset($autoload['psr-4'])) {
            return null;
        }

        return key($autoload['psr-4']);
    }

    /**
     * 初始化
     *
     * @return void
     */
    protected static function init()
    {
        // Plugin.php in vendor
        $file = __DIR__ . '/../../../../../support/init.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
        require_once __DIR__ . '/init.php';
    }
}
