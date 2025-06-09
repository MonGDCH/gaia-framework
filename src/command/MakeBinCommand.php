<?php

declare(strict_types=1);

namespace gaia\command;

use mon\util\File;
use mon\console\Input;
use mon\console\Output;
use mon\console\Command;

/**
 * 生成进程启动文件指令
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class MakeBinCommand extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static $defaultName = 'make:bin';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Make process start file util.';

    /**
     * 指令分组
     *
     * @var string
     */
    protected static $defaultGroup = 'gaia';

    /**
     * 模板
     *
     * @var string
     */
    protected $bin_tpl = <<<TPL
#!/usr/bin/env php
<?php

declare(strict_types=1);

use gaia\App;
use gaia\Gaia;
use support\Plugin;

/**
 * %s 应用启动入口
 *
 * Class %s
 * @author Mon <985558837@qq.com>
 * @copyright Gaia
 * @version 1.0.0 %s
 */
class %s
{
    /**
     * 应用名称
     *
     * @var string
     */
    protected \$name = '%s';

    /**
     * 启动进程
     *
     * @example 进程名 => 进程驱动类名, eg: ['test' => Test::class]
     * @var array
     */
    protected \$process = [];

    /**
     * 开启插件支持
     *
     * @var boolean
     */
    protected \$supportPlugin = true;

    /**
     * 构造方法
     */
    public function __construct()
    {
        // 加载composer autoload文件
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    /**
     * 启动应用
     *
     * @return void
     */
    public function run()
    {
        if (empty(\$this->process)) {
            echo '未定义启动进程';
            return;
        }
        if (empty(\$this->name)) {
            echo '未定义应用名称';
            return;
        }

        // 初始化
        App::init(\$this->name);

        // 加载插件
        \$this->supportPlugin && Plugin::register();

        // TODO 更多操作

        // 启动服务
        Gaia::instance()->runProcess(\$this->process);
    }

    /**
     * 获取应用名称
     *
     * @return string
     */
    public function getName(): string
    {
        return \$this->name;
    }

    /**
     * 获取应用启动进程
     *
     * @return array
     */
    public function getProcess(): array
    {
        return \$this->process;
    }
}

// 启用应用
(new %s)->run();

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
        $now = date('Y-m-d H:i:s', time());
        $args = $input->getArgs();
        foreach ($args as $name) {
            $className = ucfirst($name);
            // 创建进程文件
            $path = BIN_PATH . DIRECTORY_SEPARATOR . $name . '.php';
            if (file_exists($path)) {
                $output->write("{$name} process start file exists!");
                return;
            }
            $content = sprintf($this->bin_tpl, $name, $className, $now, $className, $name, $className);
            $save = File::createFile($content, $path, false);
            if (!$save) {
                $output->write("Make {$name} process start file faild!");
                continue;
            }

            $output->write("Make {$name} process start file success!");
        }
    }
}
