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
namespace Loli;
use ArrayAccess, Loli\Crypt\RSA;
class_exists('Loli\HTTP\Request') || exit;
class Route implements ArrayAccess{

	protected static $callback = [
		'request' => 'Loli\\Route::load',
		'response' => 'Loli\\Route::load',
		'localize' => 'Loli\\Route::load',
		'session' => 'Loli\\Route::load',
		'DB' => 'Loli\\Route::load',
		'table' => 'Loli\\Route::load',
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
			$defaultHost = empty($_SERVER['LOLI']['ROUTE']['host']) ? [] : (array) $_SERVER['LOLI']['ROUTE']['host'];
			foreach (empty($_SERVER['LOLI']['ROUTE']['file']) ? [] : require $_SERVER['LOLI']['ROUTE']['file'] as $model => $route) {
				// 模块
				if (empty($route['model'])) {
					$route['model'] = $model;
				}
				if (!is_array($route['model'])) {
					$route['model'] = ($length = strrpos($route['model'], '.')) ? [substr($route['model'], 0, $length), substr($route['model'], $length + 1)] : [$model];
				}
				$route['model'] += [1 => 'index'];


				// 协议
				if (empty($route['scheme'])) {
					$route['scheme'] = ['https', 'http'];
				} else if (!is_array($route['scheme'])) {
					$route['scheme'] = (array) $route['scheme'];
				}

				// 方法
				if (empty($route['method'])) {
					$route['method'] = ['GET', 'POST'];
				} elseif (!is_array($route['method'])) {
					$route['method'] = preg_split('/(\.|\| )/', $route['method'], -1, PREG_SPLIT_NO_EMPTY);
				}
				if (in_array('GET', $route['method'], true)) {
					$route['method'][] = 'HEAD';
				}


				// 域名
				if (!isset($route['host'])) {
					$route['host'] = $defaultHost;
				} elseif (!is_array($route['host'])) {
					$route['host'] = (array) $route['host'];
				}

				// 路径
				if (!isset($route['path'])) {
					continue;
				}

				$route['path'] = '/' . ltrim($route['path'], '/');


				// 是否是文件
				$route['isFile'] = preg_match('/\.[0-9a-zA-Z_-]$/', $route['path']);

				$continue = false;

				// host 规则重写
				$route['hostMatch'] = [];
				foreach ($route['host'] as $key => $value) {
					$array = ['', [], []];
					$offset = 0;
					while (isset($value[$offset]) && ($beginOffset = strpos($value, '{', $offset)) !== false) {
						$endOffset = strpos($value, '}', $beginOffset);
						if ($endOffset === false) {
							$continue = true;
							break;
						}
						$optional = $value[$endOffset-1] === '?';
						$name = substr($value, $beginOffset + 1, $endOffset - $beginOffset - ($optional? 2 : 1));


						if (empty($route['match'][$name])) {
							$route['match'][$name] = '[0-9a-zA-Z_-]+';
						}
						$array[2][$name] = $optional && isset($value[$endOffset + 1]) && $value[$endOffset + 1] === '.' && ($beginOffset === 0 || $value[$beginOffset - 1] === '.') ? '.' : '';
						$array[1][$name] = '(?<_' . $name .'>(?:'. str_replace('/', '\\/', $route['match'][$name]) . ')' . ($optional ? '?' : '') .')';
						$array[0] .= substr($value, $offset, $beginOffset - $offset) . '"'. $name. '"';
						$offset = $endOffset + ($array[2][$name] ? 2 : 1);
					}
					if ($continue) {
						break;
					}
					$array[0] .= substr($value, $offset);
					$route['hostMatch'][] = $array;
				}
				if ($continue) {
					continue;
				}


				// path 规则重写
				$route['pathMatch'] = ['', [], []];
				$offset = 0;
				while (isset($route['path'][$offset]) && ($beginOffset = strpos($route['path'], '{', $offset)) !== false) {
					$endOffset = strpos($route['path'], '}', $beginOffset);
					if ($endOffset === false) {
						$continue = true;
						break;
					}
					$optional = $route['path'][$endOffset-1] === '?';
					$name = substr($route['path'], $beginOffset + 1, $endOffset - $beginOffset - ($optional? 2 : 1));

					if (empty($route['match'][$name])) {
						$route['match'][$name] = '[0-9a-zA-Z_-]+';
					}

					$route['pathMatch'][2][$name] = $optional && in_array($route['path'][$beginOffset - 1], ['-', '/'], true) && (!isset($route['path'][$endOffset + 1]) || $route['path'][$endOffset + 1] === $route['path'][$beginOffset - 1]) ? $route['path'][$beginOffset - 1] : '';
					$route['pathMatch'][1][$name] = '(?<_'. $name .'>(?:'. str_replace('/', '\\/', $route['match'][$name]) . ')' . ($optional ? '?' : '') .')';
					$route['pathMatch'][0] .= substr($route['path'], $offset, $beginOffset - ($route['pathMatch'][2][$name] ? $offset + 1 : $offset)) . '"'. $name. '"';
					$offset = $endOffset + 1;
				}
				if ($continue) {
					continue;
				}

				$route['pathMatch'][0] .= rtrim(substr($route['path'], $offset), '/');

				//  模块
				foreach ($route['model'] as $key => $value) {
					$offset = 0;

					$pattern = '';
					while (isset($value[$offset]) && ($beginOffset = strpos($value, '{', $offset)) !== false) {
						$endOffset = strpos($value, '}', $beginOffset);
						if ($endOffset === false) {
							$continue = true;
							break;
						}
						$optional = $value[$endOffset-1] === '?';
						$name = substr($value, $beginOffset + 1, $endOffset - $beginOffset - ($optional? 2 : 1));
						if (empty($route['match'][$name])) {
							$route['match'][$name] = '[0-9a-zA-Z_-]+';
						}
						$patternPart = '(?<_' . $name .'>(?:'. str_replace('/', '\\/', $route['match'][$name]) .')' . ($optional ? '?' : '') .')';
						$startChar = $optional && $beginOffset !== 0 && $value[$beginOffset - 1] === '.' && (!isset($value[$endOffset + 1]) || $value[$endOffset + 1] === '.') ? '.' : '';
						if ($startChar) {
							$patternPart = '(?:\\' . $startChar. $patternPart .')?';
						}
						$pattern .= substr($value, $offset, $beginOffset - ($startChar ? $offset + 1 : $offset)) . $patternPart;
						$offset = $endOffset + 1;
					}
					if ($continue) {
						break;
					}
					$pattern .= substr($value, $offset);
					$route['modelMatch'][] = $pattern;
				}

				if ($continue) {
					continue;
				}

				self::$routes[] = $route;
			}
		}
	}

	public function __invoke() {
		try {
			$scheme = $this->request->getScheme();
			$method = $this->request->getMethod();
			$host = $this->request->getHeader('Host');
			$path = $this->request->getPath();
			$dirPath = $path;
			if (substr($dirPath, -1, 1) !== '/') {
				$dirPath .= '/';
			}
			foreach (self::$routes as $route) {

				// 协议
				if (!in_array($scheme, $route['scheme'], true)) {
					continue;
				}

				// 方法
				if (!in_array($method, $route['method'], true)) {
					continue;
				}

				// host 判断
				if ($route['hostMatch']) {
					$continue = true;
					foreach ($route['hostMatch'] as $hostMatch) {
						$strtr = [];
						foreach ($hostMatch[1] as $name => $value) {
							$strtr['"'. $name . '"'] = $hostMatch[2][$name] ? '(?:'. $value .'\\'. $hostMatch[2][$name] .')?' : $value;
						}
						if (preg_match('/^' . strtr(preg_quote($hostMatch[0], '/'), $strtr). '$/', $host, $matches)) {
							$continue = false;
							break;
						}
					}
					if ($continue) {
						continue;
					}
				} else {
					$matches = [];
				}

				$strtr = [];
				foreach ($route['pathMatch'][1] as $name => $value) {
					$strtr['"'. $name . '"'] = $route['pathMatch'][2][$name] ? '(?:\\'. $route['pathMatch'][2][$name] . $value. ')?' : $value;
				}

				if (!preg_match('/^' . strtr(preg_quote($route['pathMatch'][0], '/'), $strtr) . ($route['isFile'] ? '' :  '\/?'). '$/', $path, $matches2)) {
					continue;
				}

				// 是目录就跳到目录去
				if (!$route['isFile'] && in_array($method, ['GET', 'HEAD']) && substr($route['path'], -1, 1) === '/' && substr($path, -1, 1) !== '/') {
					throw new Message(301, Message::NOTICE, ['redirect' => $scheme .'://' . $host . $path . '/' . (($queryString = merge_string($this->request->getQuerys())) ? '?' . $queryString: '')], '', 0);
				}


				$params = $replace = [];
				foreach ($matches + $matches2 as $key => $value) {
					if (!is_int($key)) {
						$key = substr($key, 1);
						$replace['{'. $key . '?}'] = $replace['{'. $key . '}'] = $value;
						if (!is_numeric($key)) {
							$params[$key] = $value;
						}
					}
				}
				foreach ($route['match'] as $key => $value) {
					if (!isset($replace['{'. $key . '}'])) {
						$replace['{'. $key . '?}'] = $replace['{'. $key . '}'] = '';
					}
				}

				$model = $route['model'];

				unset($value);
				foreach ($model as &$value) {
					$value = strtr($value, $replace);
				}
				$model[0] = trim(preg_replace('/[\\\\\/>.-]+/', '\\', $model[0]), '\\');
				if ($model[1] === '') {
					$model[1] = 'index';
				}
				break;
			}


			if (empty($model)) {
				throw new Message(404, Message::ERROR);
			}

			if (empty($model[1]) || $model[1]{0} === '_') {
				throw new Message(404, Message::ERROR, new Message([1, 'Model not exists'], Message::ERROR));
			}

			if (!class_exists($class = 'Model\\' . $model[0])) {
				throw new Message(404, Message::ERROR, new Message([1, 'Model not exists'], Message::ERROR));
			}

			$params += $this->request->getParams();

			$this->model = $model;

			$model[0] = new $class($this, true);

			// 执行方法
			$view = call_user_func($model, $params);

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
		} catch (Storage\Exception $e) {
			// Storage
			$view = new Message([6, $e->getMessage()], Message::ERROR);
			$view->route($this);
			$this->response->setStatus(500);
		} catch (DB\Exception $e) {
			// DB
			$view = new Message([8, $e->getMessage()], Message::ERROR);
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

		if ($this->response->hasMessage()) {
			$view = [$this->response, 'getMessage'];
		}

		$this->response->setContent($view);
	}




	public function __call($name, $args) {
		return call_user_func_array($this->$name, $args);
	}

	public function __get($name) {
		if (empty(self::$callback[$name])) {
			throw new Message('Unregistered route object', Message::ERROR);
		} else {
			$this->$name = call_user_func(self::$callback[$name], $this, $name);
		}
		return $this->$name;
	}

	public function __isset($name) {
		return isset($this->$name) || !empty(self::$callback[$name]);
	}

	public function offsetExists($name) {
		return $this->__isset($name);
	}

	public function offsetGet($name) {
		return $this->$name;
	}

	public function offsetSet($name, $value) {
		return $this->$name = $value;
	}

	public function offsetUnset($name) {
		unset($this->$name);
	}

	public function __destruct() {
		foreach ($this as $name => $value) {
			unset($this->$name);
		}
	}



	public static function callback($model, $callback) {
		self::$callback[$name] = $callback;
		return true;
	}

	public static function URL(array $model, array $params = [], $method = false) {

		return true;
	}


	protected static function load(Route $route, $name) {
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
				// 本地化
				$result = new Localize(empty($_SERVER['LOLI']['LOCALIZE']['language']) ? false : $_SERVER['LOLI']['LOCALIZE']['language'], empty($_SERVER['LOLI']['LOCALIZE']['timezone']) ? false : $_SERVER['LOLI']['LOCALIZE']['timezone']);
				foreach ($route->request->getAcceptLanguages() as $language) {
					if ($result->setLanguage($language)) {
						break;
					}
				}
				if ($route->request->getCookie('language')) {
					$result->setLanguage($route->request->getCookie('language', ''));
				}
				if ($route->request->getParam('language')) {
					$result->setLanguage($route->request->getParam('language', ''));
				}

				if ($route->request->getCookie('timezone')) {
					$result->setLanguage($route->request->getCookie('timezone', ''));
				}
				if ($route->request->getParam('timezone')) {
					$result->setLanguage($route->request->getParam('timezone', ''));
				}
				break;
			case 'session':
				// Session
				$result = new Session($route->request->getToken());
				break;
			case 'DB':
				// 数据库
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

				$servers = empty($_SERVER['LOLI']['DB']) ? [] : $_SERVER['LOLI']['DB'];
				$server = reset($servers);
				if (empty($server['protocol'])) {
					$server['protocol'] = 'mysql';
				}
				$class = __NAMESPACE__.'\\DB\\';
				if (class_exists('PDO') && in_array($server['protocol'], \PDO::getAvailableDrivers())) {
					$class .= 'PDO';
				} elseif (isset($protocol[$server['protocol']])) {
					$class .= $protocol[$server['protocol']][1];
				} else {
					$class .= ucwords($server['protocol']);
				}
				$result = new $class($servers);
				break;
			case 'table':
				$result = new TableObject($route, 'Table');
				break;
			default:
				throw new Message('Unregistered route object', Message::ERROR);
		}
		return $result;
	}
}