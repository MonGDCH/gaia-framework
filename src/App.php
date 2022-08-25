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
     * 应用初始化
     *
     * @return void
     */
    public static function init()
    {
        $path = __DIR__ . '/command';
        $namespance = 'gaia\\command';
        Console::instance()->load($path, $namespance);
    }
}
