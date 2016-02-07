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

class Route extends ArrayObject{

	protected static $callback = [
		'request' => 'Loli\\Route::load',
		'response' => 'Loli\\Route::load',
		'ajaxJS' => 'Loli\\Route::load',
	];

	public static $rules = [];

	protected static $self;

	public function __construct(Request $request = NULL, Response $response = NULL, array $data = []) {
		if ($request) {
			$this->request = $request;
		}

		if ($response) {
			$this->response = $response;
		}

		$data && $this->data($data);

		self::$self = $this;

		self::rules();
	}


	public function __isset($name) {
		return parent::__isset($name) || !empty(self::$callback[$name]);
	}

	public function __get($name) {
		$value = parent::__get($name);
		if ($value === NULL) {
			if (empty(self::$callback[$name])) {
				throw new Exception('Unregistered route object', Message::ERROR);
			}
			$value = call_user_func(self::$callback[$name], $this, $name);
			$this->__set($name, $value);
		}
		return $value;
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
			foreach (self::$rules as $route) {

				// 协议
				if (!in_array($scheme, $route['scheme'], true)) {
					continue;
				}

				// 方法
				if (!in_array($method, $route['method'], true)) {
					continue;
				}

				$replace = [];
				foreach ($route['pathRule'][1] as $name => $pattern) {
					if ($route['pathRule'][2][$name] !== '' || $route['pathRule'][3][$name] !== '') {
						$replace['"'.$name.'"'] = '(?:'. preg_quote($route['pathRule'][2][$name], '/') . $pattern . preg_quote($route['pathRule'][3][$name], '/') .')' . $route['pathRule'][4][$name];
					} else {
						$replace['"'.$name.'"'] = $pattern;
					}
				}
				if (!preg_match('/^'. strtr(preg_quote($route['pathRule'][0], '/'), $replace) . ($route['isFile'] ? '' :  '\/?') . '$/u', $route['isFile'] ? $path : $dirPath, $pathMatches)) {
					continue;
				}

				// host 判断
				if ($route['hostRule']) {
					$continue = true;
					foreach ($route['hostRule'] as $rule) {
						$replace = [];
						foreach ($rule[1] as $name => $pattern) {
							if ($rule[2][$name] !== '' || $rule[3][$name] !== '') {
								$replace['"'.$name.'"'] = '(?:'. preg_quote($rule[2][$name], '/') . $pattern . preg_quote($rule[3][$name], '/') .')' . $rule[4][$name];
							} else {
								$replace['"'.$name.'"'] = $pattern;
							}
						}
						if (preg_match('/^' . strtr(preg_quote($rule[0], '/'), $replace). '$/', $host, $hostMatches)) {
							$continue = false;
							break;
						}
					}
					if ($continue) {
						continue;
					}
				} else {
					$hostMatches = [];
				}

				// 是目录就跳到目录去
				if (!$route['isFile'] && in_array($method, ['GET', 'HEAD']) && substr($route['path'], -1, 1) === '/' && substr($path, -1, 1) !== '/') {
					throw new Message(301, Message::NOTICE, ['redirect' => $scheme .'://' . $host . $path . '/' . (($queryString = merge_string($this->request->getQuerys())) ? '?' . $queryString: '')], '', 0);
				}

				$params = $replace = [];
				$matches = $hostMatches + $pathMatches;
				foreach ($route['match'] as $key => $value) {
					if (isset($matches['_' . $key])) {
						$value = $matches['_' . $key];
						if (!is_numeric($key)) {
							$params[$key] = $matches['_' . $key];
						}
					} elseif (isset($route['default'][$key])) {
						$value = (string) $route['default'][$key];
						if (!is_numeric($key)) {
							$params[$key] = (string) $route['default'][$key];
						}
					} else {
						$value = '';
					}
					$replace[$key] = $value;
				}


				$controller = [];
				foreach ($route['controllerRule'] as $controllerRule) {
					$controllerReplace = [];
					foreach ($controllerRule[2] as $name => $value) {
						$controllerReplace['"' .$name. '"'] = $replace[$name] === '' ? '' : $value . $replace[$name] . $controllerRule[3][$name];
					}
					$controller[] = strtr($controllerRule[0], $controllerReplace);
				}
				$controller[0] = trim(preg_replace('/[\\\\\/>.]+/', '\\', $controller[0]), '\\');
				if ($controller[1] === '') {
					$controller[1] = 'index';
				}
				break;
			}


			if (empty($controller)) {
				throw new Message(404, Message::ERROR);
			}

			if (empty($controller[1]) || $controller[1]{0} === '_') {
				throw new Message(404, Message::ERROR, new Message([1, 'Model not exists'], Message::ERROR));
			}

			$this->controller = $controller;
			$this->controller = [strtr($this->controller[0], '\\', '/'), $this->controller[1]];
			$this->nodes = explode('/', implode('/', $this->controller));


			if (!class_exists($class = 'App\Controllers\\' . strtr($controller[0], '/.', '\\\\'))) {
				throw new Message(404, Message::ERROR, new Message([1, 'Controller not exists'], Message::ERROR));
			}
			$class = new $class(true);

			$params += $this->request->getParams();

			// 执行方法
			$view = $class->$controller[1]($params);

			// 数组
			if (is_array($view)) {
				$view = new View($this->controller[0] . '/' . $this->controller[1], $view);
			}
		} catch (Message $view) {
			// Message
		} catch (HTTP\Exception $e) {
			// HTTP
			$view = new Message([2, $e->getMessage()], Message::ERROR);
			$this->response->setStatus($e->getCode());
		} catch (Cache\Exception $e) {
			// Cache
			$view = new Message([4, $e->getMessage()], Message::ERROR);
			$this->response->setStatus(500);
		} catch (Storage\ConnectException $e) {
			// Storage
			$view = new Message([5, $e->getMessage()], Message::ERROR);
			$this->response->setStatus(500);
		}  catch (Storage\Exception $e) {
			// Storage
			$view = new Message([5, $e->getMessage()], Message::ERROR);
			$this->response->setStatus(500);
		} catch (Database\ConnectException $e) {
			// Database
			$view = new Message([7, $e->getMessage()], Message::ERROR);
			$this->response->setStatus(500);
		} catch (Database\Exception $e) {
			// Database
			$view = new Message([7, $e->getMessage()], Message::ERROR);
			$this->response->setStatus(500);
		} catch (\Exception $e) {
			// 其他
			$view = new Message([1, $e->getMessage()], Message::ERROR);
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
			$view = new View($this->response->getStatus() >= 400 &&  $this->response->getStatus() < 600 ? ['errors/' . $this->response->getStatus(), 'errors', 'messages'] : ['messages'], $data);
		}
		$this->request->getToken();
		if ($this->response->hasMessage()) {
			$view = $this->response->getMessage();
		}

