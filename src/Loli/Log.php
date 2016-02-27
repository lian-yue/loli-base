<?php
namespace Loli;
class Log extends Group{

	protected static $name = 'log';

	protected static function link($group, array $config, $exists) {
		$class = empty($config['type']) ? 'Memory' : $config['type'];
		if ($class{0} !== '\\') {
			$class = __NAMESPACE__ . '\Log\\' . $class . 'Logger';
		}
		return new $class($config + ['group' => $group]);
	}
}
