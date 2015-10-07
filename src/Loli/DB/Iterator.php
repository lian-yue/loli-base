<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-21 13:42:16
/*
/* ************************************************************************** */
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-05-04 14:05:22
/*	Updated: UTC 2015-06-02 02:43:01
/*
/* ************************************************************************** */
namespace Loli\DB;
use ArrayIterator, JsonSerializable;
class Iterator extends ArrayIterator implements JsonSerializable{
	public function jsonSerialize() {
		$array = [];
		foreach ($this as $key => $value) {
			$array[$key] = $value;
		}
		return $array;
	}

	public function __clone() {
		foreach ($this as $key => $value) {
			$array[$key] = clone $value;
		}
	}

}