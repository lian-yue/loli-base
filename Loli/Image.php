<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-01 15:44:57
/*	Updated: UTC 2015-01-01 15:58:42
/*
/* ************************************************************************** */
namespace Loli;
class Image{
	private $_link = '';
	public function __construct($a = '', $type = false) {
		$class = __NAMESPACE__ '\Image\\' . (empty($_SERVER['LOLI']['IMAGE']['mode']) ? 'GD' : $_SERVER['LOLI']['IMAGE']['mode']);
		$this->_link = new $class($a, $type);
	}
	public function __call() {
		return call_user_func_array([$this->_link, $method], $args);
	}
}