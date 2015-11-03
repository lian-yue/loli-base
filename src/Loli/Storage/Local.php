<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-10-06 05:16:44
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
/*	Created: UTC 2014-01-15 13:01:52
/*	Updated: UTC 2015-02-26 10:02:24
/*
/* ************************************************************************** */
namespace Loli\Storage;
class_exists('Loli\Storage\Base') || exit;
class Local extends Base{
	protected $dir = './';

	protected $chmod = 0644;

	protected $chmodDir = 0755;

	private $_context;

	public function __destruct() {
		unset($this->_context);
	}

	public function dir_closedir() {
		$result = $this->_context && closedir($this->_context);
		$this->_context = NULL;
		return $result;
	}

	public function dir_opendir($path, $options) {
		if (!$path = $this->path($path)) {
			return false;
		}
		$this->_context = @opendir($this->dir . $path);
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
		if (!($pathFrom = $this->path($pathFrom)) || !($pathTo = $this->path($pathTo))) {
			return false;
		}
		return @rename($this->dir . $pathFrom, $this->dir . $pathTo);
	}

	public function mkdir($path, $mode, $options) {
		if (!$path = $this->path($path)) {
			return false;
		}
		return @mkdir($this->dir . $path, $this->chmodDir);
	}

	public function rmdir($path) {
		if (!$path = $this->path($path)) {
			return false;
		}
		return @rmdir($this->dir . $path);
	}

	public function stream_close() {
		$result = $this->_context && fclose($this->_context);
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
		if (!$path = $this->path($path, $protocol)) {
			return false;
		}
		$openedPath = $protocol . ':/' . $path;
		$this->_context = @fopen($this->dir . $path, $mode);
		if ($mode[0] !== 'r') {
			@chmod($this->dir . $path, $this->chmod);
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
		if (!$path = $this->path($path)) {
			return false;
		}
		return @unlink($this->dir . $path);
	}

	public function url_stat($path, $flags) {
		if (!$path = $this->path($path)) {
			return false;
		}
		return @stat($this->dir . $path);
	}
}
