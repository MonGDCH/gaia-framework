<?php

declare(strict_types=1);

namespace gaia\command;

use Phar;
use ZipArchive;
use mon\console\Input;
use mon\console\Output;
use mon\console\Command;

/**
 * 生成二进制文件
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class BuildBinCommand extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static $defaultName = 'build:bin';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Build binary file a project';

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
            return $output->error("The 'phar.readonly' is 'On', build phar must setting it 'Off' or exec with 'php -d phar.readonly=0 ./gaia phar:bin'");
        }
        $args = $input->getArgs();
        $version = floatval($args[0] ?? PHP_VERSION);
        $version = max($version, 8.1);
        $supportZip = class_exists(ZipArchive::class);
        $microZipFileName = $supportZip ? "php$version.micro.sfx.zip" : "php$version.micro.sfx";
        $pharFileName = config('app.phar.phar_name', 'gaia.phar');
        $binFileName = config('app.phar.bin_name', 'gaia.bin');
        $buildDir = config('app.phar.build_path', ROOT_PATH . DIRECTORY_SEPARATOR . 'build');
        $customIni = config('app.phar.custom_ini', []);

        $binFile = "$buildDir/$binFileName";
        $pharFile = "$buildDir/$pharFileName";
        $zipFile = "$buildDir/$microZipFileName";
        $sfxFile = "$buildDir/php$version.micro.sfx";
        $customIniHeaderFile = "$buildDir/custominiheader.bin";

        // 打包
        $command = new BuildPharCommand();
        $command->execute($input, $output);

        // 下载 micro.sfx.zip
        if (!is_file($sfxFile) && !is_file($zipFile)) {
            $domain = 'download.workerman.net';
            $output->write("\r\nDownloading PHP$version ...");
            if (extension_loaded('openssl')) {
                $context = stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ]
                ]);
                $client = stream_socket_client("ssl://$domain:443", $context);
            } else {
                $client = stream_socket_client("tcp://$domain:80");
            }

            fwrite($client, "GET /php/$microZipFileName HTTP/1.1\r\nAccept: text/html\r\nHost: $domain\r\nUser-Agent: gaia/console\r\n\r\n");
            $bodyLength = 0;
            $bodyBuffer = '';
            $lastPercent = 0;
            while (true) {
                $buffer = fread($client, 65535);
                if ($buffer !== false) {
                    $bodyBuffer .= $buffer;
                    if (!$bodyLength && $pos = strpos($bodyBuffer, "\r\n\r\n")) {
                        if (!preg_match('/Content-Length: (\d+)\r\n/', $bodyBuffer, $match)) {
                            return $output->error("Download php$version.micro.sfx.zip failed");
                        }
                        $firstLine = substr($bodyBuffer, 9, strpos($bodyBuffer, "\r\n") - 9);
                        if (!preg_match('/200 /', $bodyBuffer)) {
                            return $output->error("Download php$version.micro.sfx.zip failed, $firstLine");
                        }
                        $bodyLength = (int)$match[1];
                        $bodyBuffer = substr($bodyBuffer, $pos + 4);
                    }
                }
                $receiveLength = strlen($bodyBuffer);
                $percent = ceil($receiveLength * 100 / $bodyLength);
                if ($percent != $lastPercent) {
                    echo '[' . str_pad('', (int)$percent, '=') . '>' . str_pad('', 100 - (int)$percent) . "$percent%]";
                    echo $percent < 100 ? "\r" : "\n";
                }
                $lastPercent = $percent;
                if ($bodyLength && $receiveLength >= $bodyLength) {
                    file_put_contents($zipFile, $bodyBuffer);
                    break;
                }
                if ($buffer === false || !is_resource($client) || feof($client)) {
                    return $output->error("Fail donwload PHP$version ...");
                }
            }
        } else {
            $output->write("\r\nUse PHP$version ...");
        }

        // 解压
        if (!is_file($sfxFile) && $supportZip) {
            $zip = new ZipArchive;
            $zip->open($zipFile, ZipArchive::CHECKCONS);
            $zip->extractTo($buildDir);
        }

        // 生成二进制文件
        file_put_contents($binFile, file_get_contents($sfxFile));
        // 自定义INI
        if (!empty($customIni)) {
            if (file_exists($customIniHeaderFile)) {
                unlink($customIniHeaderFile);
            }
            $f = fopen($customIniHeaderFile, 'wb');
            fwrite($f, "\xfd\xf6\x69\xe6");
            fwrite($f, pack('N', strlen($customIni)));
            fwrite($f, $customIni);
            fclose($f);
            file_put_contents($binFile, file_get_contents($customIniHeaderFile), FILE_APPEND);
            unlink($customIniHeaderFile);
        }
        file_put_contents($binFile, file_get_contents($pharFile), FILE_APPEND);
        // 添加执行权限
        chmod($binFile, 0755);

        return $output->block("\r\nSaved $binFileName to $binFile\r\nBuild Success!\r\n", 'success');
    }
}
