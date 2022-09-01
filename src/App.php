<?php

declare(strict_types=1);

namespace gaia;

use mon\console\App as Console;

/**
 * 初始化gaia
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class App
{
    /**
     * 版本号
     * 
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * 应用初始化
     *
     * @param Console $console  执行管理器实例
     * @return void
     */    
    public static function init(Console $console)
    {
        // 注册指令
        $path = __DIR__ . '/command';
        $namespance = 'gaia\\command';
        $console->load($path, $namespance);
    }
}
