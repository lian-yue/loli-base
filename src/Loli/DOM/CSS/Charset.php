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
/*	Updated: UTC 2015-06-10 14:49:24
/*
/* ************************************************************************** */
namespace Loli\DOM\CSS;
// 编码规则
class Charset extends Rule{

	// 规则类型
	protected $type = 2;

	public function __toString() {
		$charset = preg_replace('/[^0-9a-z-]/', '', strtoupper($this->value));
		return $charset ? '@charset \''. $charset .'\';' : '';
	}
}