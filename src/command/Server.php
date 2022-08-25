<?php

declare(strict_types=1);

namespace gaia\command;

use gaia\Process;
use mon\console\Input;
use mon\console\Output;
use mon\console\Command;

class Server extends Command
{
    protected static $defaultName = 'server';
    protected static $defaultDescription = 'Run service';

    /**
     * 执行指令的接口方法
     *
     * @param Input $input		输入实例
     * @param Output $output	输出实例
     * @return mixed
     */
    public function execute(Input $input, Output $output)
    {
        Process::instance()->run();
    }
}
