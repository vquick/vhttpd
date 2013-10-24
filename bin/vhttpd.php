<?php
/**
 * VHTTPD 服务系统
 * 
 * 运行要求：
 * Linux2.6 + PHP>=5.3 + Cli + Socket + Pcntl
 * 
 * ps -el 
 * 其中，有标记为Z的进程就是僵尸进程 
 * S代表休眠状态；D代表不可中断的休眠状态；R代表运行状态；Z代表僵死状态；T代表停止或跟踪状态
 * 
 * @version 1.0
 * @author V哥
 */

// 系统版本号
define('VHTTPD_VERSION', '1.0');

// 系统根目录
define('VHTTPD_ROOT', realpath(dirname(__FILE__).'/../'));

// 引入配置文件
$conf = include(VHTTPD_ROOT.'/conf/vhttpd.php');

// 设置不超时
set_time_limit(0);

// 设置php错误
$error = $conf['debug'] ? E_ALL : E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR;
error_reporting($error);

// 将打开绝对（隐式）刷送
ob_implicit_flush(); 

// 必要要设置 declare 机制，因为 pcntl_signal 是基于它实现的
declare(ticks = 1);

// 设置时区
date_default_timezone_set($conf['timezone']);

// 引入系统类
require VHTTPD_ROOT.'/bin/lib/sys.php';

// HTTP 协议解析类
require VHTTPD_ROOT.'/bin/lib/http.php';

// SOCKET 套接字操作
require VHTTPD_ROOT.'/bin/lib/socket.php';

// 服务类
require VHTTPD_ROOT.'/bin/lib/httpd.php';

// 载入配置文件
vhttpd_sys::loadConf($conf);

// 检查系统运行的必备条件,不成立则直接退出
vhttpd_sys::checkSystem();

// 启动守护进程网关
vhttpd_httpd::run();
