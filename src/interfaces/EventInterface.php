<?php

declare(strict_types=1);

namespace gaia\interfaces;

/**
 * 钩子接口
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface EventInterface
{
    /**
     * 钩子回调处理接口
     *
     * @param string $event 钩子名称
     * @return boolean  返回false则停止继续运行钩子
     */
    public function handler(string $event): bool;
}
