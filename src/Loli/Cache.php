<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-27 04:00:39
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
/*	Created: UTC 2015-02-07 12:42:01
/*	Updated: UTC 2015-02-07 12:48:07
/*
/* ************************************************************************** */
namespace Loli;
class Cache extends Group{
	protected static $name = 'cache';

	protected static function link($group, array $config, $exists) {
		$class = empty($config['type']) ? 'Memory' : $config['type'];
		if ($class{0} !== '\\') {
			$class = __NAMESPACE__ . '\Cache\\' . $class . 'CacheItemPool';
		}
		$result = new $class($config + ['key' => $group . (empty($_SERVER['LOLI']['key']) ? '' : $_SERVER['LOLI']['key'])]);
		$result->setLogger(Log::group(static::$name));
		return $result;
	}
}
