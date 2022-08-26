<?php

/*
|--------------------------------------------------------------------------
| 错误信息处理
|--------------------------------------------------------------------------
| 这里定义PHP错误处理，开启所有错误信息
|
*/

ini_set('display_errors', 'on');
error_reporting(E_ALL);


/*
|--------------------------------------------------------------------------
| 定义应用根路径
|--------------------------------------------------------------------------
| 这里定义根路径, 用于路径查找
|
*/
define('ROOT_PATH', dirname(__DIR__));


/*
|--------------------------------------------------------------------------
| 定义应用APP路径
|--------------------------------------------------------------------------
| 这里定义APP路径, 用于路径查找
|
*/
define('APP_PATH', ROOT_PATH . '/app');


/*
|--------------------------------------------------------------------------
| 定义配置文件路径
|--------------------------------------------------------------------------
| 这里定义配置文件路径, 用于路径查找获取配置信息
|
*/
define('CONFIG_PATH', ROOT_PATH . '/config');


/*
|--------------------------------------------------------------------------
| 运行时目录路径
|--------------------------------------------------------------------------
| 这里定义运行时路径, 用于存储系统运行时相关资源内容，目录需拥有读写权限
|
*/
define('RUNTIME_PATH', ROOT_PATH . '/runtime');

