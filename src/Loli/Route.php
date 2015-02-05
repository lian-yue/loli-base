<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-12-31 10:37:27
/*	Updated: UTC 2015-02-05 16:37:14
/*
/* ************************************************************************** */
namespace Loli;
class Route{
	private $_run = false;

    // 默认
	public static $default = [
		'scheme' => ['http', 'https'],
		'method' => ['GET'],
		'host' => [''],
	];

	public static $node = [];
	public static $currentGroup = [];


	public static function addNode($path, $call, $method = ['GET'], $host = false, $scheme = []) {
		self::$node[(self::$currentGroup ? '/' . implode('/', self::$currentGroup) : self::$currentGroup) . '/'. trim($path, '/')] = ['path' => $path, 'call' => $call, 'method' => $method, 'host' => $host, 'scheme' => $scheme];
	}

	public static function addGroup($path, $call, $host = false, $scheme = ['http', 'https']) {
		self::$_all[] = [, 'node' => $node] + self::$default;
	}

	public static function run() {
		if (is_string($args)) {
			$args['path'] = $args;
		}
		$args += self::$default;
		$class = 'Controller';
		$key = strtr($class, '\\', '/') . '.';
		$this->_controller->load('include');

		$a = $this;
		$rewrite = [];
		$after =  substr($this->_path, 1);
		while($after !== false) {
			list($current, $after) = explode('/', $after, 2) + [1 => false];

			// 没解析到
			if (!$node = $a->runNode($current, $after, $rewrite)) {
				Message::set(404);
				Message::run();
			}

			// 读节点信息错误
			if (!$value = $a->getNode($node)) {
				Message::set(500);
				Message::run();
			}

			// 无下一个
			if (empty($value['class']) && empty($value['method'])) {
				Message::set(500);
				Message::run();
			}

			// 类型必须不是0
			if (!empty($value['method']) && !empty($value['type'])) {
				Message::set(500);
				Message::run();
			}

			// 类型必须是0
			if (!empty($value['type']) && empty($value['method'])) {
				Message::set(500);
				Message::run();
			}


			// 选择节点
			$this->_node[] = $node;

			// 有类的
			if (!empty($value['class'])) {
				$class .= '\\' .  $value['class'];
				$a = new $class;
				$key .=  $value['class'] . '/';
				do_array_call(rtrim($key, '/'), [&$a]);
			}

			// 有方法的 跳出
			if (!empty($value['method'])) {
				$method = $value['method'];
				break;
			}
		}

		// 没方法  404
		if (empty($method)) {
			Message::set(404);
			Message::run();
		}

		// 方法不存在
		if (method_exists($a, $method)) {
			Message::set(500);
			Message::run();
		}

		// 重写 _REQUEST
		$_REQUEST = array_merge($_GET, $_POST, $rewrite);


		// 对象引用
		foreach ($this->quotes as $v) {
			$a->$v =& $this->$v;
		}

		// 需要判断权限的
		if (!isset($value['auth']) || $value['auth'] !== false) {
			if (!$a->auth($a->node)) {
				Message::set(403);
				Message::run();
			}
		}

		// 默认执行的
		$a->init();

		// 返回数据
		return $a->$method();
	}
}




if (!empty($_SERVER['LOLI']['ROUTE'])) {
	foreach ($_SERVER['LOLI']['ROUTE'] as $key => $value) {
		if (in_array($key, ['host', 'method', 'scheme'])) {
			Route::$default[$key] = $value;
		}
	}
	unset($key, $value);
}