		$this->response->setContent($view);
	}


	public static function __callStatic($name, $args) {
		return self::$self->$name;
	}


	public static function get($name = NULL) {
		return $name === NULL ? self::$self : self::$self->$name;
	}

	public static function callback($controller, $callback) {
		self::$callback[$name] = $callback;
		return true;
	}

	public static function URL(array $controller, array $query = [], $method = 'GET') {
		return new URLRoute($controller, $query, $method);
	}


	private static function _parseRule($rule, &$route) {
		$result = ['', [], [], []];
		$offset = 0;
		while (isset($rule[$offset]) && ($beginOffset = strpos($rule, '{', $offset)) !== false) {
			$endOffset = $beginOffset + 1 + strcspn($rule, '{}', $beginOffset + 1);
			if (!isset($rule[$endOffset])) {
				return false;
			}
			if ($rule[$endOffset] === '{') {
				$beginOffset2 = $endOffset;
				if (($endOffset2 = strpos($rule, '}', $beginOffset2)) === false || ($endOffset = strpos($rule, '}', $endOffset2 + 1)) === false) {
					return false;
				}
				$optional = $rule[$endOffset2-1] === '?';

				$optional2 = $rule[$endOffset-1] === '?';

				$name = substr($rule, $beginOffset2 + 1, $endOffset2 - $beginOffset2 - ($optional ? 2 : 1));
				$result[2][$name] = substr($rule, $beginOffset + 1, $beginOffset2 - $beginOffset - 1);
				$result[3][$name] = substr($rule, $endOffset2 + 1, $endOffset - $endOffset2 - ($optional2 ? 2 : 1));
				$result[4][$name] = $optional2 ? '?' : '';
			} else {
				$optional = $rule[$endOffset-1] === '?';
				$name = substr($rule, $beginOffset + 1, $endOffset - $beginOffset - ($optional ? 2 : 1));
				$result[4][$name] = $result[2][$name] = $result[3][$name] = '';
			}
			if (empty($route['match'][$name])) {
				$route['match'][$name] = '[0-9a-zA-Z_-]+';
			}
			$result[1][$name] = '(?<_' . $name . '>' . str_replace('/', '\\/', $route['match'][$name]) . ')' . ($optional ? '?' : '');
			$result[0] .= substr($rule, $offset, $beginOffset - $offset). '"'. $name. '"';
			$offset = $endOffset + 1;
		}
		$result[0] .= substr($rule, $offset);
		return $result;
	}


	protected static function rules() {
		self::$rules = [];
		$defaultHost = empty($_SERVER['LOLI']['route']['hosts']) ? [self::request()->getHeader('Host')] : (array) $_SERVER['LOLI']['route']['hosts'];
		foreach (empty($_SERVER['LOLI']['route']['rules']) ? [] : $_SERVER['LOLI']['route']['rules'] as $controller => $route) {
			// 模块
			if (empty($route['controller'])) {
				$route['controller'] = $controller;
			}
			if (!is_array($route['controller'])) {
				$route['controller'] = strtr($route['controller'], '.\\@', '/');
				$route['controller'] = ($length = strrpos($route['controller'], '/')) ? [substr($route['controller'], 0, $length), substr($route['controller'], $length + 1)] : [$controller];
			}
			$route['controller'] += [1 => 'index'];
			if (!isset($route['defaults']) || !is_array($route['defaults'])) {
				$route['defaults'] = [];
			}


			// 协议
			if (empty($route['scheme'])) {
				$route['scheme'] = ['http', 'https'];
			} else if (!is_array($route['scheme'])) {
				$route['scheme'] = array_values((array) $route['scheme']);
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
			$route['path'] = ltrim($route['path'], '/');
			if (substr($route['path'] = ltrim($route['path'], '/'), 0, 2) !== '{/') {
				$route['path'] = '/' . $route['path'];
			}

			// 是否是文件
			$route['isFile'] = preg_match('/\.[0-9a-zA-Z_-]\??\}?$/', $route['path']);


			// 匹配
			if (!isset($route['match'])) {
				$route['match'] = [];
			}

			$continue = false;

			// host 规则重写
			$route['hostRule'] = [];
			foreach ($route['host'] as $key => $value) {
				if (!$rule = self::_parseRule($value, $route)) {
					$continue = true;
					break;
				}
				$route['hostRule'][$key] = $rule;
			}
			if ($continue) {
				continue;
			}


			if (!$route['pathRule'] = self::_parseRule($route['path'], $route)) {
				continue;
			}

			$route['controllerRule'] = [];
			foreach ($route['controller'] as $value) {
				if (!$rule = self::_parseRule($value, $route)) {
					$continue = true;
					break;
				}
				$replace = [];
				foreach ($rule[1] as $name => $pattern) {
					if ($rule[2][$name] !== '' || $rule[3][$name] !== '') {
						$replace['"'.$name.'"'] = '(?:'. preg_quote($rule[2][$name], '/') . $pattern . preg_quote($rule[3][$name], '/') .')' . $rule[4][$name];
					} else {
						$replace['"'.$name.'"'] = $pattern;
					}
				}
				$rule[4] = '/^'. strtr(preg_quote($rule[0], '/'), $replace) . '$/';;
				$route['controllerRule'][] = $rule;
			}
			if ($continue) {
				continue;
			}
			self::$rules[] = $route;
		}
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
			case 'ajaxJS':
				// 请求对象
				$result = self::request()->getParam('_token') === self::request()->getToken();
				break;
			default:
				throw new Exception('Unregistered route object');
		}
		return $result;
	}

}