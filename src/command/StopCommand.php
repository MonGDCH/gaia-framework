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
class StopCommand extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static $defaultName = 'stop';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Stop worker. Use mode -g to stop gracefully.';

    /**
     * 指令分组
     *
     * @var string
     */
    protected static $defaultGroup = 'server';

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
