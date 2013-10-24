#!/bin/bash
#-------------------------------------------
# PHP HTTP WEB SERVER
#
# @version 1.0
# @author V哥
#-------------------------------------------
PHP_CMD="/data/soft/php/bin/php";
VHTTPD_PATH="/data/soft/vhttpd";

function start_server(){
	cd $VHTTPD_PATH
	$PHP_CMD ./bin/vhttpd.php >/dev/null 2>&1
}
function stop_server(){
	cd $VHTTPD_PATH
	kill `cat ./logs/vhttpd.pid` >/dev/null 2>&1
}
function restart_server(){
	stop_server;
	start_server;
}
# 检测参数
case $1 in
	"start")
		start_server;
		;;
	"stop")
		stop_server;
		;;
	"restart")
		restart_server;
		;;
	*)
		echo $0 "(start|stop|restart)";
		;;
esac
