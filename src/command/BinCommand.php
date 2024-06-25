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
class BinCommand extends Command
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

/*
|--------------------------------------------------------------------------
| 加载composer
|--------------------------------------------------------------------------
| 加载composer autoload文件
|
*/
require __DIR__ . '/../vendor/autoload.php';


/*
|--------------------------------------------------------------------------
| 初始化Gaia
|--------------------------------------------------------------------------
| 这里初始化Gaia
|
*/
\gaia\App::init('%s');


/*
|--------------------------------------------------------------------------
| Plugins插件支持
|--------------------------------------------------------------------------
| 注册Plugins插件
|
*/
\support\Plugin::register();


/*
|--------------------------------------------------------------------------
| 运行程序
|--------------------------------------------------------------------------
| 运行程序，基于workerman管理进程，如需同时运行更多进程，可以在数组内添加
|
*/
\gaia\Gaia::instance()->runProcess([
    // 进程名 => 进程驱动类名, eg: 'test' => Test::class
]);

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
        foreach ($args as $name) {
            $class = strtolower($name);
            // 创建进程文件
            $path = BIN_PATH . DIRECTORY_SEPARATOR . $class . '.php';
            if (file_exists($path)) {
                $output->write("{$name} process start file exists!");
                return;
            }
            $content = sprintf($this->bin_tpl, $class);
            $save = File::instance()->createFile($content, $path, false);
            if (!$save) {
                $output->write("Make {$name} process start file faild!");
                continue;
            }

            $output->write("Make {$name} process start file success!");
        }
    }
}
