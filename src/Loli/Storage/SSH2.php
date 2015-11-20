<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-21 13:42:16
/*
/* ************************************************************************** */
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

	protected $dir = '/';

	protected $chmod = 0644;

	protected $chmodDir = 0755;

	protected $hostname = '127.0.0.1';

	protected $username = false;

	protected $password = false;

	protected $timeout = 30;

	protected $methods = [];

	protected $publicKey = false;

	protected $privateKey = false;

	private $_link;

	private $_sftpLink;

	public function __destruct() {
		unset($this->_context);
	}

	private function _base() {
		if ($this->_link === NULL) {
			print_r($this);
			$hostname = explode(':', $this->hostname, 2) + [1 => 22];
			if (!$this->_link = @ssh2_connect($hostname[0], $hostname[1], $this->publicKey || $this->privateKey ? $this->methods + ['hostkey' => 'ssh-rsa'] : $this->methods, ['disconnect' => [$this, 'disconnect']])) {
				throw new ConnectException('Unable to connect to server', 10);
				return false;
			}

			if ($this->publicKey || $this->privateKey) {
				if (!@ssh2_auth_pubkey_file($this->_link, $this->username, $this->publicKey, $this->privateKey, $this->password)) {
					throw new ConnectException('Unable to login to the server', 11);
					$this->_link = false;
				}
			} else {
				if (!@ssh2_auth_password($this->_link, $this->username, $this->password)) {
					throw new ConnectException('Unable to login to the server', 11);
					$this->_link = false;
				}
			}
		}

		if (!$this->_link) {
			return false;
		}

		if ($this->_sftpLink === NULL) {
			if (!$this->_sftpLink = @ssh2_sftp($this->_link)) {
				throw new ConnectException('ssh2_sftp', 11);
			}
		}

		if (!$this->_sftpLink) {
			return false;
		}

		return 'ssh2.sftp://' . $this->_sftpLink . ($this->dir && $this->dir !== '/' ? '/' . trim($this->dir, '/') : '');
	}


	public function disconnect() {
		$this->_sftpLink = $this->_link = NULL;
	}



	public function dir_closedir() {
		$result = $this->_context && closedir($this->_context);
		$this->_context = NULL;
		return $result;
	}

	public function dir_opendir($path, $options) {
		$this->_context = @opendir($this->_base() . $this->path($path));
		return !empty($this->_context);
	}

	public function dir_readdir() {
		if (!$this->_context) {
			return false;
		}
		while(in_array($result = readdir($this->_context), ['.', '..'], true)) {
		}
		return $result;
	}

	public function dir_rewinddir() {
		return $this->_context ? rewinddir($this->_context) : false;
	}


	public function rename($pathFrom, $pathTo) {
		$base = $this->_base();
		return @rename($base . $this->path($pathFrom), $base . $this->path($pathTo));
	}


	public function mkdir($path, $mode, $options) {
		return @mkdir($this->_base() . $this->path($path), $this->chmodDir, $options & STREAM_MKDIR_RECURSIVE);
	}

	public function rmdir($path) {
		return @rmdir($this->_base() . $this->path($path));
	}



	public function stream_close() {
		$result = $this->_context && @fclose($this->_context);
		$this->_context = NULL;
		return $result;
	}

	public function stream_eof() {
		return $this->_context ? feof($this->_context) : true;
	}
	public function stream_flush() {
		return $this->_context ? fflush($this->_context) : true;
	}

	public function stream_lock($operation) {
		return $this->_context ? flock($this->_context, $operation) : false;
	}

	public function stream_open($path, $mode, $options, &$openedPath) {
		$base = $this->_base();
		$path = $this->path($path, $protocol);
		$openedPath = $protocol . ':/' . $path;
		$this->_context = fopen($base . $path, $mode);
		if ($this->_context && $mode[0] !== 'r') {
			@chmod($base . $path, $this->chmod);
		}
		return !empty($this->_context);
	}

	public function stream_read($count) {
		return $this->_context ? fread($this->_context, $count) : false;
	}

	public function stream_seek($offset, $whence = SEEK_SET) {
		return $this->_context ? fseek($this->_context, $offset, $whence) : false;
	}

	public function stream_stat() {
		return $this->_context ? fstat($this->_context) : false;
	}

	public function stream_tell() {
		return $this->_context ? ftell($this->_context) : false;
	}

	public function stream_truncate($newSize) {
		return $this->_context ? ftruncate($this->_context, $newSize) : false;
	}

	public function stream_write($data) {
		return $this->_context ? fwrite($this->_context, $data) : false;
	}

	public function unlink($path) {
		return @unlink($this->_base() . $this->path($path));
	}

	public function url_stat($path, $flags) {
		return @stat($this->_base() . $this->path($path));
	}
}
