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
use App\Auth;
use App\User;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;


use Loli\Http\Message\ServerRequest;
use Loli\Http\Message\ServerRequestInput;
use Loli\Http\Message\Header;


use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Response;


class Route extends ArrayObject{

	const TOKEN_HEADER = 'X-Token';

	const TOKEN_COOKIE = 'token';



	const AJAX_HEADER = 'X-Ajax';

	const AJAX_PARAM = 'ajax';


	const PJAX_HEADER = 'X-Pjax';


	const JSON_HEADER = 'X-Json';

	const JSON_PARAM = 'json';

	const JSONP_PARAM = 'callback';

	protected static $callback = [
		'request' => 'static::load',
		'response' => 'static::load',
		'extension' => 'static::load',
		'ajax' => 'static::load',
		'pjax' => 'static::load',
		'json' => 'static::load',
		'jsonp' => 'static::load',
		'token' => 'static::load',
		'csrf' => 'static::load',
		'auth' => 'static::load',
		'user' => 'static::load',
		'ip' => 'static::load',
	];

	public static $rules = [];

	protected static $self;

	public function __construct(ServerRequestInterface $request = NULL, array $data = []) {
		if ($request) {
			$this->request = $request;
		}
		$data && $this->merge($data);
		self::$self = $this;
	}



