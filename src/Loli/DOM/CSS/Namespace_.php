<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-06-10 03:24:18
/*	Updated: UTC 2015-06-10 04:38:44
/*
/* ************************************************************************** */
namespace Loli\DOM\CSS;
// 编码规则
class Namespace_ extends Rule{

	// 规则类型
	protected $type = 10;

	public function __toString() {
		// 为空
		if (!$this->value) {
			return '';
		}

		// 正则匹配失败
		if (!preg_match('/(?:([a-z]+[0-9a-z]*)\s+)url\(("|\')?(.+?)(?(2)\2|)\)/i', $this->value, $matches)) {
			return '';
		}

		// 为空
		if (!$url = preg_replace('/(["\'();*<>\\\\]|\s)/', '', $matches[3])) {
			return '';
		}

		if (!in_array(strtolower(parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
			return '';
		}

		return '@namespace '. $matches[1] .' url('.$url.');';
	}
}