<?php
/**
 * VHTTPD 服务类
 * 
 * @author V哥
 */
class vhttpd_httpd
{
	/**
	 * 当前服务的配置
	 *
	 * @var unknown_type
	 */
	static private $_conf = null;
	
	/**
	 * 子进程的PID
	 *
	 */
	static private $_childPidArr = array();
	
	/**
	 * 当前服务是否要终止(服务接收到了要终止的信号)
	 *
	 * @var unknown_type
	 */
	static private $_serverIsStop = false;
	
	/**
	 * 当前服务端socket连接对象
	 *
	 * @var socket fd
	 */
	static private $_connect = null;
	
	/**
	 * 运行服务
	 * 
	 * @return void
	 */
	static public function run()
	{
		// 得到配置
		self::$_conf = vhttpd_sys::getConf();
		
		// 载入 cgi 模块
		self::_loadModule();
		
		// 将服务转为守护进程模式
		$pid = vhttpd_sys::becomeDaemon();
				
		// 记录主进程PID，用于停止服务(因为它会生成很多的子进程)
		file_put_contents(VHTTPD_ROOT.'/logs/vhttpd.pid', $pid);
				
		// 安装进程管理器
		self::_processManage();
		
		// 连接起监听
		self::$_connect = new vhttpd_socket();
		self::$_connect->init(self::$_conf['bind_host'], self::$_conf['bind_port']);
		
		// 先预先启动多个进程用于处理监听
		for($i=1; $i<=self::$_conf['server_num']; ++$i){
			self::$_childPidArr[] = self::_fork();
		}
		
		// 父进程进入监工管理员状态,每秒检测一下子进程的完整性
		while(true)
		{
			sleep(1);		
			// 如果当前是正常服务中(没有收到终止信号)
			if(! self::$_serverIsStop){
				// 如果有子进程被意外终止了则重新 fork 出子进程
				$num = self::$_conf['server_num'] - count(self::$_childPidArr);
				for($i=1; $i<=$num; ++$i){
					self::$_childPidArr[] = self::_fork();
				}				
			}
		}
		
		// 如果异常则终止整个服务(一般不会执行到这里来)
		exit(0);		
	}
	
	/**
	 * 装入 cgi 模块
	 *
	 */
	static public function _loadModule()
	{
		// 载入扩展模块
		foreach (self::$_conf['cgi_module'] as $modName=>$modConf){
			$file = VHTTPD_ROOT.'/bin/modules/'.$modName.'.php';
			if(!file_exists($file)){
				vhttpd_sys::halt($file.' not found');
			}
			require $file;
		}
	}
	
	/**
	 * fork() 子进程处理 socket
	 * 
	 * @return  int PID 子进程PID
	 */
	static private function _fork()
	{
		// fork()后子进程返回 0,父进程则返回子进程的 pid
		$pid = pcntl_fork();
		if($pid == 0){			
			// 子进程开始监听连接
			self::$_connect->loop(array(__CLASS__,'server'));
			
			// 防止子进程影响到父进程的上下文（正常情况下不会执行到这里，防止异常）
			exit(0);
		}elseif ($pid == -1){
			// fork 子进程失败
			vhttpd_sys::halt('fork child process fail');
		}
		// 在父进程中继续返回
		return $pid;
	}
		
	/**
	 * 执行请求答应
	 * 
	 * @param string $recvData :请求的原始报文
	 * @param string $clientIp :客户端IP地址
	 * 
	 * @return string
	 */
	static public function server($recvData, $clientIp='')
	{
		// 记录日志
		if(self::$_conf['debug']){
			echo $recvData.PHP_EOL;
			file_put_contents(VHTTPD_ROOT.'/logs/rweaccess.log', PHP_EOL.$recvData.PHP_EOL, FILE_APPEND);
		}
		
		// 得到 http 的实例
		$http = vhttpd_http::getInstance();
		
		// 解析 HTTP 头
		$http->reset()->parse($recvData);
		
		// 得到执行后的主体内容
		$result = self::_execRequest($http);
		
		// 如果是特殊状态码
		if(in_array($result['status'], array(404,502))){
			$result['body'] = file_get_contents(VHTTPD_ROOT.'/page/'.$result['status'].'.html');
		}
		
		// 写日志
		self::_accessLog($clientIp, $http, $result);	
			
		// 解析请求对应的响应内容
		return $http->getResponse($result);
	}
	
