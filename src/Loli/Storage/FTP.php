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
class_exists('Loli\Storage\Base') || exit;
class FTP extends Base{


	protected $dir = '/';

	protected $chmod = 0644;

	protected $chmodDir = 0755;

	protected $hostname = '127.0.0.1';

	protected $username = false;

	protected $password = false;

	protected $ssl = false;

	private $_context;

	public function __destruct() {
		unset($this->_context);
	}

	private function _error() {
		$error = error_get_last();
		if ($error && strpos($error['message'], 'failed to open stream: operation failed') !== false) {
			throw new ConnectException($error['message'], 10);
		}
	}
	private function _base() {
		return ($this->ssl ? 'ftps' : 'ftp') . '://' . ($this->username || $this->password ? $this->username . ':' . $this->password . '@' : '') . $this->hostname . ($this->dir && $this->dir !== '/' ? '/'. trim($this->dir, '/') : '');
	}

	public function dir_closedir() {
		$result = $this->_context && closedir($this->_context);
		$this->_context = NULL;
		return $result;
	}

	public function dir_opendir($path, $options) {
		$this->_context = @opendir($this->_base() . $this->path($path));
		$this->_context || $this->_error();
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
		return @rename($this->_base() . $this->path($pathFrom), $this->_base() . $this->path($pathTo));
	}

	public function mkdir($path, $mode, $options) {
		$path = $this->path($path);
		if ($options & STREAM_MKDIR_RECURSIVE) {
			$base = $this->_base();
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
			return @mkdir($this->_base() . $path, $this->chmodDir);
		}
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

	public function stream_open($path, $mode, $options, &$openedPath) {
		$path = $this->path($path, $protocol);
		$openedPath = $protocol . ':/' . $path;
		$this->_context = @fopen($this->_base() . $path, $mode);
		if ($this->_context && $mode[0] !== 'r') {
			@chmod($this->_base() . $path, $this->chmod);
		}
		$this->_context || $this->_error();
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