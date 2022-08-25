<?php

declare(strict_types=1);

namespace gaia\interfaces;

use Workerman\Worker;

/**
 * 初始化执行业务接口
 */
interface Bootstrap
{
    /**
     * 执行业务
     *
     * @param Worker $worker
     * @return void
     */
    public static function start(Worker $worker);
}
