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
class Shh2Storage extends AbstractStorage{

	protected $dir = '/';

	protected $chmod = 0644;

	protected $chmodDir = 0755;

	protected $hostname = 'localhost';

	protected $username = false;

	protected $password = false;

	protected $timeout = 30;

	protected $methods = [];

	protected $publicKey = false;

	protected $privateKey = false;

	private $link;

	private $sftpLink;

	private $context;

	public function __destruct() {
		unset($this->context);
	}

	private function base() {
		if ($this->link === null) {
			print_r($this);
			$hostname = explode(':', $this->hostname, 2) + [1 => 22];
			if (!$this->link = @ssh2_connect($hostname[0], $hostname[1], $this->publicKey || $this->privateKey ? $this->methods + ['hostkey' => 'ssh-rsa'] : $this->methods, ['disconnect' => [$this, 'disconnect']])) {
				$this->logger && $this->logger->alert(__METHOD__ . '() Unable to connect to server');
				throw new ConnectException(__METHOD__ . '() Unable to connect to server');
				return false;
			}

			if ($this->publicKey || $this->privateKey) {
				if (!@ssh2_auth_pubkey_file($this->link, $this->username, $this->publicKey, $this->privateKey, $this->password)) {
					$this->logger && $this->logger->alert(__METHOD__ . '() Unable to connect to server');
					throw new ConnectException(__METHOD__ .'() Unable to login to the server');
					$this->link = false;
				}
			} else {
				if (!@ssh2_auth_password($this->link, $this->username, $this->password)) {
					$this->logger && $this->logger->alert(__METHOD__ . '() Unable to connect to server');
					throw new ConnectException(__METHOD__ .'() Unable to login to the server');
					$this->link = false;
				}
			}
		}

		if (!$this->link) {
			return false;
		}

		if ($this->sftpLink === null) {
			if (!$this->sftpLink = @ssh2_sftp($this->link)) {
				throw new ConnectException(__METHOD__ . '() ssh2_sftp');
			}
		}

		if (!$this->sftpLink) {
			return false;
		}

		return 'ssh2.sftp://' . $this->sftpLink . ($this->dir && $this->dir !== '/' ? '/' . trim($this->dir, '/') : '');
	}


	public function disconnect() {
		$this->sftpLink = $this->link = null;
	}



	public function dir_closedir() {
		$result = $this->context && closedir($this->context);
		$this->context = null;
		return $result;
	}

	public function dir_opendir($path, $options) {
		$this->context = @opendir($this->base() . $this->path($path));
		return !empty($this->context);
	}

	public function dir_readdir() {
		if (!$this->context) {
			return false;
		}
		while(in_array($result = readdir($this->context), ['.', '..'], true)) {
		}
		return $result;
	}

	public function dir_rewinddir() {
		return $this->context ? rewinddir($this->context) : false;
	}


	public function rename($pathFrom, $pathTo) {
		$base = $this->base();
		return @rename($base . $this->path($pathFrom), $base . $this->path($pathTo));
	}


	public function mkdir($path, $mode, $options) {
		return @mkdir($this->base() . $this->path($path), $this->chmodDir, $options & STREAM_MKDIR_RECURSIVE);
	}

	public function rmdir($path) {
		return @rmdir($this->base() . $this->path($path));
	}



	public function stream_close() {
		$result = $this->context && @fclose($this->context);
		$this->context = null;
		return $result;
	}

	public function stream_eof() {
		return $this->context ? feof($this->context) : true;
	}
	public function stream_flush() {
		return $this->context ? fflush($this->context) : true;
	}

	public function stream_lock($operation) {
		return $this->context ? flock($this->context, $operation) : false;
	}

	public function stream_open($path, $mode, $options, &$openedPath) {
		$base = $this->base();
		$path = $this->path($path, $protocol);
		$openedPath = $protocol . ':/' . $path;
		$this->context = fopen($base . $path, $mode);
		if ($this->context && $mode[0] !== 'r') {
			@chmod($base . $path, $this->chmod);
		}
		return !empty($this->context);
	}

	public function stream_read($count) {
		return $this->context ? fread($this->context, $count) : false;
	}

	public function stream_seek($offset, $whence = SEEK_SET) {
		return $this->context ? fseek($this->context, $offset, $whence) : false;
	}

	public function stream_stat() {
		return $this->context ? fstat($this->context) : false;
	}

	public function stream_tell() {
		return $this->context ? ftell($this->context) : false;
	}

	public function stream_truncate($newSize) {
		return $this->context ? ftruncate($this->context, $newSize) : false;
	}

	public function stream_write($data) {
		return $this->context ? fwrite($this->context, $data) : false;
	}

	public function unlink($path) {
		return @unlink($this->base() . $this->path($path));
	}

	public function url_stat($path, $flags) {
		return @stat($this->base() . $this->path($path));
	}
}
