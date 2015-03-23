<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-10-25 12:08:21
/*	Updated: UTC 2015-03-22 08:15:11
/*
/* ************************************************************************** */
namespace Loli\Storage;
class_exists('Loli\Storage\Base') || exit;
class SSH2 extends Base{

	private $_link;

	private $_sftpLink;

	private $_throw;

	protected $dir = '/';

	protected $chmod = 0644;

	protected $chmodDir = 0755;

	protected $host;

	protected $port = 22;

	protected $user = false;

	protected $pass = false;

	protected $timeout = 30;

	protected $methods = [];

	protected $publicKey = false;

	protected $privateKey = false;

	private function _link() {
		if ($this->_link) {
			return $this->_link;
		}
		if ($this->_link !== NULL) {
			throw $this->_throw;
		}
		if (!$this->_link = @ssh2_connect($this->host, $this->port, $this->publicKey || $this->privateKey ? $this->methods + ['hostkey' => 'ssh-rsa'] : $this->methods, ['disconnect' => [$this, 'disconnect']])) {
			throw $this->_throw = new ConnectException('Unable to connect to server', 10);
		}


		if ($this->publicKey || $this->privateKey) {
			if (!@ssh2_auth_pubkey_file($this->_link, $this->user, $this->publicKey, $this->privateKey, $this->pass)) {
				$this->_link = false;
				throw $this->_throw = new ConnectException('Unable to login to the server', 11);
			}
		} else {
			if (!@ssh2_auth_password($this->_link, $this->user, $this->pass)) {
				$this->_link = false;
				throw $this->_throw = new ConnectException('Unable to login to the server', 11);
			}
		}
		$this->_sftpLink = ssh2_sftp($this->_link);
		return $this->_link;
	}



	public function put($remote, $local) {
		if (!is_file($local)) {
			return false;
		}
		$resource = fopen($local, 'rb');
		$ret = $this->fput($remote, $resource);
		fclose($resource);
		return $ret;
	}

	public function get($remote) {
		if (!($path = $this->_path($remote)) || !$this->is_file($remote)) {
			return false;
		}
		return $path;
	}

	public function fput($remote, $resource) {
		if (!($path = $this->_path($remote)) || !is_resource($resource)) {
			return false;
		}
		$this->_mkdir(dirname($remote));

		// 写入文件
		if (!$fopen = fopen($path, 'wb')){
			return false;
		}
		$ftell = ftell($resource);
		fseek($resource, 0);
		while (!feof($resource)) {
		   fwrite($fopen, fread($resource, $this->buffer));
		}
		fseek($resource, $ftell);
		fclose($fopen);
		@chmod($path, $this->chmod);
		return true;
	}

	public function fget($remote) {
		if (!($path = $this->_path($remote)) || !$this->is_file($remote)) {
			return false;
		}
		return fopen($path, 'rb');
	}





	public function cput($remote, $contents) {
		if (!($path = $this->_path($remote)) || $contents === NULL) {
			return false;
		}
		$this->_mkdir(dirname($remote));
		$ret = @file_put_contents($path, $contents);
		if ($ret !== strlen($contents)) {
			return false;
		}
		@chmod($path, $this->chmod);
		return true;
	}

	public function cget($remote) {
		if (!($path = $this->_path($remote)) || !$this->is_file($remote)) {
			return false;
		}
		return @file_get_contents($path);
	}

	public function rename($source, $destination) {
		if (!($path1 = $this->_path($source)) || !($path2 = $this->_path($destination))) {
			return false;
		}
		$this->_mkdir(dirname($destination));
		return @rename($path1, $path2);
	}

	public function unlink($remote) {
		if (!($path = $this->_path($remote)) || !$this->is_file($remote)) {
			return false;
		}
		return unlink($path);
	}

	public function unlinks($remote) {
		foreach ($remote as $v) {
			 $this->unlink($v);
		}
		return true;
	}

	public function size($remote) {
		if (!($path = $this->_path($remote)) || !$this->is_file($remote)) {
			return false;
		}
		return filesize($path);
	}

	public function exists($remote) {
		if (!$path = $this->_path($remote)) {
			return false;
		}
		return is_file($path);
	}


	public function disconnect() {
		$this->_sftpLink = $this->_link = NULL;
	}


	private function _mkdir($dir) {
		if (!$dir || !($dir = $this->_path($dir))) {
			return false;
		}
		is_dir($dir) || @mkdir($dir, $this->chmodDir, true);
	}


	private function _path($path) {
		if (!($path = $this->filter($path)) || !$this->_link()) {
			return false;
		}
		return 'ssh2.sftp://' . $this->_sftpLink . '/' . $this->dir . '/' . $path;
	}
}