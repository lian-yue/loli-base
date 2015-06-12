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
/*	Updated: UTC 2015-06-10 04:38:50
/*
/* ************************************************************************** */
namespace Loli\DOM\CSS;
class Import extends Rule{


	// 规则类型
	protected $type = 19;

	public function __toString() {

		// 为空
		if (!$this->value) {
			return '';
		}

		// 正则匹配失败
		if (!preg_match('/\s*url\(("|\')?(.+?)(?(1)\1|)\)(?:\s+(.+))?/i', $this->value, $matches) && !preg_match('/\s*("|\')?(.+?)(?(1)\1|)(?:\s+(.+))?/i', $this->value, $matches)) {
			return '';
		}

		// 为空
		if (!$import = preg_replace('/(["\'()*;<>\\\\]|\s)/', '', $matches[2])) {
			return '';
		}


		// 协议不是 http https
		$scheme = parse_url($import, PHP_URL_SCHEME);
		if (($scheme = parse_url($import, PHP_URL_SCHEME)) && strcasecmp($scheme, 'http') !== 0 && strcasecmp($scheme, 'https') !== 0) {
			return '';
		}
		return '@import url(\''. $import .'\') '. $this->mediaQuery($matches[3]) .';';
	}
}