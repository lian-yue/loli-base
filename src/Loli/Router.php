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
/*	Updated: UTC 2015-02-24 06:04:38
/*
/* ************************************************************************** */
namespace Loli;
use Loli\HMVC\View, Loli\HMVC\Error, Loli\HMVC\Message, stdClass;
class Router{

	// 全部 资源
	private static $_this = [];

	// 全部命名空间
	private static $_all = [];

	// 默认主机
	public static $host = '.*';

	// 默认参数
	public static $scheme = ['http', 'https'];



	public $request;
	public $response;
	private $_key;


	// 当前请求
	public static function request() {
		return current(self::$_this)->request;
	}

	// 当前返回
	public static function response() {
		return current(self::$_this)->response;
	}

	/**
	 * 添加类或命名空间
	 * @param string  $class     命名空间 class = class  class\ = name Space 命名空间允许递归
	 * @param string  $path      自定义定义 path
	 * @param boolean $host      自定义定义 host
	 * @param [type]  $scheme    允许的协议
	 * @param integer $priority  优先级
	 */
	public static function add($class, $path = false, $host = false, $scheme = [], $priority = 10) {
		self::$_all[$class][$priority][] = ['path' => $path, 'host' => $host, 'scheme' => $scheme, 'priority' => $priority];
		return true;
	}

	/**
	 * url 地址
	 * @param [type]  $class  [description]
	 * @param [type]  $args   [description]
	 * @param boolean $path   [description]
	 * @param boolean $host   [description]
	 * @param [type]  $scheme [description]
	 */
	//public static function URL($class, $args, $path = false, $host = false, $scheme = []) {
		//self::$_all[$class][$priority][] = ['path' => $path, 'host' => $host, 'scheme' => $scheme, 'recursive' => $recursive, 'priority' => $priority];
	//}

	//public static function addClass($nameSpace, $host = false, $path = '/', $scheme = []) {
	//	self::$_nameSpaces[$nameSpace] = ['host' => $host, 'path' => $path, 'scheme' => $scheme];
	//}

	//public static function addClass($nameSpace, $host = false, $path = '/', $scheme = []) {
	//	self::$_nameSpaces[$nameSpace] = ['host' => $host, 'path' => $path, 'scheme' => $scheme];
	//}
	//

	//public static function add($nameSpace, $host = false, $path = '/', $scheme = []) {
//		self::$_nameSpaces[$nameSpace] = ['host' => $host, 'path' => $path, 'scheme' => $scheme];
//	}

	//public function add(Request $request, Response $response = null) {

	//}
	//
	//

	//public static function add($space, $path, $host = '', $scheme = []) {
	//	self::$_nameSpaces[] = ['space' => trim(strtr($space, '\\', '/'), '/'), 'path' => $path, 'host' => $host, 'scheme' => $scheme];
	//	return true;
	//}


