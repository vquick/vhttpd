<?php
/**
 * 共享内存结合信号灯的锁机制操作
 *
 * @author V哥
 */
class vhttpd_ipc
{
	/**
	 * 共享内存句柄
	 *
	 * @var unknown_type
	 */
	private $_shmid = null;
	
	/**
	 * 信号灯句柄
	 *
	 * @var unknown_type
	 */
	private $_semid = null;
	
	/**
	 * 构造函数
	 *
	 * @param unknown_type $memSize 申请共享内存的大小,单位：字节
	 */
	public function __construct($memSize)
	{
		// 先删除系统之前可能由于进程非正常退出时所打开的 ipc
		$shmkey = ftok(__FILE__, 't');
		system('ipcrm -M 0x'.dechex($shmkey).' >/dev/null 2>&1', $retval);
		$semKey = ftok(__FILE__, 'm');
		system('ipcrm -S 0x'.dechex($semKey).' >/dev/null 2>&1', $retval);
		
		// 再申请共享内存和信号灯
		if(($this->_shmid = shmop_open($shmkey, 'c', 0777, $memSize)) === false){
			vhttpd_sys::halt('Create or open shared memory fail');
		}
		if(($this->_semid = sem_get($semKey)) ===  false){
			vhttpd_sys::halt('Get a semaphore id fail');
		}
	}
	
	/**
	 * 以数组方式读出共享内存
	 *
	 * @return array
	 */
	public function read()
	{
		$data = shmop_read($this->_shmid, 0, shmop_size($this->_shmid));
		if($data){
			return json_decode(trim($data),true);
		}
		return false;
	}

	/**
	 * 以数组格式写共享内存
	 *
	 * @param array $arr
	 * @return boolean
	 */
	public function write($arr=array())
	{
		// 采用信号灯上锁,如果没有得到资源时，进程将挂起阻塞
		if(sem_acquire($this->_semid)){
			$ret = shmop_write($this->_shmid, json_encode($arr), 0);
			// 写完后解锁
			sem_release($this->_semid);
			return $ret;
		}
		return false;
	}
	
	/**
	 * 关闭共享内存和信号灯
	 *
	 */
	public function close()
	{
		// 关闭程序前一定要先关闭共享内存，否则会造成在相同key的情况无法再次申请的错误
		shmop_delete($this->_shmid); 
		shmop_close($this->_shmid);
		// 删除锁
		sem_remove($this->_semid);
	}
}
/*
	/**
	 * 当前父进程的ID
	 *
	 * @param int $parentPid
	 */
	static private function _getMemSize($parentPid)
	{
		// 根据当前父进程的PID计算出申请的共享内存大小
		$proNum = self::$_conf['server_num'];
		$pidLen = strlen((string)$parentPid) + 2;
		return $proNum*$pidLen + $proNum + 2;
	}
*/