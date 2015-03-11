<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-05-12 18:03:40
/*	Updated: UTC 2015-02-26 05:48:43
/*
/* ************************************************************************** */
namespace Loli\Storage;
class_exists('Loli\Storage\Base') || exit;
class FTP extends Base{

	private $_link;

	private $_throw;

	protected $dir = '/';

	protected $chmod = 0644;

	protected $chmodDir = 0755;

	protected $host;

	protected $port = 21;

	protected $user = false;

	protected $pass = false;

	protected $pasv = false;

	protected $ssl = false;

	protected $timeout = 15;

	protected $mode = FTP_BINARY;


	private function _link() {
		if ($this->_link) {
			return $this->_link;
		}
		if ($this->_link !== NULL) {
			throw $this->_throw;
		}

		// 连接
		$this->_link = $this->ssl && function_exists('ftp_ssl_connect') ? @ftp_ssl_connect($this->host, $this->port, $this->timeout) : @ftp_connect($this->host, $this->port, $this->timeout);
		if (!$this->_link) {
			throw $this->_throw = new Exception('Unable to connect to server', 10);
		}

		// 登陆
		if ($this->user && $this->pass && !@ftp_login($this->_link, $this->user, $this->pass)) {
			$this->_close();
			throw $this->_throw = new Exception('Unable to login to the server', 11);
		}

		// 被动模式
		$this->pasv && ftp_pasv($this->_link, true);

		// 设置超时时间
		ftp_get_option($this->_link, FTP_TIMEOUT_SEC) < $this->timeout && ftp_set_option($this->_link, FTP_TIMEOUT_SEC, $this->timeout);

		return $this->_link;
	}


	public function put($remote, $local) {
		if (!is_file($local)){
			throw new Exception('Storage data does not exist', 5);
		}

		$file = $this->dir . $this->filter($remote);
		$this->_mkdir(dirname($file));
		if (!ftp_put($this->_link(), $file, $local, $this->mode)) {
			throw new Exception('Unable to write data', 6);
		}
		$this->_chmod($file, $this->chmod);
		return true;
	}

	public function get($remote) {
		if (!$this->exists($remote)) {
			throw new Exception('Storage data does not exist', 5);
		}
		return 'ftps://' . ($this->user || $this->pass ?  $this->user . ':' . $this->pass. '@' : '') . $this->host . ':' . $this->port . '/' .$this->dir . $this->filter($remote);
	}


	public function fput($remote, $resource) {
		if (!is_resource($resource)) {
			throw new Exception('Resource does not exist', 3);
		}

		$file = $this->dir . $this->filter($remote);
		$this->_mkdir(dirname($file));
		if (!ftp_fput($this->_link() ,$file,  $resource, $this->mode)){
			throw new Exception('Unable to write data', 6);
		}
		$this->_chmod($file, $this->chmod);
		return true;
	}


	public function fget($remote) {
		return fopen($this->get($remote), 'rb');
	}


	public function cput($remote, $contents) {
		if ($contents === NULL) {
			throw new Exception('Data storage is empty', 4);
		}
		$remote = $this->filter($remote);
		$resource = tmpfile();
		fwrite($resource, $contents);
		$this->fput($remote, $resource);
		fclose($resource);
		return true;
	}


	public function cget($remote) {
		$resource = $this->fget($remote);
		$contents = '';
		while (!feof($resource)) {
			$contents .= fread($file, $this->buffer);
		}
		fclose($resource);
		return $contents;
	}



	public function rename($source, $destination) {
		$source = $this->filter($source);
		$destination = $this->filter($destination);
		if (!$this->exists($source)) {
			throw new Exception('Storage data does not exist', 5);
		}
		$this->_mkdir(dirname($destination));
		if (!@ftp_rename($this->_link(), $this->dir . $source, $this->dir . $destination)){
			throw new Exception('Unable to rename', 7);
		}
		return true;
	}


	public function unlink($remote) {
		if (!$this->exists($source)) {
			throw new Exception('Storage data does not exist', 5);
		}
		if (!@ftp_delete($this->_link(), $this->dir . $this->filter($remote))) {
			throw new Exception('You can not delete', 7);
		}
		return true;
	}

	public function unlinks($remote) {
		foreach ($remote as $v) {
			 $this->unlink($v);
		}
		return true;
	}



	public function size($remote) {
		if (!$this->exists($remote) || ($size = @ftp_size($this->_link(), $this->dir . $remote)) == -1) {
			throw new Exception('Storage data does not exist', 5);
		}
		return $size < 0 ? false : $size;
	}

	public function exists($remote) {
		if (!ftp_nlist($this->_link(), $this->dir . $this->filter($remote))) {
			return false;
		}
		return !$this->_isDir($this->dir . $this->filter($remote));
	}



	private function _close() {
		$this->_link && @ftp_close($this->_link);
		$this->_link = NULL;
	}

	private function _isDir($dir) {
		$pwd = @ftp_pwd($this->_link());
		if (!$result = @ftp_chdir($this->_link(), $dir)) {
			return false;
		}
		@ftp_chdir($this->_link, $pwd);
		return true;
	}




	private function _mkdir($dir) {
		if (!$dir || $this->_isDir($dir)) {
			return;
		}
		$this->_mkdir(dirname($dir));
		if (!@ftp_mkdir($this->_link(), $dir)) {
			throw new Exception('Unable to create directory', 12);
		}
		$this->_chmod($dir, $this->chmodDir);
	}


	private function _chmod($path, $mode) {
		// 属性的文件或目录
		if (!function_exists('ftp_chmod')) {
			return @ftp_site($this->_link(), sprintf('CHMOD %o %s', $mode, $path));
		}
		return @ftp_chmod($this->_link(), $mode, $path);
	}

}