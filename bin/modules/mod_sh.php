<?php
/**
 * linux shell 模块
 *
 * @author V哥
 */
class vhttpd_mod_sh
{
	/**
	 * 执行 sh 请求文件
	 *
	 * @param string $requestFile :sh请求文件,要绝对路径
	 * @param array $conf :所有配置项
	 * 
	 * @return array
	 */
	static public function callback($requestFile, $conf)
	{
		// sh 解析器
		$shBin = '/bin/bash';
		// 在管道中执行PHP脚本
		$pipes = array();
		$descSpec = array(
			array('pipe', 'r'), // 标准输入(向 $shBin 进程输入要执行的PHP脚本)
			array('pipe', 'w'), // 标准输出($shBin 进程执行后的输出内容,即程序中所做的输出)
			array('pipe', 'w'), // 标准错误($shBin 进程执行出错时的信息)
		);
		$handle = proc_open($shBin, $descSpec, $pipes, dirname($requestFile));
		
		// 如果执行管道创建失败，就返回 502 网关错误
		if(! is_resource($handle)){
			return array(
				'status'=>502,
				'message'=>'Bad Gateway',
			);
		}
		
		// 向输入管道写入要执行的脚本内容(相关于在命令行时指定文件名)
		fwrite($pipes[0], file_get_contents($requestFile));
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
}
