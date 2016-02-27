<?php
namespace Loli;
class Group{

	protected static $name;

	public static function __callStatic($method, $args) {
		return static::group('default')->$method(...$args);
	}

	public static function group($group) {
		static $links = [], $configs = [];

		if (empty($links[$group])) {
			if (empty($configs)) {
				foreach (empty($_SERVER['LOLI'][static::$name]) ? [[]] : $_SERVER['LOLI'][static::$name] as $key => $value) {
					$configs[is_int($key) ? 'default' : $key] = (array) $value;
				}
				if (!isset($configs['default'])) {
					$configs['default'] = $configs ? reset($configs) : [];
				}
			}
			$exists = isset($configs[$group]);
			$config = $exists ? $configs[$group] : reset($configs);
			$links[$group] = static::link($group, $config, $exists);
		}
		return $links[$group];
	}

	protected static function link($group, array $config, $exists) {

	}
}
