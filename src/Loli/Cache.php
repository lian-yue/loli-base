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
class Cache extends Service{
	protected static $configure = 'cache';

	protected static $group = true;

	protected static function register(array $config, $group = null) {
		$config  = isset($config[$group]) ? $config[$group] : reset($config);
		$class = empty($config['type']) ? 'Memory' : $config['type'];

		if ($class{0} !== '\\') {
			$class = __NAMESPACE__ . '\Cache\\' . $class . 'CacheItemPool';
		}
		$result = new $class($config + ['key' => $group . (empty($_SERVER['LOLI']['key']) ? '' : $_SERVER['LOLI']['key'])]);
		$result->setLogger(Log::cache());
		return $result;
	}
}
