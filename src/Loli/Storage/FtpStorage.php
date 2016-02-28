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
namespace Loli\Storage;
use Psr\Log\LogLevel;
class FtpStorage extends AbstractStorage{


	protected $dir = '/';

	protected $chmod = 0644;

	protected $chmodDir = 0755;

	protected $hostname = '127.0.0.1';

	protected $username = false;

	protected $password = false;

	protected $ssl = false;

	private $context;

	public function __destruct() {
		unset($this->context);
	}

	private function error() {
		$error = error_get_last();
		if ($error && strpos($error['message'], 'failed to open stream: operation failed') !== false) {
			$this->throwLog(new ConnectException($error['message']), LogLevel::ALERT);
		}
	}

	private function base() {
		return ($this->ssl ? 'ftps' : 'ftp') . '://' . ($this->username || $this->password ? $this->username . ':' . $this->password . '@' : '') . $this->hostname . ($this->dir && $this->dir !== '/' ? '/'. trim($this->dir, '/') : '');
	}

	public function dir_closedir() {
		$result = $this->context && closedir($this->context);
		$this->context = null;
		return $result;
	}

	public function dir_opendir($path, $options) {
		$this->context = @opendir($this->base() . $this->path($path));
		$this->context || $this->error();
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
		return @rename($this->base() . $this->path($pathFrom), $this->base() . $this->path($pathTo));
	}

	public function mkdir($path, $mode, $options) {
		$path = $this->path($path);
		if ($options & STREAM_MKDIR_RECURSIVE) {
			$base = $this->base();
			$names = $path === '/' ? [] : explode('/', trim($path, '/'));
			$mkdir = [];
			while ($names) {
				if (@file_exists($base . '/' . implode('/', $names))) {
					break;
				}
				$mkdir[] = array_pop($names);
			}
			if (!$mkdir) {
				return false;
			}
			$path = $base . implode('/', $names);
			while ($mkdir) {
				$path .= '/' . array_pop($mkdir);
				if (!@mkdir($path, $this->chmodDir)) {
					return false;
				}
			}
			return true;
		} else {
			return @mkdir($this->base() . $path, $this->chmodDir);
		}
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

	public function stream_open($path, $mode, $options, &$openedPath) {
		$path = $this->path($path, $protocol);
		$openedPath = $protocol . ':/' . $path;
		$this->context = @fopen($this->base() . $path, $mode);
		if ($this->context && $mode[0] !== 'r') {
			@chmod($this->base() . $path, $this->chmod);
		}
		$this->context || $this->error();
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
