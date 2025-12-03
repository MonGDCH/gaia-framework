<?php

declare(strict_types=1);

namespace gaia\command;

use gaia\App;
use mon\console\Input;
use mon\console\Output;
use mon\console\Command;

/**
 * 查看版本号
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class VersionCommand extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static $defaultName = 'version';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Show Gaia Version';

    /**
     * 指令别名
     *
     * @var string
     */
    protected static $defaultAliasName = 'v';

    /**
     * 指令分组
     *
     * @var string
     */
    protected static $defaultGroup = 'gaia';

    /**
     * 执行指令的接口方法
     *
     * @param Input $input		输入实例
     * @param Output $output	输出实例
     * @return mixed
     */
    public function execute(Input $input, Output $output)
    {
        $output->write(App::VERSION);
    }
}
