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
/*	Updated: UTC 2015-03-10 08:37:03
/*
/* ************************************************************************** */
namespace Loli\DB;
abstract class Param{
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