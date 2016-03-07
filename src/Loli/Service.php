<?php
namespace Loli;
class Service{

	protected static $configure;

	protected static $reuse = false;

	protected static $group = false;

	protected $link;

	protected static function getService($group = null) {
		static $services = [], $configs = [];
		if (static::$group) {
			$group = strtolower($group);
			if (!isset($services[static::class][$group])) {
				if (!isset($configs[static::class])) {
					if (static::$configure && ($configure = configure(static::$configure, []))) {
						foreach ($configure as $key => $value) {
							$config[is_int($key) ? 'default' : $key] = (array) $value;
						}
					}
					if (empty($config)) {
						$config['default'] = [];
					}
					$configs[static::class] = $config;
				}
				$class = static::register($configs[static::class], $group);
				if (static::$reuse) {
					return $class;
				}
				$services[static::class][$group] = $class;
			}
			return $services[static::class][$group];
		} else {
			if (!isset($services[static::class])) {
				if (!isset($configs[static::class])) {
					$configs[static::class] = static::$configure ? configure(static::$configure, []) : [];
				}
				$class = static::register($configs[static::class]);
				if (static::$reuse) {
					return $class;
				}
				$services[static::class] = $class;
			}
			return $services[static::class];
		}
	}


	protected static function register(array $config, $group = null) {
		throw new \RuntimeException('The method is not registered');
	}

	public static function __callStatic($method, $args) {
		if (static::$group) {
			if ($args) {
				throw new \InvalidArgumentException('The service group cannot pass arguments');
			}
			return static::getService($method);
		} else {
			return static::getService()->$method(...$args);
		}
	}

	public function __construct($group = null) {
		$this->link = static::getService($group);
	}

	public function __call($method, $args) {
		return $this->link->$method(...$args);
	}

	public function __get($name) {
		return $this->link->$name;
	}

	public function __set($name, $value) {
		return $this->link->$name = $value;
	}

	public function __isset($name) {
		return isset($this->link->$name);
	}

	public function __unset($name) {
		unset($this->link->$name);
		return true;
	}
}
