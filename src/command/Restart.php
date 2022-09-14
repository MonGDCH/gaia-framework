<?php

declare(strict_types=1);

namespace gaia\command;

use gaia\Gaia;
use gaia\WorkerMap;
use mon\console\Input;
use mon\console\Output;
use mon\console\Command;

/**
 * 运行workerman
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Restart extends Command
{
    protected static $defaultName = 'restart';
    protected static $defaultDescription = 'Restart workers. Use mode -d to start in DAEMON mode.';

    /**
     * 执行指令的接口方法
     *
     * @param Input $input		输入实例
     * @param Output $output	输出实例
     * @return mixed
     */
    public function execute(Input $input, Output $output)
    {
        if (DIRECTORY_SEPARATOR !== '/') {
            return $output->error('The `' . self::$defaultName . '` command for windows env not supported!');
        }
        WorkerMap::instance()->clearWorkerMap();
        Gaia::instance()->run();
    }
}
