<?php

declare(strict_types=1);

namespace gaia\command;

use Phar;
use mon\util\File;
use mon\env\Config;
use mon\console\Input;
use mon\console\Output;
use mon\console\Command;
use mon\util\Obfuscator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

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
            return $output->error("The 'phar.readonly' is 'On', build phar must setting it 'Off' or exec with " . Output::WARING . " 'php -d phar.readonly=0 ./gaia build:phar'",);
        }

        // 保存路径
        $dir = Config::instance()->get('app.phar.build_path', ROOT_PATH . DIRECTORY_SEPARATOR . 'build');
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
        // 文件混淆处理配置
        $obfuscator_config = Config::instance()->get('app.phar.obfuscator.config', []);
        // 文件混淆处理过滤变量名
        $obfuscator_fillterVars = Config::instance()->get('app.phar.obfuscator.fillterVars', []);
        // 混淆处理
        $obfuscator = new Obfuscator($obfuscator_config, $obfuscator_fillterVars);
        // 移除的目录
        $exclude_dirs = Config::instance()->get('app.phar.exclude_dirs', []);
        // 排除文件名
        $exclude_files = Config::instance()->get('app.phar.exclude_files', []);
        // 排除的文件完整路径
        $exclude_filePaths = array_merge(Config::instance()->get('app.phar.exclude_filePaths', []), [
            'vendor/mongdch/gaia-framework/src/command/BuildPharCommand.php',
            'vendor/mongdch/gaia-framework/src/command/BuildBinCommand.php',
            'vendor/mongdch/gaia-framework/src/command/MakeBinCommand.php',
            'vendor/mongdch/gaia-framework/src/command/MakeCmdCommand.php',
            'vendor/mongdch/gaia-framework/src/command/MakeProcessCommand.php',
            'vendor/mongdch/gaia-framework/src/command/VendorPublishCommand.php',
            'support/command/http/RouteCacheCommand.php',
            'support/command/http/RouteClearCommand.php',
            'support/command/crontab/DbInitCommand.php',
            'support/command/queue/DbInitCommand.php'
        ]);
        $exclude_filePaths = array_map(function ($dir) {
            // 将目录分割符号\,/统一修改为 DIRECTORY_SEPARATOR
            return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $dir);
        }, $exclude_filePaths);

        // 需要混淆的目录
        $obfuscate_dirs =  Config::instance()->get('app.phar.obfuscate_dirs', []);
        // 修正路径
        $exclude_dirs = array_map(function ($dir) {
            // 将目录分割符号\,/统一修改为 DIRECTORY_SEPARATOR
            return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $dir);
        }, $exclude_dirs);
        $obfuscate_dirs = array_map(function ($dir) {
            // 将目录分割符号\,/统一修改为 DIRECTORY_SEPARATOR
            return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $dir);
        }, $obfuscate_dirs);

        // 遍历源码目录，处理并添加文件
        $output->spinBegiin();
        $directory = new RecursiveDirectoryIterator(ROOT_PATH, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::SELF_FIRST);
        /** @var RecursiveDirectoryIterator $iterator */
        foreach ($iterator as $item) {
            $relativePath = $iterator->getSubPathname();
            $fileName = $item->getFilename();
            $pathName = $item->getPathname();
            // 不处理目录
            if ($item->isDir()) {
                continue;
            }
            if (in_array($relativePath, $exclude_filePaths)) {
                continue;
            }
            // 判断是否在排除文件或目录中
            foreach ($exclude_dirs as $pattern) {
                if (strpos($relativePath, $pattern) === 0) {
                    continue 2;
                }
            }
            foreach ($exclude_files as $pattern) {
                if (fnmatch($pattern, $fileName)) {
                    continue 2;
                }
            }

            // 处理文件
            if ($item->getExtension() !== 'php') {
                // 非PHP文件，直接导入
                $phar->addFromString($relativePath, File::read($pathName));
            } else {
                // 混淆文件目录，混淆处理
                $isObfuscate = false;
                foreach ($obfuscate_dirs as $pattern) {
                    if (strpos($relativePath, $pattern) === 0) {
                        // 混淆处理
                        $code = File::read($pathName);
                        $new_code = $obfuscator->encode($code);
                        $phar->addFromString($relativePath, $new_code);
                        $isObfuscate = true;
                        $newPath = $dir . DIRECTORY_SEPARATOR . '/encode/' . $relativePath;
                        File::createFile($new_code, $newPath, false);
                        break;
                    }
                }
                // 直接导入
                if (!$isObfuscate) {
                    $phar->addFromString($relativePath, File::read($pathName));
                }
            }

            $output->spin();
        }
        $output->spinEnd();

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
        return $output->write("Save business code to the Phar archive, save to $phar_file");
    }
}
