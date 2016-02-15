<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-10-06 03:56:55
/*
/* ************************************************************************** */
namespace Loli;
use finfo;

class Storage{
	private $_link = NULL;

	public function __call($method, $args) {
		static $methods = ['dir_opendir', 'rename', 'stream_open', 'unlink', 'url_stat', 'mkdir', 'rmdir'];
		if (in_array(strtolower($method), $methods, true)) {
			if (!$key = parse_url($args[0], PHP_URL_HOST)) {
				$key = '';
			}
			if (empty($_SERVER['LOLI']['storage'][$key])) {
				$key = empty($_SERVER['LOLI']['storage']) ? '' : key($_SERVER['LOLI']['storage']);
			}
			$class = (empty($_SERVER['LOLI']['storage'][$key]['type']) ? 'File' : $_SERVER['LOLI']['storage'][$key]['type']);
			if ($class[0] !== '\\') {
				$class = __NAMESPACE__ . '\Storage\\' . $class;
			}
			$this->_link = new $class(empty($_SERVER['LOLI']['storage'][$key]) ? [] : $_SERVER['LOLI']['storage'][$key]);
		}
		return $this->_link->$method(...$args);
	}


	public static function mime($file) {
		$info = new finfo();
		return ['type' => $info->file($file, FILEINFO_MIME_TYPE), 'encoding' => $info->file($file, FILEINFO_MIME_ENCODING)];
	}
}
