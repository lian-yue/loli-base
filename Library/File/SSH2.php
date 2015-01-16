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
/*	Updated: UTC 2015-01-16 08:03:47
/*
/* ************************************************************************** */
namespace Loli\File;
class_exists('Loli\File\Base') || exit;
class SSH2 extends Base{

	private $_link;

	private $_sftpLink;

	public $dir = '/';

	public $chmod = 0644;

	public $chmodDir = 0755;

	public $host;

	public $port = 22;

	public $user = false;

	public $pass = false;

	public $timeout = 30;

	public $methods = [];

	public $publicKey = false;

	public $privateKey = false;

	public function __construct($args) {
		foreach ($args as $k => $v) {
			if (in_array($k, ['dir', 'chmod', 'host', 'port', 'user', 'pass', 'timeout', 'methods', 'publicKey', 'privateKey'])) {
				$this->$k = $v;
			}
		}
	}

	private function link() {
		if ($this->_link !== null) {
			return $this->_link;
		}
		if (!$this->_link = @ssh2_connect($this->host, $this->port, $this->publicKey || $this->privateKey ? $this->methods + ['hostkey' => 'ssh-rsa'] : $this->methods, ['disconnect' => [$this, 'disconnect']])) {
			return false;
		}


		if ($this->publicKey || $this->privateKey) {
			if (!@ssh2_auth_pubkey_file($this->_link, $this->user, $this->publicKey, $this->privateKey, $this->pass)) {
				return $this->_link = false;
			}
		} else {
			if (!@ssh2_auth_password($this->_link, $this->user, $this->pass)) {
				return $this->_link = false;
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
		   fwrite($fopen, fread($resource, 1024* 1024*10));
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
		if (!($path = $this->_path($remote)) || $contents === null) {
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
		$this->_sftpLink = $this->_link = null;
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