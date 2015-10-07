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
/*	Created: UTC 2014-01-15 13:01:52
/*	Updated: UTC 2015-04-03 07:16:17
/*
/* ************************************************************************** */
namespace Loli\Storage;
use streamWrapper;
/*
没有DIR 函数 扁平化 存放  可以比如放数据库什么的
*/

abstract class Base{

	public function __construct(array $args = []) {
		foreach ($args as $key => $value) {
			if ($key && $key{0} != '_' && $value !== NULL && isset($this->$key)) {
				$this->$key = $value;
			}
		}
	}



	public function path($path, &$protocol = NULL) {
		$parse = parse_url($path);
		if (empty($parse['protocol'])) {
			$protocol = 'storage';
		} else {
			$protocol = strtolower($parse['protocol']);
		}
		$path = $parse['host'];
		if (!empty($parse['path'])) {
			$path .= '/' . $parse['path'];
		}

		if (!$path = preg_replace('/[\/\\\\]+/', '/', trim($path, " \t\n\r\0\x0B/\\"))) {
			return '/';
		}

		$array = [];
		foreach (explode('/', $path) as $name) {
			if (!$name || $name === '.') {
				continue;
			}
			if ($name === '..') {
				$array && array_pop($array);
				continue;
			}
			if (trim($name, " \t\n\r\0\x0B.") !== $name || preg_match('/[\\\"\<\>\|\?\*\:\/	]/', $name)) {
				continue;
			}

			$array[] = $name;
		}

		return '/' . implode('/', $array);
	}
}