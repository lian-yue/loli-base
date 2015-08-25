<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-04-15 03:40:04
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
/*	Created: UTC 2015-04-06 12:01:29
/*	Updated: UTC 2015-04-15 03:40:04
/*
/* ************************************************************************** */
namespace Loli;
class_exists('Loli\HTTP\Request') || exit;
class Route{

	protected static $callback = [
		'request' => 'Loli\\Route::load',
		'response' => 'Loli\\Route::load',
		'localize' => 'Loli\\Route::load',
		'cache' => 'Loli\\Route::load',
		'session' => 'Loli\\Route::load',
		'storage' => 'Loli\\Route::load',
		'DB' => 'Loli\\Route::load',
	];

	protected static $routes = [];

	public function __construct(Request $request = NULL, Response $response = NULL, array $args = []) {
		if ($request) {
			$this->request = $request;
		}

		if ($response) {
			$this->response = $response;
		}

		foreach ($args as $name => $value) {
			$this->$name = $value;
		}

		if (!self::$routes) {
			if (!empty($_SERVER['LOLI']['ROUTE']['host'])) {
				$defaultHost = '/^' . preg_quote($this->request->getHeader('Host'), '/') . '$/';
			} else {
				$defaultHost = (array) $_SERVER['LOLI']['ROUTE']['host'];
				$defaultHost = array_map(function($host) {
					if ($host !== '*') {
						$host = preg_quote($host, '/');
						if (substr($host, 0, 2) === '\\*') {
							$host = '*' . substr($host, 2);
						}
					}
					return $host;

				}, $defaultHost);
				$defaultHost = '/^(?:' . implode('|', $defaultHost) . ')$/';
			}
			foreach (empty($_SERVER['LOLI']['ROUTE']['file']) ? [] : require $_SERVER['LOLI']['ROUTE']['file'] as $route => $controller) {
				if ($route{0} === '/') {
					$path = $route;
					$method = empty($_SERVER['LOLI']['ROUTE']['method']) ? ['GET', 'POST'] : (is_array($_SERVER['LOLI']['ROUTE']['method']) ? $_SERVER['LOLI']['ROUTE']['method'] : preg_split('/(,|\||\s+)/', $_SERVER['LOLI']['ROUTE']['method'], -1, PREG_SPLIT_NO_EMPTY));
					$host = $defaultHost;
				} else {
					$route = explode('/', $route, 2) + [1 => ''];

					// host 自定义
					if (stripos($route[0], '.') !== false || stripos($route[0], '(') !== false || stripos($route[0], '*') !== false || stripos($route[0], '\\') !== false  || strtoupper($route[0]) !== $route[0]) {
						$host = '/^' . str_replace('/', '\\/', $route[0]) . '$/';
						$route = explode('/', $route, 2) + [1 => ''];
					}
					$method = preg_split('/(,|\||\s+)/', $route[0], -1, PREG_SPLIT_NO_EMPTY);
					$host = $defaultHost;
					$path = '/' . $route[1];
				}
				$path = '/^'. str_replace('/', '\\/', $path) . '$/';
				if (is_object($controller)) {
				} elseif (is_array($controller)) {
					if (is_string($controller[0])) {
						$controller[0] = str_replace('/', '\\', $controller[0]);
					}
					$controller += [1=> 'index'];
				} else {
					$controller = preg_split('/(\s+|\:+|-\>|@|\>)/', $controller, 2, PREG_SPLIT_NO_EMPTY) + [1 => 'index'];
					$controller[0] = str_replace('/', '\\', $controller[0]);
				}
				self::$routes[] = ['method' => $method, 'path' => $path, 'host' => $host, 'controller' => $controller];
			}
		}
	}

	public function __get($name) {
		if (!isset($this->$name)) {
			if (empty(self::$callback[$name])) {
				$this->$name = NULL;
			} else {
				$this->$name = call_user_func(self::$callback[$name], $this, $name);
			}
		}
		return $this->$name;
	}

	public function __set($name, $value) {
		$this->$name = $value;
	}



