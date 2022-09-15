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
class StatusCommand extends Command
{
    protected static $defaultName = 'status';
    protected static $defaultDescription = 'Show worker status. Use mode -d to show live status.';

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
        Gaia::instance()->run();
    }
}
