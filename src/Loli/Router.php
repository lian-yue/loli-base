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
/*	Updated: UTC 2015-02-17 13:39:48
/*
/* ************************************************************************** */
namespace Loli;
use Loli\HMVC\View;
class Router{
	public static $_request;
	public static $_response;
	public function request() {
		return self::$_response;
	}
	public function response() {
		return self::$_response;
	}

}
/*
class Router{

	private static $_request = [];
	private static $_response = [];
	private static $_ID = 0;
	private static $_routers = [];
	private static $_stacks = [];
	private static $_nameSpaces = [];

	// 默认 host
	public static $host = 'www.loli.dev';

	// 默认方法
	public static $method = ['GET'];

	//
	public static $scheme = ['http', 'https'];

/*
	public static function ID() {
		return end(self::$_stacks);
	}

	public static function request() {
		return self::$_request[self::ID()];
	}


	public static function response() {
		return self::$_response[self::ID()];
	}


	//
	/*public static function add() {
		if (self::$_groups) {
			foreach (array_reverse(self::$_groups) as $value) {
				if (!$host && $value['host']) {
					$host = $value['host'];
				}
				if (!$scheme && $value['scheme']) {
					$scheme = $value['scheme'];
				}
				if ($value['path'] = trim($value['path'], '/')){
					$path = '/' . $value['path'] . '/' . ltrim($path, '/');
				}
			}
		}
		self::$_routers[] = ['path' => $path, 'call' => $call, 'host' => $host, 'method' => $method, 'scheme' => $scheme];
	}*/


	// 添加路由 添加后允许多个访问那命名空间下的数据 比如多域名什么的
	/*public static function add() {
		if (self::$_groups) {
			foreach (array_reverse(self::$_groups) as $value) {
				if (!$host && $value['host']) {
					$host = $value['host'];
				}
				if (!$scheme && $value['scheme']) {
					$scheme = $value['scheme'];
				}
				if ($value['path'] = trim($value['path'], '/')){
					$path = '/' . $value['path'] . '/' . ltrim($path, '/');
				}
			}
		}
		self::$_routers[] = ['path' => $path, 'call' => $call, 'host' => $host, 'method' => $method, 'scheme' => $scheme];
	}


	// 设置命名空间 设置后改名空间的 host  和path 继承 scheme 继承这个的
	public static function setNameSpace($nameSpace, $host = false, $path = '/', $scheme = []) {
		self::$_nameSpaces[$nameSpace] = ['host' => $host, 'path' => $path, 'scheme' => $scheme];
	}

	// 运行
	public static function run(Request &$request, Response &$response = null) {
		array_push(self::$_stacks, self::$_ID);
		if (!$response instanceof Response) {
			$response = new Response($request);
		}
		self::$_request[self::$_ID] = &$request;
		self::$_response[self::$_ID] = &$response;

		// 初始化 语言 时间
		Lang::init();
		Date::init();

		try {
			$method = $request->getMethod();
			$scheme = $request->getScheme();
			$host = $request->getHost();
			$path = $request->getPath();
			$uniqid = 'ID' . uniqid();
			$strtr = [
				'/' => '\\/',
				'(?<$' => '(?<' . $uniqid,
				'(?\'$' => '(?\'' . $uniqid,
				'(?"$' => '(?"' . $uniqid,
				'\p{$' => '\p{'. $uniqid,
				'(?($' => '(?(' . $uniqid
			];
			$defaultHosts = array_map(function($host){ return preg_quote($host, ''); }, (array) self::$host);
			$defaultHosts = implode('|', $defaultHosts);

			foreach (self::$_routers as $value) {
				$params = [];
				$value['scheme'] = $value['scheme'] ? (array) $value['scheme'] : self::$scheme;
				$value['host'] = $value['host'] ? $value['host'] : $defaultHosts;

				// 允许的协议
				if (!in_array($scheme, $value['scheme'] ? (array) $value['scheme'] : self::$scheme)) {
					continue;
				}

				// 允许的方法
				if (!in_array($method, $value['method'] ? (in_array('*', (array)$value['method']) ? ['GET', 'POST', 'OPTIONS', 'PUT', 'DELETE', 'TRACE'] : (array) $value['method']) : self::$method)) {
					continue;
				}

				// 允许的 host
				if (!preg_match('/^'.strtr($value['host'], $strtr). '$/', $host, $matches)) {
					continue;
				}
				$params += $matches;

				// 允许的路径
				if (!preg_match('/^'.strtr($value['path'], $strtr). '$/', $path, $matches)) {
					continue;
				}

				$params = $matches + $params;
				$break = true;
				break;
			}
			empty($break) && $response->addMessage(404);
		} catch (Exception $e) {
			$response->addMessage(500);
		}

		// 没错误 执行控制器
		if (!$messages = $response->getMessages()) {
			$length = strlen($uniqid);
			foreach($params as $name => $param) {
				if (is_int($name)) {
					unset($params[$name]);
					continue;
				}
				if (substr($name, 0, $length) == $uniqid) {
					unset($params[$name]);
					$params['$'. substr($name, $length)] = $param;
				}
			}
			$request->setRewriteParams($params);
			$view = call_user_func($value['call']);
		}

		// 有错误 记录错误代码
		if ($messages = $response->getMessages()) {
			$arrays = ['message' => Lang::get('Error Messages', ['message', 'default'])];
			foreach($messages as $e) {
				// 如果 误代码是 400 -599 范围内并且状态码 是200 就写入状态码
				if (is_int($code = $e->getCode()) && $code >= 400 && $code< 600 && $response->getStatus() == 200) {
					$response->setStatus($code);
				}
				$arrays['errors'][$code] = ['code' => $code, 'message' => $e->getMessage(), 'args' => $e->getArgs()];
				$arrays += $e->getData();
			}
			$view = new View('messages', $arrays);
		}

		// 没错误 返回的是 数组 stdClass 对象 自动创建 View
		if (is_array($view) || (is_object($view) && get_class($view) == 'stdClass')) {
			$view = new view($value['path'], $view);
		}


		// 如果 是 Ajax 的 并且是 View 执行
		if ($request->isAjax() && $view instanceof View) {
			$response->setAjax($view->getData());
		} else {
			$response->setContent($view);
		}

		// 删除请求
		unset(self::$_request[self::ID()], self::$_response[self::ID()]);

		// 出栈
		array_pop(self::$_stacks);

		++self::$_ID;
		return $response;
	}
}

/*

if (!empty($_SERVER['LOLI']['ROUTE'])) {
	foreach ($_SERVER['LOLI']['ROUTE'] as $key => $value) {
		if (in_array($key, ['host', 'method', 'scheme'])) {
			Router::$default[$key] = $value;
		}
	}
	unset($key, $value);
}


    // 默认
	/*public static $default = [
		'scheme' => ['http', 'https'],
		'method' => ['GET'],
		'host' => '',
	];

	public static $node = [];
	public static $currentGroup = [];


	//public static function addNode($path, $call, $method = ['GET'], $host = false, $scheme = []) {
	//	self::$node[(self::$currentGroup ? '/' . implode('/', self::$currentGroup) : self::$currentGroup) . '/'. trim($path, '/')] = ['path' => $path, 'call' => $call, 'method' => $method, 'host' => $host, 'scheme' => $scheme];
	//}

	//public static function addGroup($path, $call, $host = false, $scheme = ['http', 'https']) {
	//	self::$_all[] = [, 'node' => $node] + self::$default;
	//}
	//
	//

	public function getID() {
		return key(self::$_request);
	}


	public function request() {
		return current(self::$_request);
	}

	public function response() {
		$id = $this->getID();
		if (empty(self::$_response[$id])) {
			self::$_response[$id] = new Response;
		}
		return self::$_response[$id];
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
			Router::$default[$key] = $value;
		}
	}
	unset($key, $value);
}
*/