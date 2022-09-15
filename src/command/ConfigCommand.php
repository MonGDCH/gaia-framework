<?php

declare(strict_types=1);

namespace gaia\command;

use mon\console\Input;
use mon\console\Output;
use mon\console\Command;
use mon\env\Config as Env;

/**
 * 查看配置
 *
 * @author Mon <98555883@qq.com>
 * @version 1.0.0
 */
class ConfigCommand extends Command
{
    protected static $defaultName = 'config';
    protected static $defaultDescription = 'Config utils';

    /**
     * 构造方法
     */
    public function __construct()
    {
        // 加载配置
        defined('CONFIG_PATH') && Env::instance()->loadDir(CONFIG_PATH);
    }

    /**
     * 执行指令
     *
     * @param  Input  $in  输入实例
     * @param  Output $out 输出实例
     * @return integer  exit状态码
     */
    public function execute(Input $in, Output $out)
    {
        // 获取查看的节点
        $args = $in->getArgs();
        $action = $args[0] ?? '';
        $out->write('');
        $config = Env::instance()->get($action, []);


        if (!empty($action)) {
            return $out->dataList((array)$config, $action, false, ['ucFirst' => false]);
        } else {
            foreach ($config as $title => $value) {
                $out->dataList($value, $title, false, ['ucFirst' => false]);
            }

            return 0;
        }
    }
}