	public function __construct(Request &$request, Response &$response = null) {
		if (!$response instanceof Response) {
			$response = new Response($request);
		}
		self::$_this[] = &$this;
		end(self::$_this);
		$this->_key = key(self::$_this);


		$this->request = &$request;
		$this->response = &$response;
		try {

			Lang::init();
			Filter::run('Router', [$this]);
			$method = $request->getMethod();
			$scheme = $request->getScheme();
			$host = $request->getHost();
			$path = $request->getPath();
			$uniqid = 'ID' . uniqid() . mt_rand();
			$strtr = [
				'/' => '\\/',
				'(?<$' => '(?<' . $uniqid,
				'(?\'$' => '(?\'' . $uniqid,
				'(?"$' => '(?"' . $uniqid,
				'\p{$' => '\p{'. $uniqid,
				'(?($' => '(?(' . $uniqid
			];

			uksort(self::$_all, function($str1, $str2) {
				if (($len1 = strlen($str1)) > ($len2 = strlen($str2))) {
					return 1;
				}
				if (($len1 = strlen($str1)) < ($len2 = strlen($str2))) {
					return -1;
				}
				return 0;
			});

			foreach (self::$_all as $class => &$vlas) {
				ksort($vlas, SORT_NUMERIC);
				foreach ($vlas as &$values) {
					foreach ($values as &$value) {

						// 整理 class
						$class = strtr($class, '//', '\\');
						if ($class != '\\' && $class{0} == '\\') {
							$class = ltrim($class, '\\');
						}

						// class 是否是命名空间
						$isNameSpace = $class && substr($class, -1, 1) == '\\';


						// 整理 path
						if (!$value['path']) {
							$value['path'] = '/' . strtolower(trim(strtr($class, '\\', '/'), '/'));
						}

						// 带有命名空间的path
						if ($isNameSpace && strpos($value['path'], '(?<$class') === false && strpos($value['path'], '(?\'$class') === false && strpos($value['path'], '(?"$class') === false) {
							$value['path'] .= '(?<$class>(?:/[_a-z][0-9a-z_]*)+)/?';
						}

						// 整理 host
						if (!$value['host']) {
							$value['host'] =  self::$host;
						}

						// 整理协议
						$value['scheme'] = $value['scheme'] ? (array) $value['scheme'] : self::$scheme;


						// class 加上前缀
						$class = 'Controller\\' . ltrim($class, '\\');


						// 允许的协议
						if (!in_array($scheme, $value['scheme'])) {
							continue;
						}
						$params = [];

						// 允许的 host
						if (!preg_match('/^'.strtr($value['host'], $strtr). '$/', $host, $matches)) {
							continue;
						}
						$params += $matches;

						// 允许的路径
						if (!preg_match('/^'.strtr($value['path'], $strtr). '$/', $path, $matches)) {
							continue;
						}

						// 带有命名空间的
						if ($isNameSpace && empty($matches[$uniqid.'class']) || !class_exists(($class .= strtr(ucwords(strtr(trim($matches[$uniqid.'class'], '/'), '/', ' ')), ' ', '\\')))) {
							continue;
						}

						$params = $matches + $params;
						$break = true;
						break 3;
					}
				}
			}


			// 404 页面不存在
			if (empty($break)) {
				throw new Error(404);
			}

			// 整理 取得的参数 并且写入
			$length = strlen($uniqid);
			foreach($params as $name => $param) {
				if (is_int($name) || $name === ($uniqid . 'class')) {
					unset($params[$name]);
					continue;
				}
				if (substr($name, 0, $length) == $uniqid) {
					unset($params[$name]);
					$params['$'. substr($name, $length)] = $param;
				}
			}
			$request->setParams($params);


			// 执行控制器　返回视图
			$view = new $class($request, $response);


			// 返回的是异常
			if ($view instanceof \Exception) {
				throw $view;
			}

			// 控制器返回的数组 stdclass 对象 自动 创建视图
			if (is_array($view) || $view instanceof stdClass) {
				$view = new View(strtolower(strtr($class, '\\', '/')), $view);
			}
		} catch (Error $errors) {
			// 错误捕获
		} catch (Message $messages) {
			// 消息捕获
		} catch (\Exception $e) {
			// 其他异常捕获 创建错误
			$errors = new Error;
		}


		// 错误控制器
		if (!empty($errors)) {
			// 自动重定向的
			if ($errors->getRedirect() && is_string($errors->getRedirect()) && $errors->getRefresh() == 0 && in_array($response->getStatus(), [200, 300, 301,302,303])) {
				$response->getStatus() == 200 && $response->setStatus(302);
				$response->addHeader('Location', $errors->getRedirect(), false);
			}
			$data = ['title' => $errors->getTitle(), 'redirect' => $errors->getRedirect(), 'refresh' => $errors->getRefresh()];
			foreach($errors as $error) {
				// 是 400-599 的状态码 设置 http 状态码
				if (is_int($code = $error->getCode()) && $code >= 400 && $code < 600 && $response->getStatus() == 200) {
					$response->setStatus($code);
				}
				$response->addHeader('X-Error', $code);
				$data['errors'][$code] = ['message' => $error->getMessage(), 'code' => $code, 'args' => $error->getArgs()];
				$data += $error->getData();
			}
			$response->addCache('no-cache', 0);
			$view = new View('errors', $data);
		}


		// 消息控制器
		if (!empty($messages)) {
			// 自动重定向的
			if ($messages->getRedirect() && is_string($messages->getRedirect()) && $messages->getRefresh() == 0 && in_array($response->getStatus(), [200, 300, 301,302,303])) {
				$response->getStatus() == 200 && $response->setStatus(302);
				$response->addHeader('Location', $messages->getRedirect(), false);
			}
			$data = ['title' => $messages->getTitle(), 'redirect' => $messages->getRedirect(), 'refresh' => $messages->getRefresh()];
			foreach($messages as $message) {
				// 是 200-399 的状态码 设置 http 状态码
				if (is_int($code = $messages->getCode()) && $code >= 200 && $code < 400 && $response->getStatus() == 200) {
					$response->setStatus($code);
				}
				$response->addHeader('X-Message', $code);
				$data['messages'][$code] = ['message' => $message->getMessage(), 'code' => $code, 'args' => $message->getArgs()];
				$data += $message->getData();
			}
			$response->addCache('no-cache', 0);
			$view = new View('messages', $data);
		}


		// 发送内容
		$response->setContent($view);




		// 消息控制器
		//
		//
		//
		//

		/*
		// 捕获的错误
			$data = [];
			$data['title'] = 'Error Messages';
			$messages = [];
			foreach($errors as $error) {
				// 状态码
				if (is_int($code = $error->getCode()) && $code >= 400 && $code < 600 && $response->getStatus() == 200) {
					$response->setStatus($code);
				}
				$messages[] = [$code] + $error->getArgs();
				$data += $error->getData();
			}
			$view = new Messages($messages, $data, true, 3);

		 */

		// 没错误 执行控制器
		/*if (empty($errors)) {
			try {
				try {
				try {
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
					$request->setParams($params);
					$view = new $class($request, $response);
				} catch (Message $e) {
					// Message
				} catch (Exception $e) {
					// 500
					new Message(500);
				}
		}



		// 有错误的
		/*if (Message::all()) {
			$arrays = ['message' => Message::title()];
			foreach(Message::all() as $message) {
				// 如果 误代码是 400 -599 范围内并且状态码 是200 就写入状态码
				if (is_int($code = $message->getCode()) && $code >= 400 && $code< 600 && $response->getStatus() == 200) {
					$response->setStatus($code);
				}
				$arrays['errors'][$code] = ['code' => $code, 'message' => $message->getMessage(), 'args' => $message->getArgs()];
				$arrays += $message->getData();
			}
			$view = new View('messages', $arrays);
		}*/




		//unset(self::$_this[$this->_key]);
		//end(self::$_this);

		/*try {
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

			//$defaultHosts = array_map(function($host){ return preg_quote($host, ''); }, (array) self::$hosts);
			//$defaultHosts = implode('|', $defaultHosts);

			/*Lang::init();
			foreach (self::$_nameSpaces as $value) {
				$params = [];

				$value = array_filter($value) + ['path' => '/'];
				$value += sekf::$defaults;

				// 允许的协议
				if (!empty($value['scheme']) && !in_array($scheme, (array)$value['scheme'])) {
					continue;
				}

				// 允许的 host
				if (!empty($value['host']) && !preg_match('/^'.strtr($value['host'], $strtr). '$/', $host, $matches)) {
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

				//$params = [];
				//$value['scheme'] = $value['scheme'] ? (array) $value['scheme'] : self::$scheme;
				//$value['host'] = $value['host'] ? array_map(function($host){ return preg_quote($host, '');} : $defaultHosts;





			//}


			/*foreach (self::$_routers as $value) {










				/*$params = [];
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


			/*$host = $request->getHost();
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
			empty($break) && $response->addMessage(404);*/
		/*} catch (Exception $e) {
			$response->addMessage(500);
		}*/
	}



	public function __destruct() {
		unset(self::$_this[$this->_key]);
		end(self::$_this);
	}
}
	/*
<<<<<<< HEAD
	public static $_request;
	public static $_response;
	public function request() {
		return self::$_response;
	}
	public function response() {
		return self::$_response;
	}

=======
	private static $_reset = [];

	public static function reset($id, $call) {
		self::$_reset[$id] = $call;
		return true;
	}
>>>>>>> a575566acf86936c7087ef9a24eb9a29dd660b98
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
			$view = new View($value['path'], $view);
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