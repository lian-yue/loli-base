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


use Psr\Log\LogLevel;
use Psr\Log\LoggerAwareInterface;

use Loli\Traits\ConstructConfigTrait;
use Loli\Traits\LoggerAwareExceptionTrait;




abstract class AbstractStorage implements LoggerAwareInterface{
	use ConstructConfigTrait, LoggerAwareExceptionTrait;

	public function path($path, &$protocol = null) {
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
		foreach (explode('/', urldecode($path)) as $name) {
			if (!$name || $name === '.') {
				continue;
			}
			if ($name === '..') {
				$array && array_pop($array);
				continue;
			}
			if (trim($name, " \t\n\r\0\x0B.") !== $name || preg_match('/[\\\"\<\>\|\?\*\:\/	]/', $name)) {
				$this->throwLog(new \InvalidArgumentException('Path is not allowed'), LogLevel::ERROR, ['path' => $path]);
			}
			$array[] = $name;
		}
		return '/' . str_replace(' ', '%20', implode('/', $array));
	}
}
