<?php

declare(strict_types=1);

namespace gaia\command;

use Phar;
use mon\util\File;
use mon\env\Config;
use mon\console\Input;
use mon\console\Output;
use mon\console\Command;

/**
 * 生成Phar包
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class BuildPharCommand extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static $defaultName = 'build:phar';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Build packaged a project into phar';

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
        // 是否启用phar扩展
        if (!class_exists(Phar::class, false)) {
            return $output->error("The 'phar' extension is required for build phar package");
        }
        // 是否支持phar生成
        if (ini_get('phar.readonly')) {
            return $output->error("The 'phar.readonly' is 'On', build phar must setting it 'Off' or exec with 'php -d phar.readonly=0 ./gaia phar:build'");
        }

        // 保存路径
        $dir = Config::instance()->get('app.phar.build_path', ROOT_PATH);
        File::createDir($dir);
        // 移除原文件
        $phar_file = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . Config::instance()->get('app.phar.phar_name', 'gaia.phar');
        if (file_exists($phar_file)) {
            unlink($phar_file);
        }

        // 创建phar包
        $phar = new Phar($phar_file, Phar::KEY_AS_PATHNAME, 'gaia');
        // 开启缓冲区
        $phar->startBuffering();
        // 设置加密算法
        $phar->setSignatureAlgorithm(Config::instance()->get('app.phar.algorithm', Phar::SHA256));
        // 设置包路径
        $exclude_pattern = config('app.phar.exclude_pattern', '#^(?!.*(composer.json|/.github/|/.idea/|/.git/|/.setting/|/runtime/|/vendor-bin/|/build/|/bin/))(.*)$#');
        $phar->buildFromDirectory(ROOT_PATH, $exclude_pattern);
        // 移除文件
        $exclude_files = Config::instance()->get('app.phar.exclude_files', []);
        // 打包生成的phar和bin文件是面向生产环境的，所以以下这些命令没有任何意义，执行的话甚至会出错，需要排除在外。
        $exclude_command_files = [
            'BuildPharCommand.php',
            'BuildBinCommand.php',
            'MakeBinCommand.php',
            'MakeCmdCommand.php',
            'MakeProcessCommand.php',
            'VendorPublishCommand.php',
        ];
        $exclude_command_files = array_map(function ($cmd_file) {
            return 'vendor/mongdch/gaia-framework/src/command/' . $cmd_file;
        }, $exclude_command_files);
        $exclude_files = array_unique(array_merge($exclude_command_files, $exclude_files));
        foreach ($exclude_files as $file) {
            if ($phar->offsetExists($file)) {
                $phar->delete($file);
            }
        }

        $output->write('Files collect complete, begin add file to Phar.');

        // 设置加载器
        $phar->setStub("#!/usr/bin/env php
<?php
define('IN_PHAR', true);
Phar::mapPhar('gaia');
require 'phar://gaia/gaia';
__HALT_COMPILER();
");

        // 关闭缓存区
        $phar->stopBuffering();
        unset($phar);
        return $output->write('Write requests to the Phar archive, save changes to disk.');
    }
}