	/**
	 * 记录访问日志
	 *
	 * @param string $ip 
	 * @param object $http
	 * @param array $result
	 */
	static private function _accessLog($ip, $http, $result)
	{
		// 日志文件名
		$logFile = self::$_conf['log_file'];
		
		// 是否是访问的虚似主机
		$header = $http->getHead();		
		if(isset(self::$_conf['vhosts'][$header['hostname']])){
			$vhostConf = self::$_conf['vhosts'][$header['hostname']];
			if(isset($vhostConf['log_file']) && $vhostConf['log_file']!=''){
				$logFile = $vhostConf['log_file'];
			}
		}
		
		// 如果没有定义日志文件
		if($logFile == ''){
			return;
		}
		
		// 得到日志格式
		// 127.0.0.1 - - [02/Oct/2013:15:30:56 +0800] "GET /announce.php?i606&compact=1 HTTP/1.0" 404 301
		$log = sprintf('%s - - [%s] "%s %s %s" %d %d', $ip, date('Y-m-d H:i:s'), $header['method'], 
		$header['url'], $header['ver'], $result['status'], strlen($result['body']));
		
		// 得到完整的日志文件名
		$search = array('%Y','%m','%d','%H','%i');
		$replace = array(date('Y'),date('m'),date('d'),date('H'),date('i'));
		$file = VHTTPD_ROOT.'/logs/'.str_replace($search, $replace, $logFile);
		
		// 写日志
		file_put_contents($file, $log.PHP_EOL, FILE_APPEND);
	}
	
	/**
	 * 执行整个 http 请求
	 *
	 */
	static private function _execRequest($http)
	{
		// 得到系统配置和解析后的请求头
		$conf = self::$_conf;
		$header = $http->getHead();
		
		// 如果是所配置的虚似主机则使用虚似主机所对应的根目录，否则使用默认的根目录
		if(isset($conf['vhosts'][$header['hostname']])){
			$webRoot = $conf['vhosts'][$header['hostname']]['document_root'];
		}else{
			$webRoot = $conf['document_root'] ? $conf['document_root'] : VHTTPD_ROOT.'/htdocs';
		}
		
		// 判断要请求的文件是否存在，不存在否则响应 404 页面
		// 处理访问目录的情况,比如：“http://a.com/path1”
		// 这里要注意：由于http的使用约定，访问目录如果没有以"/"结尾时要进行重定向以"/"结束，主要是为了兼容性。
		$requestFile = $webRoot.$header['path'];
		if(is_dir($requestFile)){
			// 如果不是以 "/" 结尾则直接重定向
			if(substr($requestFile,-1,1) != '/'){
				return array(
					'status'=>301,
					'message'=>'Moved Permanently',
					'body'=>'',
					'location'=>$header['path'].'/',
				);
			}
			// 遍历索引文件
			$indexLen = count($conf['index_file']);
			for($i=0; $i<$indexLen-1; ++$i){
				if(file_exists($requestFile.'/'.$conf['index_file'][$i])){
					break;
				}
			}
			$requestFile .= $conf['index_file'][$i];
		}
		// 如果是调试
		if($conf['debug']){
			vhttpd_sys::log(date('Y-m-d H:i:s').'=>'.$requestFile);
		}
		if(! file_exists($requestFile)){
			return array(
				'status'=>404,
				'message'=>'Not Found',
			);
		}
		
		// 如果是模块特殊的扩展名则调用模块的hook回调函数来处理
		$ext = pathinfo($requestFile, PATHINFO_EXTENSION);
		if(isset($conf['mod_exts'][$ext])){
			$modClassName = 'vhttpd_'.$conf['mod_exts'][$ext];
			return call_user_func_array(array($modClassName, 'callback'), array($requestFile,$conf));
		}else{
			// 如果是普通文件则直接读取
			return array(
				'status'=>200,
				'message'=>'OK',
				'ext'=>$ext,
				'body'=>file_get_contents($requestFile),
			);
		}
	}
	
