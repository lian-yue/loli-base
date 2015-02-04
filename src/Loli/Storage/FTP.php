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
/*	Updated: UTC 2015-02-03 09:19:52
/*
/* ************************************************************************** */
namespace Loli\Storage;
class_exists('Loli\Storage\Base') || exit;
class FTP extends Base{

	private $_link;

	public $dir = '/';

	public $chmod = 0644;

	public $chmodDir = 0755;

	public $host;

	public $port = 21;

	public $user = false;

	public $pass = false;

	public $pasv = false;

	public $ssl = false;

	public $timeout = 15;

	public $mode = FTP_BINARY;


	public function __construct($args) {
		foreach ($args as $k => $v) {
			if ($v !== null && in_array($k, ['dir', 'chmod', 'chmodDir', 'host', 'port', 'user', 'pass', 'ssl', 'mode', 'pasv', 'timeout'])) {
				$this->$k = $v;
			}
		}
	}

	private function _link() {
		if ($this->_link !== null) {
			return $this->_link;
		}

		// 连接
		$this->_link = $this->ssl && function_exists('ftp_ssl_connect') ? @ftp_ssl_connect($this->host, $this->port, $this->timeout) : @ftp_connect($this->host, $this->port, $this->timeout);
		if (!$this->_link) {
			return $this->_link = false;
		}

		// 登陆
		if ($this->user && $this->pass && !@ftp_login($this->_link, $this->user, $this->pass)) {
			$this->close();
			return $this->_link = false;
		}

		// 被动模式
		$this->pasv && ftp_pasv($this->_link, true);

		// 设置超时时间
		ftp_get_option($this->_link, FTP_TIMEOUT_SEC) < $this->timeout && ftp_set_option($this->_link, FTP_TIMEOUT_SEC, $this->timeout);

		return $this->_link;
	}


	public function put($remote, $local) {
		if (!($remote = $this->filter($remote)) || !is_file($local) || !$this->_link()) {
			return false;
		}
		$file = $this->dir . '/' . $remote;
		$this->_mkdir(dirname($file));
		if (!ftp_put($this->_link(), $file, $local, $this->mode)) {
			return false;
		}
		$this->_chmod($file, $this->chmod);
		return true;
	}

	public function get($remote) {
		if (!($remote = $this->filter($remote)) || !$this->exists($remote)) {
			return false;
		}
		return 'ftps://' . ($this->user || $this->pass ?  $this->user . ':' . $this->pass. '@' : '') . $this->host . ':' . $this->port . '/' .$this->dir . '/' . $remote;
	}


	public function fput($remote, $resource) {
		if (!$this->_link() || !($remote = $this->filter($remote)) || !is_resource($resource)) {
			return false;
		}
		$file = $this->dir . '/' . $remote;
		$this->_mkdir(dirname($file));
		if (!ftp_fput($this->_link() ,$file,  $resource, $this->mode)){
			return false;
		}
		$this->_chmod($file, $this->chmod);
		return true;
	}


	public function fget($remote) {
		if (!$path = $this->get($remote)) {
			return false;
		}
		return fopen($path, 'rb');
	}


	public function cput($remote, $contents) {
		if (!($remote = $this->filter($remote)) || $contents === null) {
			return false;
		}
		$resource = tmpfile();
		fwrite($resource, $contents);
		fclose($fopen);
		if (!$this->fput($remote, $resource)) {
			fclose($resource);
			return false;
		}
		fclose($resource);
		return true;
	}


	public function cget($remote) {
		if (!$resource = $this->fget($remote)) {
			return false;
		}
		$contents = '';
		while (!feof($resource)) {
			$contents .= fread($file, 1024 * 1024);
		}
		fclose($resource);
		return $contents;
	}



	public function rename($source, $destination) {
		if (!($source = $this->filter($source)) || !($destination = $this->filter($destination)) || !$this->exists($source)) {
			return false;
		}
		$this->_mkdir(dirname($destination));
		return @ftp_rename($this->_link(), $this->dir . '/' .$source, $this->dir . '/' .$destination);
	}


	public function unlink($remote) {
		return ($remote = $this->filter($remote)) && $this->exists($remote) && ftp_delete($this->_link(), $this->dir . '/' . $remote);
	}

	public function unlinks($remote) {
		foreach ($remote as $v) {
			 $this->unlink($v);
		}
		return true;
	}



	public function size($remote) {
		if (!($remote = $this->filter($remote)) || !$this->exists($remote) || ($size = @ftp_size($this->_link(), $this->dir . '/' . $remote)) == -1) {
			return false;
		}
		return $size < 0 ? false : $size;
	}

	public function exists($remote) {
		if (!($remote = $this->filter($remote)) || !$this->_link() || !ftp_nlist($this->_link(), $this->dir . '/' . $remote)) {
			return false;
		}
		return !$this->_isDir($this->dir . '/' . $remote);
	}



	public function close() {
		$this->_link && ftp_close($this->_link);
		$this->_link = null;
	}

	private function _isDir($dir) {
		if (!$this->_link()) {
			return false;
		}
		$pwd = @ftp_pwd($this->_link());
		if (!$result = @ftp_chdir($this->_link(), $dir)) {
			return false;
		}
		@ftp_chdir($this->_link, $pwd);
		return true;
	}




	private function _mkdir($dir) {
		if (!$dir || !$this->_link() || $this->_isDir($dir)) {
			return false;
		}
		$this->_mkdir(dirname($dir));
		if (!@ftp_mkdir($this->_link(), $dir)) {
			return false;
		}
		$this->_chmod($dir, $this->chmodDir);
		return true;
	}


	private function _chmod($path, $mode) {
		if (!$this->_link()) {
			return false;
		}

		// 属性的文件或目录
		if (!function_exists('ftp_chmod')) {
			return @ftp_site($this->_link(), sprintf('CHMOD %o %s', $mode, $path));
		}
		return @ftp_chmod($this->_link(), $mode, $path);
	}

}