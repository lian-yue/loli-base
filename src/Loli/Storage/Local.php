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
/*	Updated: UTC 2015-02-26 10:02:24
/*
/* ************************************************************************** */
namespace Loli\Storage;
class_exists('Loli\Storage\Base') || exit;
class Local extends Base{
	protected $dir = './';

	protected $chmod = 0644;

	protected $chmodDir = 0755;

	public function put($remote, $local) {
		if (!is_file($local)){
			throw new Exception('Storage data does not exist', 5);
		}

		$file = $this->dir . $this->filter($remote);
		$this->_mkdir(dirname($file));
		if (!@copy($local, $file)) {
			throw new Exception('Unable to write data', 6);
		}
		@chmod($file, $this->chmod);
		return true;
	}


	public function get($remote) {
		if (!$this->exists($remote)) {
			throw new Exception('Storage data does not exist', 5);
		}
		return $this->dir. $this->filter($remote);
	}


	public function fput($remote, $resource) {
		if (!is_resource($resource)) {
			throw new Exception('Resource does not exist', 3);
		}

		$file = $this->dir . $this->filter($remote);
		$this->_mkdir(dirname($file));

		// 写入文件
		if (!$fp = fopen($file, 'wb')) {
			throw new Exception('Unable to write data', 6);
		}
		$ftell = ftell($resource);
		fseek($resource, 0);
		while (!feof($resource)) {
		   fwrite($fp, fread($resource, $this->buffer));
		}
		fseek($resource, $ftell);
		fclose($fp);
		@chmod($file, $this->chmod);
		return true;
	}


	public function fget($remote) {
		if (!$this->exists($remote)) {
			throw new Exception('Storage data does not exist', 5);
		}
		return fopen($this->dir . $this->filter($remote), 'rb');
	}


	public function cput($remote, $contents) {
		if ($contents === NULL) {
			throw new Exception('Data storage is empty', 4);
		}
		$remote = $this->filter($remote);
		$file = $this->dir .$remote;
		$this->_mkdir(dirname($file));

		// 写入文件
		if (!$fp = fopen($file, 'wb')) {
			throw new Exception('Unable to write data', 6);
		}
		fwrite($fp, $contents);
		fclose($fp);
		@chmod($file, $this->chmod);
		return true;
	}



	public function cget($remote) {
		if (!$this->exists($remote)) {
			throw new Exception('Storage data does not exist', 5);
		}
		return fread(fopen($file = $this->dir . $this->filter($remote), 'rb'), filesize($file));
	}


	public function rename($source, $destination) {
		if (!$this->exists($remote)) {
			throw new Exception('Storage data does not exist', 5);
		}
		$source = $this->filter($source);
		$destination = $this->filter($destination);
		$this->_mkdir(dirname($destination));
		return @rename($this->dir .$source, $this->dir .$destination);
	}



	public function unlink($remote) {
		if (!$this->exists($source)) {
			throw new Exception('Storage data does not exist', 5);
		}
		if (!@unlink($this->dir . $this->filter($remote))) {
			throw new Exception('You can not delete', 7);
		}
		return true;
	}

	public function unlinks($remote) {
		foreach ($remote as $v) {
			 $this->unlink($v);
		}
		return true;
	}

	public function size($remote) {
		if (!$this->exists($remote)) {
			throw new Exception('Storage data does not exist', 5);
		}
		return filesize($this->dir  . $this->filter($remote));
	}

	public function exists($remote) {
		return is_file($this->dir .$this->filter($remote));
	}

	private function _mkdir($dir) {
		$dir && (is_dir($dir) || @mkdir($dir, $this->chmodDir, true));
	}

}
