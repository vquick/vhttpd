
[说明]

vhttpd 是一款用纯php开发的轻量级 web server软件，它除了支持常规 HTTP 1.1 的 GET/POST 外，还支持虚似主机vhost的设置，
同时还支持任意 cgi 的扩展，暂支持的 cgi 有: php 和 shell

提醒：该软件不可做为生产环境使用，可做为研究参考或特殊的后台管理用。


[软件协议]

本软件完全开源使用，使用个人可以二次修改后使用。


[系统要求]

1：必须是 Linux 类系统
2：必须要安装 PHP 版本 >= 5.3
3：必须要安装 PHP 扩展有：sockets,posix,pcntl


[安装步骤]

1：安装好 PHP ，例如安装在: /data/soft/php

2：将 vhttpd 整个目录复制到服务器，例如：/data/soft/vhttpd

3: 修改 vhttpd/vhttpd.sh 中的PHP和VHTTPD的路径定义:
------------ vi vhttpd.sh ---------------
PHP_CMD="/data/soft/php/bin/php"; # 安装的 php 可执行文件的绝对路径
VHTTPD_PATH="/data/soft/vhttpd";  # vhttpd 的安装根目录
---------------------------------------

4: 修改 vhttpd/conf/vhttpd.php 中
---------- vi conf/vhttpd.php -----------
'cli_bin'=>'/data/soft/php/bin/php'
-----------------------------------------

5: 执行以下命令启动服务
chmod a+x vhttpd.sh
./vhttpd.sh start

6: 检验是否启动成功,执行: netstat -nlpt | grep '8089' 有类似如下即代表成功:
vlinux:~ # netstat -nlpt | grep '8089'
tcp        0      0 0.0.0.0:8089            0.0.0.0:*               LISTEN      3518/php 

7: 测试,用浏览器打开对应URL
http://<服务器的ip>:8089

8: 其它详细配置请仔细阅读: conf/vhttpd.php


[关于软件]

作者：V哥
QQ群: 2995220
