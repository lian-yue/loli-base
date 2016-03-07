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

class Storage{
	private $link = null;

	public function __call($method, $args) {
		static $methods = ['dir_opendir', 'rename', 'stream_open', 'unlink', 'url_stat', 'mkdir', 'rmdir'];
		if (in_array(strtolower($method), $methods, true)) {
			if (!$key = parse_url($args[0], PHP_URL_HOST)) {
				$key = '';
			}
			$configure = configure('storage', []);
			if (empty($configure[$key])) {
				$key = empty($configure) ? '' : key($configure);
			}
			$class = (empty($configure[$key]['type']) ? 'File' : $configure[$key]['type']);
			if ($class[0] !== '\\') {
				$class = __NAMESPACE__ . '\Storage\\' . $class . 'Storage';
			}
			$this->link = new $class(empty($configure[$key]) ? [] : $configure[$key]);
			$this->link->setLogger(Log::storage());
		}
		return $this->link->$method(...$args);
	}
}