	/**
	 * 进程管理,安装进程信号处理
	 *
	 */
	static private function _processManage()
	{
		/* 注册信号处理句柄 */
		$signalArr = array(
			SIGCHLD => 'SIGCHLD', // 子进程结束时
			SIGCLD	=> 'SIGCLD',  // 子进程结束时
			SIGINT  => 'SIGINT',  // 程序终断(Ctrl+C)
			SIGHUP  => 'SIGHUP',  // 终端关闭
			SIGQUIT => 'SIGQUIT', // 常是(Ctrl-\)来控制错误退出. 进程在因收到SIGQUIT退出时会产生core文件
			SIGTERM => 'SIGTERM', // 一般是 kill 命令时的终止信号
		);
		foreach ($signalArr as $signo=>$signame){
			if (! pcntl_signal($signo, array(__CLASS__, "_signalHandler"))){
				vhttpd_sys::halt("Bind Signal Handler for $signame failed");
			}
		}
	}
	
	/**
	 * 信号处理句柄
	 *
	 * @param unknown_type $signo
	 */
	static public function _signalHandler($signo)
	{
		/**
		 * 避免僵尸进程常用的有以下几种方法：
		 * 1：父进程通过wait和waitpid等函数等待子进程结束，但这会导致父进程挂起，不利于大并发的要求。
		 * 2：父进程可以用signal函数为SIGCHLD安装handler，因为子进程结束后， 父进程会收到该信号，可以在handler中调用wait回收。
		 * 3：父进程可以用signal(SIGCHLD, SIG_IGN)通知内核，自己对子进程的结束不感兴趣，那么子进程结束后，内核会回收， 
		 * 并不再给父进程发送信号。但这种方法只适合Linux系统，Unix系统中则一定要调用 wait()。
		 * 4：fork两次，父进程fork一个子进程，然后继续工作，子进程fork一 个孙进程后退出，那么孙进程被init接管，孙进程结束后，init会回收。
		 */
		switch(intval($signo))
		{
			case SIGCLD:
			case SIGCHLD:
			// 由于在并发状态下SIGCHLD信号到达服务器时，UNIX往往是不会排队，所以这里推荐采用 waitpid() 而不用 wait()
			// WNOHANG:即使没有子进程退出，也会立即返回
			// WUNTRACED:如果子进程进入暂停执行情况则马上返回,但结束状态不予以理会
			while( ($pid = pcntl_waitpid(-1,$status, WNOHANG|WUNTRACED)) > 0 ){
				if(self::$_conf['debug']){
					vhttpd_sys::log("child proccess {$pid} exited");
				}
				// 从保存的所有子进程PID的数组中去除,表示该了进程已经结束了
				if(in_array($pid, self::$_childPidArr)){
					unset(self::$_childPidArr[array_search($pid, self::$_childPidArr)]);
				}
			}
			break;
			case SIGINT:
			case SIGQUIT:		
			case SIGTERM: 
			case SIGHUP:
				// 安全退出
				self::_exitServer();
			break;
		}
	}

	/**
	 * 安全即出整个服务
	 *
	 */
	static private function _exitServer()
	{
		// 标记当前服务要被终止了(不用自动修复子进程了)
		self::$_serverIsStop = true;
		
		// 父进程终止前要先终止所有的子进程，防止产生僵尸进程
		foreach (self::$_childPidArr as $pid){
			vhttpd_sys::log("kill child proccess {$pid}");
			// SIGKILL:等同 kill -9 
			posix_kill($pid, SIGKILL);
		}
		
		// 关闭 socket 服务
		self::$_connect->close();		
		
		// 等待所有的子进程全结束后终止整个服务
		while(true)
		{
			usleep(500);
			if(count(self::$_childPidArr)){
				file_put_contents(VHTTPD_ROOT.'/logs/vhttpd.pid', '');
				exit(0);
			}
		}		
	}
}