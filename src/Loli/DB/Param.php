<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-03-05 17:06:44
/*	Updated: UTC 2015-03-14 14:13:46
/*
/* ************************************************************************** */
namespace Loli\DB;
class Param{
	public function __construct(array $params = []) {
		$this->setParams($params);
	}
	public function setParams(array $params) {
		foreach ($params as $key => $value) {
			$this->$key = $value;
		}
	}
	public function setParam($key, $value) {
		$this->$key = $value;
	}
	public function __get($name) {
		return NULL;
	}
}