<?php

declare(strict_types=1);

namespace gaia\command;

use gaia\Gaia;
use mon\console\Input;
use mon\console\Output;
use mon\console\Command;

/**
 * 运行workerman
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Start extends Command
{
    protected static $defaultName = 'start';
    protected static $defaultDescription = 'Start worker in DEBUG mode. Use mode -d to start in DAEMON mode. Use mode -g to stop gracefully.';

    /**
     * 执行指令的接口方法
     *
     * @param Input $input		输入实例
     * @param Output $output	输出实例
     * @return mixed
     */
    public function execute(Input $input, Output $output)
    {
        Gaia::instance()->run();
    }
}