	public function run() {
		try {
			self::rules();
			$scheme = $this->request->getUri()->getScheme();
			$method = $this->request->getMethod();
			$host = $this->request->getUri()->getHost();
			$path = $this->request->getUri()->getPath();
			if (!$path || $path{0}!== '/') {
				$path = '/' . $path;
			}
			$dirPath = $path;
			if (substr($dirPath, -1, 1) !== '/') {
				$dirPath .= '/';
			}
			foreach (self::$rules as $rule) {
				// 协议
				if (!in_array($scheme, $rule['scheme'], true)) {
					continue;
				}

				// 方法
				if (!in_array($method, $rule['method'], true)) {
					continue;
				}
				// path 路径
				$replace = [];
				foreach ($rule['pathRule'][1] as $name => $pattern) {
					if ($rule['pathRule'][2][$name] !== '' || $rule['pathRule'][3][$name] !== '') {
						$replace['"'.$name.'"'] = '(?:'. preg_quote($rule['pathRule'][2][$name], '/') . $pattern . preg_quote($rule['pathRule'][3][$name], '/') .')' . $rule['pathRule'][4][$name];
					} else {
						$replace['"'.$name.'"'] = $pattern;
					}
				}
				if (!preg_match('/^'. strtr(preg_quote($rule['pathRule'][0], '/'), $replace) . ($rule['isFile'] ? '' :  '\/?') . '$/u', $rule['isFile'] ? $path : $dirPath, $pathMatches)) {
					continue;
				}


				// host 判断
				if ($rule['hostRule']) {
					$continue = true;
					foreach ($rule['hostRule'] as $hostRule) {
						$replace = [];
						foreach ($hostRule[1] as $name => $pattern) {
							if ($hostRule[2][$name] !== '' || $hostRule[3][$name] !== '') {
								$replace['"'.$name.'"'] = '(?:'. preg_quote($hostRule[2][$name], '/') . $pattern . preg_quote($hostRule[3][$name], '/') .')' . $hostRule[4][$name];
							} else {
								$replace['"'.$name.'"'] = $pattern;
							}
						}
						if (preg_match('/^' . strtr(preg_quote($hostRule[0], '/'), $replace). '$/', $host, $hostMatches)) {
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
				if (!$rule['isFile'] && in_array($method, ['GET', 'HEAD']) && substr($rule['path'], -1, 1) === '/' && substr($path, -1, 1) !== '/') {
					throw new Message('Redirect into the directory', 301, ['redirect' => $scheme .'://' . $host . $path . '/' . (($queryString = $this->request->getUri()->getQuery()) ? '?' . $queryString : '')], '', 0);
				}

				$params = $replace = [];
				$matches = $hostMatches + $pathMatches;
				foreach ($rule['match'] as $key => $value) {
					if (isset($matches['_' . $key])) {
						$value = $matches['_' . $key];
						if (!is_numeric($key)) {
							$params[$key] = $matches['_' . $key];
						}
					} elseif (isset($rule['params'][$key])) {
						$value = (string) $rule['params'][$key];
						if (!is_numeric($key)) {
							$params[$key] = to_string($rule['params'][$key]);
						}
					} else {
						$value = '';
					}
					$replace[$key] = $value;
				}


				$controller = [];
				foreach ($rule['controllerRule'] as $controllerRule) {
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
				throw new Message('Controller not exists', 404);
			}

			if (empty($controller[1]) || $controller[1]{0} === '_') {
				throw new Message('Controller not exists', 404);
			}

			$controller[0] = trim(strtr($controller[0], '-.\\', '///'), '/');
			$node = explode('/', implode('/', $controller));

			if (!class_exists($class = 'App\Controllers\\' . strtr($controller[0], '/', '\\'))) {
				throw new Message('Controller not exists', 404);
			}

			// 写入附加属性
			$this->request = $this->request->withAttribute('params', $params);


			$this->controller = $controller;
			$this->node = $node;

			// 控制器
			$class = new $class;		// 中间键
			$middleware = $this->middleware($class, $controller[1]);

			// 读取附加属性
			$params = $this->request->getAttribute('params', []);
			if ($body = $this->request->getParsedBody()) {
				if (is_object($body)) {
					$bodyArray = [];
					foreach ($body as $key => $value) {
						$bodyArray[$key] = $value;
					}
					$params += $bodyArray;
				} else {
					$params += $body;
				}
			}

			$params += array_merge($this->request->getQueryParams(), ($parsedbody = $this->request->getParsedBody()) ? to_array($parsedbody) : [], $this->request->getUploadedFiles(), $params);

			// 中间键 请求
			foreach ($middleware as $value) {
				if ($value->request($params) === false) {
					break;
				}
			}

			// 执行方法
			$view = $class->$controller[1]($params);
		} catch (Message $view) {
			// Message
		} catch (\Exception $e) {
			$view = new Message(['message' => 'exception', 'value' =>  $e->getMessage(), 'code' => $e->getCode()], 500);
		}


		// 数组
		if ($view instanceof ResponseInterface) {
			$this->response = $view;
		} elseif ($view instanceof StreamInterface) {
			$this->response = $this->response->withBody($view);
		} elseif (is_resource($view)) {
			$this->response = $this->response->withBody(new Stream($view));
		} elseif ($view instanceof Message) {

			$data = ['messages' => []];
			$message = $view;
			$headerMessage = [];

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
				$message = $message->getPrevious();
			}
			$data['messages'] = array_reverse($data['messages']);

			$this->response = $this->response->withHeader('Cache-Control', Header::cacheControl(['no-cache' => true, 'max-age' => 0]));
			if ($this->response->getStatusCode() === 200) {
				if ($data['redirect'] && $data['refresh'] !== false && !$data['refresh']) {
					$this->response = $this->response->withStatus(302)->withHeader('Location', $data['redirect']);
				}
			}

			if ($errors = $view->getErrors()) {
				if ($this->response->getStatusCode() === 200) {
					foreach($errors as $status) {
						if ($status >= 400 && $status < 600) {
							$this->response = $this->response->withStatus($status);
						}
					}
				}
				$data['success'] = false;
			} else {
				$data['success'] = true;
			}

			$stream = new Stream(fopen('php://temp', 'r+b'));
			$stream->write((string) new View(['messages/' . $this->response->getStatusCode(), 'messages'], $data));
			$this->response = $this->response->withBody($stream);
		} elseif (is_array($view)) {
			$views = [];
			foreach(explode('/', $this->controller[0] . '/' . $this->controller[1]) as $value) {
				$value = lcfirst($value);
				$views[] = $value;
			}
			$stream = new Stream(fopen('php://temp', 'r+b'));
			$stream->write((string) new View($views, $view));
			$this->response = $this->response->withBody($stream);
		} else {
			$stream = new Stream(fopen('php://temp', 'r+b'));
			$stream->write((string) $view);
			$this->response = $this->response->withBody($stream);
		}

		// 中间键 响应
		if (!empty($middleware)) {
			foreach ($middleware as $value) {
				if ($value->response($view) === false) {
					break;
				}
			}
		}

	}





	protected static function load(Route  $route, $name) {
		switch ($name) {
			case 'request':
				// 请求对象
				return (new ServerRequestInput())->get();
				break;
			case 'response':
				// 响应对象
				return new Response();
				break;
			case 'extension':
				// 后缀
				return strtolower(pathinfo(self::request()->getUri()->getPath(), PATHINFO_EXTENSION));
				break;
			case 'ajax':
				// 是否是ajax
				return self::request()->getHeader(self::AJAX_HEADER) || strtolower($this->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';
				break;
			case 'pjax':
				// 是否是 pjax
				return (bool) self::request()->getHeaderLine(self::PJAX_HEADER);
				break;
			case 'json':
				// 是否是 json
				$request = self::request();
				return $request->getHeader(self::JSON_HEADER) || !empty($request->getQueryParams()[self::JSON_PARAM]) || (($parsedbody = $request->getParsedBody()) && !empty($parsedbody[self::JSON_PARAM])) || self::extension() === 'json';
				break;
			case 'jsonp':
				if (self::json()) {
					$request = self::request();
					$parsedbody = $request->getParsedBody();
					if (isset($parsedbody[self::JSONP_PARAM])) {
						return $parsedbody[self::JSONP_PARAM];
					}
					$queryParams = $request->getQueryParams();
					if (isset($queryParams[self::JSONP_PARAM])) {
						return $queryParams[self::JSONP_PARAM];
					}
				}
				return false;
				break;
			case 'token':
				if ($token = self::request()->getHeaderLine(self::TOKEN_HEADER)) {

				} elseif (($cookieParams = self::request()->getCookieParams()) && !empty($cookieParams[self::TOKEN_COOKIE])) {
					$token = $cookieParams[self::TOKEN_COOKIE];
				}
				return new Token($token);
				break;
			case 'csrf':
				$token = self::token()->get();
				$request = self::request();
				if ($request->getHeaderLine('X-Csrf') === $token) {
					return false;
				}
				$parsedbody = $request->getParsedBody();
				if (isset($parsedbody['_csrf']) && $parsedbody['_csrf'] === $token) {
					return false;
				}
				$queryParams = $request->getQueryParams();
				if (isset($queryParams['_csrf']) && $queryParams['_csrf'] === $token) {
					return false;
				}
				return true;
				break;
			case 'ip':
				$serverParams = self::request()->getServerParams();
				if (!empty($serverParams['REMOTE_ADDR'])) {
					$ip = (string) $serverParams['REMOTE_ADDR'];
				} else {
					$ip = $serverParams['REMOTE_ADDR'];
				}
				return $ip;
				break;
			case 'auth':
				// 验证信息
				if (!$auth = Auth::selectRow(self::token()->get())) {
					$request = self::request();
					$auth = new Auth(['token' => self::token()->get(), 'ip' => $request->getClientAddr(), 'user_id' => 0, 'user_agent' => substr($request->getParam('user_agent', $request->getHeader('User-Agent')), 0, 255)]);
					$auth->insert();
				}
				return $auth;
				break;
			case 'user':
				// 用户信息
				$auth = self::auth();
				$user = new User(['id' => $auth->user_id]);
				if ($user->id) {
					$user->select();
				} else {
					foreach ($auth as $key => $value) {
						if (in_array($key, ['timezone', 'language'], true)) {
							$user->$key = $value;
						}
					}
				}
				return $user;
				break;
			default:
				throw new \BadFunctionCallException( __METHOD__. '(Route, '.$name.') Unregistered route object');
		}
	}









	protected function middleware(Controller $controller, $method = __FUNCTION__) {
		$method = strtolower($method);
		foreach (isset($controller->middleware) ? $controller->middleware : [] as $key => $middleware) {
			if ($is = (strtolower($key) === $method)) {
				break;
			}
		}
		if (empty($is)) {
			$middleware = isset($controller->defaultMiddleware) ? $controller->defaultMiddleware : [];
		}
		$results = [];
		foreach ($middleware as $key => $config) {
			$class = 'App\Middleware\\' . $key;
			$results[] = new $class($config);
		}
		return $results;
	}







	public function __get($name) {
		$value = parent::__get($name);
		if ($value === NULL) {
			if (empty(self::$callback[$name])) {
				throw new \BadFunctionCallException(__METHOD__ . '('. $name .') Unregistered route object');
			}
			$value = call_user_func(self::$callback[$name], $this, $name);
			$this->__set($name, $value);
		}
		return $value;
	}

	public function __call($name, $args) {
		if ($args) {
			$this->__set($name, $args[0]);
		}
		return $this->$name;
	}


	public function __isset($name) {
		return parent::__isset($name) || !empty(self::$callback[$name]);
	}

	public static function __callStatic($name, $args) {
		return self::$self->__call($name, $args);
	}

	public static function get($name = NULL) {
		return $name === NULL ? self::$self : self::$self->$name;
	}

	public static function callback($controller, $callback) {
		self::$callback[$name] = $callback;
		return true;
	}

	protected static function rules() {
		self::$rules = [];
		$defaultHost = empty($_SERVER['LOLI']['route']['hosts']) ? [self::request()->getHeaderLine('Host')] : (array) $_SERVER['LOLI']['route']['hosts'];
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
				if (!$rule = self::parsedRule($value, $route)) {
					$continue = true;
					break;
				}
				$route['hostRule'][$key] = $rule;
			}
			if ($continue) {
				continue;
			}


			if (!$route['pathRule'] = self::parsedRule($route['path'], $route)) {
				continue;
			}

			$route['controllerRule'] = [];
			foreach ($route['controller'] as $value) {
				if (!$rule = self::parsedRule($value, $route)) {
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



	private static function parsedRule($rule, &$route) {
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
}
