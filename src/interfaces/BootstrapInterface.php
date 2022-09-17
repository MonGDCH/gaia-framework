<?php

declare(strict_types=1);

namespace gaia\interfaces;

use Workerman\Worker;

/**
 * 初始化执行业务接口
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface BootstrapInterface
{
    /**
     * 执行业务
     *
     * @param Worker $worker
     * @return void
     */
    public static function start(Worker $worker);
}
