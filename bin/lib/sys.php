<?php
/**
 * VHTTPD 系统类
 * 
 * @author V哥
 */
class vhttpd_sys
{
	/**
	 * 系统配置
	 *
	 */
	static private $_conf = null;
	

	/**
	 * 载入配置
	 *
	 * @param array $conf
	 * @return void
	 */
	static public function loadConf($conf)
	{
		// 出于性能，先格式化一下 vhosts 的配置
		$vhosts = array();
		foreach ($conf['vhosts'] as $vhost){
			$vhosts[$vhost['server_name']] = $vhost; 
		}
		$conf['vhosts'] = $vhosts;
		
		// 出于性能，先格式化一下模块的配置
		$conf['mod_exts'] = array();
		foreach ($conf['cgi_module'] as $modName=>$modConf){
			foreach ($modConf['exts'] as $ext){
				$conf['mod_exts'][$ext] = $modName;
			}
		}
		
		// 保存
		self::$_conf = $conf;
	}
	
	/**
	 * 得到系统配置
	 *
	 * @return array
	 */
	static public function getConf()
	{
		return self::$_conf;
	}
	
	/**
	 * 检测系统，如果不符和运行条件将会终止程序
	 *
	 * @return void
	 */
	static public function checkSystem()
	{
		// PHP必需是 >=5.3
		if(!version_compare(PHP_VERSION, '5.3','>=')){
			self::halt('PHP Version mast >=5.3');
		}
		// 必要安装的扩展
		$exts = array('sockets','posix','pcntl');
		foreach ($exts as $name){
			if(!extension_loaded($name)){
				self::halt(sprintf('PHP Extensions %s not find', strtoupper($name)));
			}
		}
	}
	
	/**
	 * 将当前进程设置为守护进程模式
	 *
	 * @return int
	 */
	static public function becomeDaemon()
	{
		// 如果是调度模式
		if(self::$_conf['debug']){
			return posix_getpid();
		}		
		// 生成子进程
		$pid = pcntl_fork();
		if($pid == -1){
			// fork 子进程失败
			self::halt('become daemon failure');
			exit(1); 
		}elseif($pid > 0){ 
			// 结束父进程，使子进程成为新会话的进程组首进程
			exit(0); 
		}else{ 
			// 子进程成为会话的进程组长
			posix_setsid(); 
			chdir('/'); 
			umask(0); 
			return posix_getpid();
		}	
	}
		

	/**
	 * 终止程序，并记录日志
	 *
	 * @param string $error
	 */
	static public function halt($error)
	{
		self::log($error,1);
		exit(1);
	}
	
	/**
	 * 写日志
	 *
	 * @param string $log
	 * @param int $type 日志类型 0:运行日志 1:错误日志
	 */
	static public function log($log, $type=0)
	{
		$logFileMap = array(
			0=>'run.log',
			1=>'error.log',
		);
		// 如果是调度模式
		if(self::$_conf['debug']){
			echo $logFileMap[$type].'=>'.$log.PHP_EOL;
		}
		file_put_contents(VHTTPD_ROOT.'/logs/'.$logFileMap[$type], $log.PHP_EOL, FILE_APPEND);
	}
}