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
/*	Updated: UTC 2015-02-25 12:47:56
/*
/* ************************************************************************** */
namespace Loli;
use Loli\Image\Base;
class_exists('Loli\Image\Base') || exit;
class Image{
	const FLIP_HORIZONTAL = Base::FLIP_HORIZONTAL;
	const FLIP_VERTICAL = Base::FLIP_VERTICAL;
	const FLIP_BOTH = Base::FLIP_BOTH;

	const TYPE_JPEG = Base::TYPE_JPEG;
	const TYPE_GIF = Base::TYPE_GIF;
	const TYPE_PNG = Base::TYPE_PNG;
	const TYPE_WEBP = Base::TYPE_WEBP;


	private $_link = '';
	public function __construct($a = '', $type = false) {
		$class = __NAMESPACE__ .'\Image\\' . (empty($_SERVER['LOLI']['IMAGE']['mode']) ? 'GD' : $_SERVER['LOLI']['IMAGE']['mode']);
		$this->_link = new $class($a, $type);
	}
	public function __call($method, $args) {
		return call_user_func_array([$this->_link, $method], $args);
	}
}