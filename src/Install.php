<?php

declare(strict_types=1);

namespace gaia;

use mon\util\File;

/**
 * Gaia框架安装驱动
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Install
{
    /**
     * 标志为Gaia的驱动
     */
    const GAIA_PLUGIN = true;

    /**
     * 移动的文件
     *
     * @var array
     */
    protected static $file_relation = [
        'gaia' => 'gaia'
    ];

    /**
     * 安装
     *
     * @return void
     */
    public static function install()
    {
        // 创建框架文件
        $source_path = __DIR__ . DIRECTORY_SEPARATOR;
        $desc_path = ROOT_PATH . DIRECTORY_SEPARATOR;
        foreach (static::$file_relation as $source => $desc) {
            $sourceFile = $source_path . $source;
            $descFile = $desc_path . $desc;
            File::instance()->copyFile($sourceFile, $descFile, true);
            echo "Create File $descFile\r\n";
        }
    }

    /**
     * 卸载
     *
     * @return void
     */
    public static function uninstall()
    {
    }
}
