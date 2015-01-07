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
/*	Updated: UTC 2015-01-07 13:49:38
/*
/* ************************************************************************** */
namespace Loli;
class Image{
	const FLIP_HORIZONTAL = 1;

	const FLIP_VERTICAL = 2;

	const FLIP_BOTH = 3;



	const TYPE_JPEG = 1;

	const TYPE_GIF = 2;

	const TYPE_PNG = 3;

	const TYPE_WEBP = 4;


	private $_link = '';
	public function __construct($a = '', $type = false) {
		$class = __NAMESPACE__ '\Image\\' . (empty($_SERVER['LOLI']['IMAGE']['mode']) ? 'GD' : $_SERVER['LOLI']['IMAGE']['mode']);
		$this->_link = new $class($a, $type);
	}
	public function __call() {
		return call_user_func_array([$this->_link, $method], $args);
	}
}