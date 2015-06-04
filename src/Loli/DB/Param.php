<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-05-04 11:42:33
/*	Updated: UTC 2015-05-23 11:47:41
/*
/* ************************************************************************** */
namespace Loli\DB;
class Param extends Row{
	public function setParams(array $params) {
		foreach ($params as $key => $value) {
			$this->$key = $value;
		}
	}
	public function setParam($key, $value) {
		$this->$key = $value;
	}
}