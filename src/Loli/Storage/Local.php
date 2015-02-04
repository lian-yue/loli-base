<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-01-15 13:01:52
/*	Updated: UTC 2015-02-03 09:19:59
/*
/* ************************************************************************** */
namespace Loli\Storage;
class_exists('Loli\Storage\Base') || exit;
class Local extends Base{
	public $dir = './';

	public $chmod = 0644;

	public $chmodDir = 0755;


	public function __construct($args) {
		foreach ($args as $k => $v) {
			if (in_array($k, ['dir', 'chmod', 'chmodDir'])) {
				$this->$k = $v;
			}
		}
	}


	public function put($remote, $local) {
		if (!($remote = $this->filter($remote)) || !is_file($local)) {
			return false;
		}
		$file = $this->dir . '/' . $remote;
		$this->_mkdir(dirname($file));
		if (!@copy($local, $file)) {
			return false;
		}

		@chmod($file, $this->chmod);
		return true;
	}


	public function get($remote) {
		if (!($remote = $this->filter($remote)) || !$this->exists($remote)) {
			return false;
		}
		return $this->dir. '/'.$remote;
	}


	public function fput($remote, $resource) {
		if (!($remote = $this->filter($remote)) || !is_resource($resource)) {
			return false;
		}
		$file = $this->dir . '/' . $remote;
		$this->_mkdir(dirname($file));

		// 写入文件
		if (!$fopen = fopen($file, 'wb')) {
			return false;
		}
		$ftell = ftell($resource);
		fseek($resource, 0);
		while (!feof($resource)) {
		   fwrite($fopen, fread($resource, 1024 * 1024));
		}
		fseek($resource, $ftell);
		fclose($fopen);
		@chmod($file, $this->chmod);
		return true;
	}


	public function fget($remote) {
		if (!($remote = $this->filter($remote)) || !$this->exists($remote)) {
			return false;
		}
		return fopen($this->dir . '/' . $remote , 'rb');
	}


	public function cput($remote, $contents) {
		if (!($remote = $this->filter($remote)) || $contents === null) {
			return false;
		}
		$file = $this->dir . '/' . $remote;
		$this->_mkdir(dirname($file));

		// 写入文件
		if (!$fopen = fopen($file, 'wb')) {
			return false;
		}
		fwrite($fopen, $contents);
		fclose($fopen);
		@chmod($file, $this->chmod);
		return true;
	}



	public function cget($remote) {
		if (!($remote = $this->filter($remote)) || !$this->exists($remote)) {
			return false;
		}
		return fread(fopen($file = $this->dir . '/' . $remote, 'rb'), filesize($file));
	}


	public function rename($source, $destination) {
		if (!($source = $this->filter($source)) || !($destination = $this->filter($destination)) || !$this->exists($source)) {
			return false;
		}
		$this->_mkdir(dirname($destination));
		return @rename($this->dir . '/' .$source, $this->dir . '/' .$destination);
	}



	public function unlink($remote) {
		return !($remote = $this->filter($remote)) && $this->exists($remote) && @unlink($this->dir . '/' . $remote);
	}

	public function unlinks($remote) {
		foreach ($remote as $v) {
			 $this->unlink($v);
		}
		return true;
	}

	public function size($remote) {
		if (!($remote = $this->filter($remote)) || !$this->exists($remote)) {
			return false;
		}
		return filesize($this->dir . '/' . $remote);
	}

	public function exists($remote) {
		return ($remote = $this->filter($remote)) && is_file($this->dir . '/' . $remote);
	}

	private function _mkdir($dir) {
		$dir && (is_dir($dir) || @mkdir($dir, $this->chmodDir, true));
	}

}