	public function __destruct() {
		foreach ($this as $name => $value) {
			unset($this->$name);
		}
	}

	public function __invoke() {
		try {
			$path = $this->request->getPath();
			$method = $this->request->getMethod();
			$host = $this->request->getHeader('Host');
			foreach (self::$routes as $route) {
				if (!in_array($method, $route['method'], true)) {
					continue;
				}
				if (!preg_match($route['path'], $path, $matches)) {
					continue;
				}
				if (!preg_match($route['host'], $host, $matches2)) {
					continue;
				}
				$matches += $matches2;
				foreach($matches as $key => $value) {
					if (is_int($key)) {
						unset($matches[$key]);
						continue;
					}
				}
				$controller = $route['controller'];
				if (is_object($controller) && is_object($controller[0])) {
					if ($controller = call_user_func($controller, $matches, $this)) {
						continue;
					}
					if (is_string($controller)) {
						$controller = preg_split('/(\s+|\:+|-\>|@|\>)/', $controller, 2, PREG_SPLIT_NO_EMPTY);
					}
					$controller[0] = str_replace('/', '\\', $controller[0]);
					$controller += [1 => 'index'];
				}

				$replace = [];
				foreach ($matches as $key => $value) {
					$replace['$'.$key] = $value;
				}
				foreach ($controller as &$mom) {
					if (is_string($mom)) {
						$mom = strtr($mom, $replace);
					}
				}
				break;
			}


			if (empty($controller)) {
				throw new Message(404, Message::ERROR);
			}

			if (is_object($controller[0])) {
				if (substr($class = get_class($controller[0]), 0, 11) !== 'Controller\\') {
					throw new Message(500, Message::ERROR, new Message([1, 'Class object not controller'], Message::ERROR));
				}
				$this->controller = substr($class, 11);
			} else {
				if (!class_exists($class = 'Controller\\' . $controller[0])) {
					throw new Message(404, Message::ERROR, new Message([1, 'Controller not exists'], Message::ERROR));
				}
				$this->controller = substr($class, 11);
				$controller[0] = new $class($this);
			}
			$this->method = $controller[1];

			// RBAC 权限
			if ($this->RBAC && !$this->RBAC->has($this->controller, $name)) {
				$this->response->setStatus(403);
				throw new Message([90, 'RBAC'], Message::ERROR);
			}

			// 执行方法
			$view = call_user_func($controller, $matches);

			// 返回的是 RouteInterface
			if ($view instanceof RouteInterface) {
				$view->route($this);
			}
		} catch (Message $view) {
			// Message
			$view->route($this);
		} catch (HTTP\Exception $e) {
			// HTTP
			$view = new Message([2, $e->getMessage()], Message::ERROR);
			$view->route($this);
			$this->response->setStatus($e->getCode());
		} catch (Cache\Exception $e) {
			// Cache
			$view = new Message([4, $e->getMessage()], Message::ERROR);
			$view->route($this);
			$this->response->setStatus(500);
		} catch (DB\Exception $e) {
			// DB
			$view = new Message([5, $e->getMessage()], Message::ERROR);
			$view->route($this);
			$this->response->setStatus(500);
		} catch (Storage\Exception $e) {
			// Storage
			$view = new Message([6, $e->getMessage()], Message::ERROR);
			$view->route($this);
			$this->response->setStatus(500);
		} catch (\Exception $e) {
			// 其他
			$view = new Message([99, $e->getMessage()], Message::ERROR);
			$view->route($this);
			$this->response->setStatus(500);
		}



		//  消息对象
		if ($view instanceof Message) {
			$data = ['messages' => []];
			$message = $view;
			while ($message) {
				$data += $message->getData();
				$data['messages'][] = $message;
				if (!isset($data['redirect'])) {
					$data['redirect'] = $message->getRedirect();
					$data['refresh'] = $message->getRefresh();
				}
				if (!isset($data['refresh'])) {
					$data['refresh'] = $message->getRefresh();
				}
				$this->response->addHeader('X-Message', $message->getCode() . '.' . $message->getType());

				$message = $message->getPrevious();
			}
			$data['messages'] = array_reverse($data['messages']);


			$this->response->addCache('no-cache', 0);
			if ($this->response->getStatus() === 200) {
				if (!empty($data['redirect']) && $data['redirect'] !== true && $data['refresh'] !== false && !$data['refresh']) {
					$this->response->setStatus(302);
					$this->response->addHeader('Location', $data['redirect'], false);
				} else {
					// 是 200-599 的状态码 设置 http 状态码
					if (is_int($code = $view->getCode()) && $code >= 200 && $code < 600) {
						$this->response->setStatus($code);
					}
				}
			}

			$view = new View('messages', $data);
			$view->route($this);
		}

		$this->response->setContent($view);
	}



