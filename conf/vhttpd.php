<?php
/**
 * vhttpd 系统配置
 *
 * @version 1.0
 * @author V哥
 */
return array
(
	// 运行模式 1:调试模式(开发时用到)  0:正常运行模式
	'debug'=>false,
	
	// 当前时区(一般情况下默认即可)
	'timezone'=>'Asia/Shanghai',

	// 套接字绑定的IP，（允许连接网关的IP）
	// "0.0.0.0":表示允许所有IP连接 '127.0.0.1':表示只允许本地连接 '192.168.1.78':表示指定的IP才可以连接
	'bind_host'=>'0.0.0.0',

	// 网关端口
	'bind_port'=>8089,

	// 启动客户端的进程数(建议为cpu核心数,可使用命令 "grep vendor_id /proc/cpuinfo | wc -l" 查看)
	'server_num'=>4,

	// 支持的静态文件类型(根据扩展名)
	'mime_types'=>array
	(
		'htm'=>'text/html',
		'html'=>'text/html',
		'js'=>'application/x-javascript',
		'ico'=>'image/x-icon',
		'gif'=>'image/gif',
		'bmp'=>'image/bmp',
		'jpg'=>'image/jpeg',
		'jpeg'=>'image/jpeg',
		'png'=>'image/png',
		'swf'=>'application/x-shockwave-flash',
		'css'=>'text/css',
		// 凡是没有定义的扩展名的文件都默认采用以下下载的方案打开
		'other'=>'application/octet-stream',
	),

	// CGI 模块以及模块对应的配置（只要有定义系统就会自动载入模块）
	'cgi_module'=>array
	(
		// PHP脚本解析模块
		'mod_php'=>array
		(
			// [该字段必须定义]: PHP 脚本的扩展名,支持任意扩展名，可以设置为多种.
			'exts'=>array('php'),
			// php cli 命令行的执行文件，要用绝对路径，当请求是PHP时将会调用它来解析
			'cli_bin'=>'/data/soft/php/bin/php',		
		),
		// shell脚本解析模块
		'mod_sh'=>array
		(
			// [该字段必须定义]: shell 脚本的扩展名,支持任意扩展名，可以设置为多种。
			'exts'=>array('sh'),		
		),		
	),
	
	// 默认访问文档的根目录,为空则默认是安装目录下的 ./htdocs/ (以非虚似主机的方式访问时将会自动请求该目录下的文件)
	'document_root'=>'',

	// 默认执行的索引文件，以定义的先后顺序为准
	'index_file'=>array('index.html','index.php'),
	
	// 访问日志文件 (日志文件的根目录在 ./logs )
	// 日志文件支持的格式: %Y:年 %m:月 %d:日 %H:小时 %i:分
	// 示例: 'access_%Y_%m_%d.log' 或 'access.log'
	// 
	// [注意]
	// 1:如果为空则不记录日志,例如: 'log_file'=>''
	// 2:虚拟主机配置中的日志配置将会覆盖这个配置
	'log_file'=>'access_%Y_%m_%d.log',

	// 虚拟主机配置
	'vhosts'=>array
	(
		// 虚似主机 test.vhttpd.com 的配置
		array(
			// 域名
			'server_name'=>'test.vhttpd.com',
			// 文档根目录
			'document_root'=>'/data/soft/vhttpd/vhosts/test_vhttpd_com',
			// 日志文件
			'log_file'=>'vhttpd.access_%Y_%m_%d.log',
		),
	),
);
