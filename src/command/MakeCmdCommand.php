<?php

declare(strict_types=1);

namespace gaia\command;

use mon\util\File;
use mon\console\Input;
use mon\console\Output;
use mon\console\Command;

/**
 * 生成指令类文件指令
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class MakeCmdCommand extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static $defaultName = 'make:cmd';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Make command file util.';

    /**
     * 指令分组
     *
     * @var string
     */
    protected static $defaultGroup = 'gaia';

    /**
     * 指令模板
     *
     * @var string
     */
    protected $cmd_tpl = <<<TPL
<?php

declare(strict_types=1);

namespace support\command;

use mon\console\Input;
use mon\console\Output;
use mon\console\Command;

/**
 * %s 指令
 *
 * Class %s
 * @author Mon <985558837@qq.com>
 * @copyright Gaia
 * @version 1.0.0 %s
 */
class %s extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static \$defaultName = '%s';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static \$defaultDescription = 'The user command [%s]';

    /**
     * 指令分组
     *
     * @var string
     */
    protected static \$defaultGroup = 'user';

    /**
     * 执行指令的接口方法
     *
     * @param Input \$input		输入实例
     * @param Output \$output	输出实例
     * @return mixed
     */
    public function execute(Input \$input, Output \$output)
    {
        \$output->write('%s', true, true);
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
            $className = $class . 'Command';
            // 创建进程文件
            $path = COMMAND_PATH . DIRECTORY_SEPARATOR . $className . '.php';
            if (file_exists($path)) {
                $output->write("Command `{$name}` file exists!");
                return;
            }
            $content = sprintf($this->cmd_tpl, $name, $class, $now, $className, $name, $name, $class);
            $save = File::instance()->createFile($content, $path, false);
            if (!$save) {
                $output->write("Make {$name} command file faild!");
                continue;
            }

            $output->write("Make {$name} command success!");
        }
    }
}