	public static function setCallback($name, $callback) {
		self::$callback[$name] = $callback;
		return true;
	}


	protected static function load($route, $name) {
		switch ($name) {
			case 'request':
				// 请求对象
				$result = new HTTP\Request;
				break;
			case 'response':
				// 响应对象
				$result = new HTTP\Response($route->request);
				break;
			case 'localize':
				// 本地化模块
				$result = new Localize(empty($_SERVER['LOLI']['LOCALIZE']['language']) ? false : $_SERVER['LOLI']['LOCALIZE']['language'], empty($_SERVER['LOLI']['LOCALIZE']['timezone']) ? false : $_SERVER['LOLI']['LOCALIZE']['timezone']);
				foreach ($route->request->getAcceptLanguages() as $language) {
					if ($result->setLanguage($language)) {
						break;
					}
				}
				break;
			case 'storage':
				// 储存模块
				$class = __NAMESPACE__ . '\Storage\\' . (empty($_SERVER['LOLI']['STORAGE']['type']) ? 'Local' : $_SERVER['LOLI']['STORAGE']['type']);
				$result = new $class($_SERVER['LOLI']['STORAGE']);
				break;
			case 'cache':
				// 缓存模块
				$class = __NAMESPACE__ . '\Cache\\' . (empty($_SERVER['LOLI']['CACHE']['type']) ? 'File' : $_SERVER['LOLI']['CACHE']['type']);
				$result = new $class(empty($_SERVER['LOLI']['CACHE']['args']) ? [] : $_SERVER['LOLI']['CACHE']['args'], empty($_SERVER['LOLI']['CACHE']['key']) ? '' : $_SERVER['LOLI']['CACHE']['key']);
				break;
			case 'session':
				// 缓存模块
				$result = new Session($this->cache, $route->request);
				break;
			case 'DB':
				// 数据库模块
				static $protocol = [
					'mysql' => ['mysql', 'MySQLi'],
					'maria' => ['mysql', 'MySQLi'],
					'mariadb' => ['mysql', 'MySQLi'],

					'postgresql' => ['pgsql', 'PGSQL'],
					'pgsql' => ['pgsql', 'PGSQL'],
					'pg' => ['pgsql', 'PGSQL'],

					'sqlserver' => ['mssql', 'MSSQL'],
					'mssql' => ['mssql', 'MSSQL'],

					'sqlite' => ['sqlite', 'SQLite'],

					'mongo' => ['mongo', 'Mongo'],
					'mongodb' => ['mongo', 'Mongo'],

					'oci' => ['oci', 'OCI'],
					'oracle' => ['oci', 'OCI'],

					'odbc' => ['odbc', 'ODBC'],
				];
				// $servers
				if (empty($server['protocol'])) {
					$server['protocol'] = 'mysql';
				}
				$class = __NAMESPACE__.'\\DB';
				if (class_exists('PDO') && in_array($servers['protocol'], \PDO::getAvailableDrivers())) {
					$class .= 'PDO';
				} elseif (isset($protocol[$servers['protocol']])) {
					$class .= $protocol[$servers['protocol']];
				} else {
					$class .= ucwords($servers['protocol']);
				}
				$result = new $class($servers, $route->cache);
				break;
			default:
				$result = false;
		}
		return $result;
	}
}




