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
/*	Updated: UTC 2015-05-17 02:16:50
/*
/* ************************************************************************** */
namespace Loli\DB;
use ArrayAccess;
class Row implements ArrayAccess{
	public function __construct($args = []) {
		foreach ($args as $key => $value) {
			if ($value !== NULL) {
				$this->$key = $value;
			}
		}
	}

	public function __get($name) {
		return NULL;
	}

	public function offsetExists($offset) {
		return isset($this->$offset);
	}
	public function offsetGet($offset) {
		return $this->$offset;
	}
	public function offsetSet($offset , $value) {
		if ($offset === NULL) {
			throw new Exception('Row.offsetSet(NULL)', 'The offset can not be empty');
		}
		$this->$offset = $value;
	}
	public function offsetUnset($offset) {
		unset($this->$offset);
	}
}