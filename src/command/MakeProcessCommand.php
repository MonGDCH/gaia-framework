<?php

declare(strict_types=1);

namespace gaia\command;

use mon\util\File;
use mon\console\Input;
use mon\console\Output;
use mon\console\Command;

/**
 * 生成进程驱动文件指令
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class MakeProcessCommand extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static $defaultName = 'make:process';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Make process file util.';

    /**
     * 指令分组
     *
     * @var string
     */
    protected static $defaultGroup = 'gaia';

    /**
     * 进程模板
     *
     * @var string
     */
    protected $process_tpl = <<<TPL
<?php

declare(strict_types=1);

namespace support\process;

use gaia\Process;
use Workerman\Worker;
use gaia\interfaces\ProcessInterface;

/**
 * %s 进程
 *
 * Class %s
 * @author Mon <985558837@qq.com>
 * @copyright Gaia
 * @version 1.0.0 %s
 */
class %s extends Process implements ProcessInterface
{
    /**
     * 进程配置
     *
     * @var array
     */
    protected static \$processConfig = [
        // 监听协议端口
        'listen'        => '',
        // 额外参数
        'context'       => [],
        // 进程数
        'count'         => 1,
        // 通信协议，一般不需要修改
        'transport'     => 'tcp',
        // 进程用户，一般不需要修改
        'user'          => '',
        // 进程用户组，一般不需要修改
        'group'         => '',
        // 是否开启端口复用
        'reusePort'     => false,
        // 是否允许进程重载
        'reloadable'    => true,
    ];

    /**
     * 进程启动
     *
     * @param Worker \$worker worker进程
     * @return void
     */
    public function onWorkerStart(Worker \$worker): void
    {
        // 进程启动初始化业务
    }
}
TPL;

    /**
     * 执行指令
     *
     * @param  Input  $in  输入实例
     * @param  Output $out 输出实例
     * @return mixed
     */
    public function execute(Input $input, Output $output)
    {
        $args = $input->getArgs();
        $now = date('Y-m-d');
        foreach ($args as $name) {
            $class = ucfirst($name);
            // 创建进程文件
            $path = PROCESS_PATH . DIRECTORY_SEPARATOR . $class . '.php';
            if (file_exists($path)) {
                $output->write("{$name} process file exists!");
                return;
            }
            $content = sprintf($this->process_tpl, $name, $class, $now, $class, $name, $name);
            $save = File::instance()->createFile($content, $path, false);
            if (!$save) {
                $output->write("Make {$name} process file faild!");
                continue;
            }

            $output->write("Make {$name} process success!");
        }
    }
}
