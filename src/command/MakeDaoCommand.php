<?php

declare(strict_types=1);

namespace gaia\command;

use mon\util\File;
use mon\console\Input;
use mon\console\Output;
use mon\console\Command;

/**
 * 生成Dao类文件指令
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class MakeDaoCommand extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static $defaultName = 'make:dao';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Make app dao file util.';

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

namespace app\dao;

use mon\\thinkORM\Dao;
use mon\util\Instance;

/**
 * %s Dao操作类
 *
 * Class %s
 * @author Mon <985558837@qq.com>
 * @copyright Gaia
 * @version 1.0.0 %s
 */
class %s extends Dao
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected \$table = '%s';
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
            $className = $class . 'Dao';
            // 创建进程文件
            $path = APP_PATH . DIRECTORY_SEPARATOR . 'dao' . DIRECTORY_SEPARATOR . $className . '.php';
            if (file_exists($path)) {
                $output->write("Dao `{$name}` file exists!");
                return;
            }
            $table = $this->camelToSnake($name);
            $content = sprintf($this->cmd_tpl, $name, $class, $now, $className, $table);
            $save = File::createFile($content, $path, false);
            if (!$save) {
                $output->write("Make {$name} Dao file faild!");
                continue;
            }

            $output->write("Make {$name} Dao success!");
        }
    }

    /**
     * 驼峰命名转下划线命名
     *
     * @param string $str 驼峰字符串（如 userInfo/UserInfo）
     * @param bool $lower 是否全小写（默认 true，false 则保留原大写位置的小写）
     * @return string 下划线字符串（如 user_info）
     */
    private function camelToSnake(string $str, bool $lower = true): string
    {
        // 1. 匹配大写字母，在其前添加下划线（处理大驼峰开头）
        $snakeStr = preg_replace('/([A-Z])/', '_$1', $str);
        // 2. 转小写（可选）+ 移除开头的下划线（大驼峰开头会多出下划线）
        $snakeStr = ltrim($snakeStr, '_');
        // 3. 统一转小写
        return $lower ? strtolower($snakeStr) : $snakeStr;
    }
}
