<?php

declare(strict_types=1);

namespace gaia\command;

use mon\console\Input;
use mon\console\Output;
use mon\console\Command;

/**
 * 查看配置
 *
 * @author Mon <98555883@qq.com>
 * @version 1.0.0
 */
class VendorPublishCommand extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static $defaultName = 'vendor:publish';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Publish vendor plugins.';

    /**
     * 指令分组
     *
     * @var string
     */
    protected static $defaultGroup = 'gaia';

    /**
     * 执行指令
     *
     * @param  Input  $in  输入实例
     * @param  Output $out 输出实例
     * @return integer  exit状态码
     */
    public function execute(Input $in, Output $out)
    {
        $args = $in->getArgs();
        if (empty($args)) {
            return $out->error('Please enter package namespace.');
        }
        $vendor = $args[0];
        $plugin = "\\{$vendor}\\Install";
        if (!defined($plugin . '::GAIA_PLUGIN')) {
            return $out->error('The package not support Gaia!');
        }
        $callback = $plugin . '::publish';
        if (is_callable($callback)) {
            $callback();
        } else {
            return $out->error('The package [' . $plugin . '] not support Gaia Publish!');
        }

        return $out->block('Publish ' . $vendor, 'success');
    }
}
