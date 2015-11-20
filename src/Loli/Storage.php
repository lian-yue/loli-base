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
		if (in_array($method, $methods, true)) {
			$parse = parse_url($args[0]);
			$key = empty($parse['host']) ? '' : $parse['host'];
			if (empty($_SERVER['LOLI']['STORAGE'][$key])) {
				$key = empty($_SERVER['LOLI']['STORAGE']) ? '' : key($_SERVER['LOLI']['STORAGE']);
			}
			$class = __NAMESPACE__ . '\Storage\\' . (empty($_SERVER['LOLI']['STORAGE'][$key]['type']) ? 'Local' : $_SERVER['LOLI']['STORAGE'][$key]['type']);
			$this->_link = new $class(empty($_SERVER['LOLI']['STORAGE'][$key]) ? [] : $_SERVER['LOLI']['STORAGE'][$key]);
		}
		return call_user_func_array([$this->_link, $method], $args);
	}


	public static function mime($file) {
		$info = new finfo();
		if (!is_file($file)) {
			return false;
		}
		return ['type' => $info->file($file, FILEINFO_MIME_TYPE), 'encoding' => $info->file($file, FILEINFO_MIME_ENCODING)];
	}
}