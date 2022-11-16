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
class ReloadCommand extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static $defaultName = 'reload';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Reload worker. Use -g to reload gracefully. Reload worker name can be specify.';

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
        $process = $input->getArgs();
        if (!empty($process)) {
            // 是否使用优雅重载
            $isGracefully = $input->getSopt('g', false);
            $sig = $isGracefully ? SIGUSR2 : SIGUSR1;
            // 获取指定进程pid
            foreach ($process as $name) {
                // 获取进程信息
                $map = WorkerMap::instance()->getWorkerMap($name);
                // 重启进程
                foreach ($map as $worker_id => $worker_pid) {
                    posix_kill((int)$worker_pid, $sig);
                }
            }
        } else {
            // 全量重载，需要清空原进程信息
            WorkerMap::instance()->clearWorkerMap();
            Gaia::instance()->run();
        }
    }
}
