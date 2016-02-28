<?php
namespace Loli;
class Log extends Service {

	protected static $configure = 'log';

	protected static $group = true;

	protected static function register(array $config, $group = null) {
		$config  = isset($config[$group]) ? $config[$group] : reset($config);
		$class = empty($config['type']) ? 'Memory' : $config['type'];
		if ($class{0} !== '\\') {
			$class = __NAMESPACE__ . '\Log\\' . $class . 'Logger';
		}
		return new $class($config + ['group' => $group]);
	}
}
