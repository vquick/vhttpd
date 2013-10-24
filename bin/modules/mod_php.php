<?php
/**
 * PHP 模块
 *
 * @author V哥
 */
class vhttpd_mod_php
{
	/**
	 * 解析PHP请求文件
	 *
	 * @param string $requestFile :PHP请求文件,要绝对路径
	 * @param array $conf :所有配置项
	 * 
	 * @return array
	 */
	static public function callback($requestFile, $conf)
	{
		// 暂用到了模块自己的配置
		$conf = $conf['cgi_module']['mod_php'];
		// 在管道中执行PHP脚本
		$pipes = array();
		$descSpec = array(
			array('pipe', 'r'), // 标准输入(向php cli进程输入要执行的PHP脚本)
			array('pipe', 'w'), // 标准输出(php cli进程执行后的输出内容,即程序中所做的输出)
			array('pipe', 'w'), // 标准错误(php cli进程执行出错时的信息)
		);
		$handle = proc_open($conf['cli_bin'], $descSpec, $pipes, dirname($requestFile));
		
		// 如果执行管道创建失败，就返回 502 网关错误
		if(! is_resource($handle)){
			return array(
				'status'=>502,
				'message'=>'Bad Gateway',
			);
		}
		
		// 向输入管道写入要执行的脚本内容(相关于在命令行时指定文件名)
		fwrite($pipes[0], self::_exportGlobalVal().file_get_contents($requestFile));
		fclose($pipes[0]);
		
		// 得到输出管道的内容(执行后的结果)
		$body = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		
		// 得到错误管道的内容(执行PHP时的错误休息)
		$body .= stream_get_contents($pipes[2]);
		fclose($pipes[2]);
		
		// 关闭总管道
		proc_close($handle);
		
		// 返回数据
		return array(
			'status'=>200,
			'message'=>'OK',
			'body'=>$body,
		);
	}
	
	/**
	 * 返回全局变量的或执行定义语句，这里是模似超全局变量的值。
	 * 
	 * @return string
	 */
	static private function _exportGlobalVal()
	{
		$phpCode = '<?php ';
		$arr = array(
			'$_GET'=>$_GET,
			'$_POST'=>$_POST,
			'$_COOKIE'=>$_COOKIE,
		);
		foreach ($arr as $name=>$val){
			$phpCode .= $name.'='.var_export($val,true).';';
		}
		$phpCode = $phpCode.' ?>';
		// 换成一行的主要原因是如果有执行出错时可以更准确的定位到出错的行。
		return str_replace(PHP_EOL,'',$phpCode);
	}
}
