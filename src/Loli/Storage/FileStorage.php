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

class FileStorage extends AbstractStorage{
	protected $dir = './';

	protected $chmod = 0644;

	protected $chmodDir = 0755;

	private $context;

	public function __destruct() {
		unset($this->context);
	}

	public function dir_closedir() {
		$result = $this->context && closedir($this->context);
		$this->context = null;
		return $result;
	}

	public function dir_opendir($path, $options) {
		$this->context = @opendir($this->dir . $this->path($path));
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
		return @rename($this->dir . $this->path($pathFrom), $this->dir . $this->path($pathTo));
	}

	public function mkdir($path, $mode, $options) {
		return @mkdir($this->dir . $this->path($path), $this->chmodDir, $options & STREAM_MKDIR_RECURSIVE);
	}

	public function rmdir($path) {
		return @rmdir($this->dir . $this->path($path));
	}

	public function stream_close() {
		$result = $this->context && fclose($this->context);
		$this->context = NULL;
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
		$path = $this->path($path, $protocol);
		$openedPath = $protocol . ':/' . $path;
		$this->context = @fopen($this->dir . $path, $mode);
		if ($mode[0] !== 'r') {
			@chmod($this->dir . $path, $this->chmod);
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
		return @unlink($this->dir . $this->path($path));
	}

	public function url_stat($path, $flags) {
		return @stat($this->dir . $this->path($path));
	}
}
