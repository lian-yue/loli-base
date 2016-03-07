<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-25 03:45:17
/*
/* ************************************************************************** */
namespace Loli;
class Session extends Service {
	protected static $configure = 'session';

	protected static function register(array $config, $group = null) {
		$class = empty($config['type']) ? 'Memory' : $config['type'];

		if ($class{0} !== '\\') {
			$class = __NAMESPACE__ . '\Cache\\' . $class . 'CacheItemPool';
		}
		$result = new $class($config + ['key' => Route::token()->get() . $group . configure('key')]);
		$result->setLogger(Log::session());
		return $result;
	}
}
