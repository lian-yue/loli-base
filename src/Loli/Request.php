<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-06 14:16:56
/*	Updated: UTC 2015-02-23 15:41:36
/*
/* ************************************************************************** */
namespace Loli;
class Request{


	private static $_g, $_postsLength = 2097152, $_defaultHost = 'localhost', $_methodsList = ['OPTIONS', 'HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE'];


	const TOKEN_HEADER = 'X-Token';

	const TOKEN_COOKIE = 'token';

	const AJAX_HEADER = 'X-Ajax';

	const AJAX_PARAM = 'ajax';

	private $_scheme = 'http';

	private $_version = 1.1;

	private $_method = 'GET';

	private $_URI = '/';

	private $_path = '/';

	private $_querys = [];

	private $_headers = [];

	private $_cookies = [];

	private $_posts = [];

	private $_params = [];

	private $_files = [];

	private $_content = false;

	private $_IP = false;

	private $_token = null;

	private $_newToken = false;

	private $_ajax = null;

	private $_ranges = null;

	private $_mobile = null;

	private $_API = null;



	public static function start() {
		if (!self::$_g) {
			self::$_g = array_intersect_key($GLOBALS, ['_SERVER' => '', '_GET' => '', '_POST' => '', '_COOKIE' => '', '_FILES' => '', '_ENV' => '', '_SESSION' => '']);
		}
	}



	public static function defaultScheme() {
		if (isset(self::$_g['_SERVER']['REQUEST_SCHEME'])) {
			$scheme = strtolower(self::$_g['_SERVER']['REQUEST_SCHEME']);
		} elseif (isset(self::$_g['_SERVER']['HTTPS']) && ('on' == strtolower(self::$_g['_SERVER']['HTTPS']) || '1' == self::$_g['_SERVER']['HTTPS'])) {
			$scheme = 'https';
		} elseif (isset(self::$_g['_SERVER']['SERVER_PORT']) && '443' == self::$_g['_SERVER']['SERVER_PORT']) {
			$scheme = 'https';
		} elseif (isset(self::$_g['_SERVER']['SERVER_PORT_SECURE']) && '1' == self::$_g['_SERVER']['SERVER_PORT_SECURE']) {
			$scheme = 'https';
		} else {
			$scheme = 'http';
		}
		return $scheme;
	}

	// 默认版本
	public static function defaultVersion() {
		return isset(self::$_g['_SERVER']['SERVER_PROTOCOL']) && self::$_g['_SERVER']['SERVER_PROTOCOL'] == 'HTTP/1.0' ? 1.0 : 1.1;
	}



	// 默认方法
	public static function defaultMethod() {
		if (isset(self::$_g['_SERVER']['REQUEST_METHOD'])) {
			$method = self::$_g['_SERVER']['REQUEST_METHOD'];
		} else {
			$method = 'GET';
		}
		if (!in_array($method = strtoupper($method), self::$_methodsList)) {
			$method = 'GET';
		}
		return $method;
	}

	// 默认 URL
	public static function defaultURI() {
		if (isset(self::$_g['_SERVER']['REQUEST_URI'])) {
			$URI = self::$_g['_SERVER']['REQUEST_URI'];
		} elseif (isset(self::$_g['_SERVER']['HTTP_X_ORIGINAL_URL'])) {
			$parse = parse_url(self::$_g['_SERVER']['HTTP_X_ORIGINAL_URL']);
			$URI = urlencode(empty($parse['path']) ? '/' : $parse['path']) . (empty($parse['query']) ? '' : '?' . merge_string(parse_string($parse['query'])));
		} elseif (isset(self::$_g['_SERVER']['PATH_INFO']) && isset(self::$_g['_SERVER']['SCRIPT_NAME'])) {
			if (self::$_g['_SERVER']['PATH_INFO'] == self::$_g['_SERVER']['SCRIPT_NAME']) {
				$URI = self::$_g['_SERVER']['PATH_INFO'];
			} else {
				$URI = self::$_g['_SERVER']['SCRIPT_NAME'] . self::$_g['_SERVER']['PATH_INFO'];
			}
		} else {
			$URI = '/';
		}
		return '/' . ltrim($URI, '/');
	}



	// 默认 headers 头
	public static function defaultHeaders() {
		if (function_exists('getallheaders')) {
			$headers = getallheaders();
		} elseif (function_exists('http_get_request_headers')) {
			$headers = http_get_request_headers();
		} else {
			$headers = [];
			foreach (self::$_g['_SERVER'] as $name => $value) {
				if (substr($name, 0, 5) === 'HTTP_') {
					$headers[strtr(ucwords(strtolower(strtr(substr($name, 5), '_', ' '))), ' ', '-')] = $value;
				}
			}
			if (!empty(self::$_g['_SERVER']['CONTENT_TYPE'])) {
				$headers['Content-Type'] = self::$_g['_SERVER']['CONTENT_TYPE'];
			}
			if (!empty(self::$_g['_SERVER']['CONTENT_LENGTH']) && self::$_g['_SERVER']['CONTENT_LENGTH'] > 0) {
				$headers['Content-Length'] = self::$_g['_SERVER']['CONTENT_LENGTH'];
			}
		}
		if (!empty($headers['Content-Type'])) {
			$headers['Content-Type'] = strtolower(trim(explode(';', $headers['Content-Type'], 2)[0]));
		}

		if (empty($headers['Host'])) {
			if (isset(self::$_g['_SERVER']['HTTP_HOST'])) {
				$headers['Host'] = self::$_g['_SERVER']['HTTP_HOST'];
			}  elseif (isset(self::$_g['_SERVER']['SERVER_NAME'])) {
				$headers['Host'] = self::$_g['_SERVER']['SERVER_NAME'];
				if (isset(self::$_g['_SERVER']['SERVER_PORT']) && !in_array(self::$_g['_SERVER']['SERVER_PORT'], ['80', '443'])) {
					$headers['Host'] = ':' . self::$_g['_SERVER']['SERVER_PORT'];
				}
			} else {
				$headers['Host'] = self::$_defaultHost;
			}
		}
		unset($headers['X-Original-Url']);
		return $headers;
	}


	// 默认内容数据
	public static function defaultPosts() {
		static $posts;
		if (!isset($posts)) {
			if (empty(self::$_g['_SERVER']['CONTENT_TYPE']) || empty(self::$_g['_SERVER']['CONTENT_LENGTH']) || self::$_g['_SERVER']['CONTENT_LENGTH'] < 1 || self::$_g['_SERVER']['CONTENT_LENGTH'] > self::$_postsLength) {
				$posts = self::$_g['_POST'];
			} elseif (in_array($method = self::defaultMethod(), ['POST', 'PUT', 'PATCH', 'DELETE']) && in_array(self::$_g['_SERVER']['CONTENT_TYPE'], ['application/json', 'text/json'])) {
				$posts = ($jsons = json_decode(trim(file_get_contents('php://input', 'rb')), true)) ? $jsons : [];
			} elseif (in_array($method, ['PUT', 'PATCH', 'DELETE']) && strpos(self::$_g['_SERVER']['CONTENT_TYPE'], 'application/x-www-form-urlencoded') !== false) {
				$posts = ($arrays = parse_string(trim(file_get_contents('php://input', 'rb')), true)) ? $arrays : [];
			} else {
				$posts = self::$_g['_POST'];
			}
		}
		return $posts;
	}


	// 默认文件
	public static function defaultFiles() {
		$files = [];
		foreach (self::$_g['_FILES'] as $key => $value) {
			$files[$key] = self::_files($value['name'], $value['type'], $value['tmp_name'], $value['error'], $value['size']);
		}
		return $files;
	}

	private static function _files($name, $type, $tmp_name, $error, $size) {
		$files = [];
		if (is_array($name)) {
			foreach ($name as $key => $value) {
				foreach (self::_files($name[$key], $type[$key], $tmp_name[$key], $error[$key], $size[$key]) as $k => $v) {
					$files[$k] = array_merge(empty($files[$k]) ? [] : $files[$k], $v);
				}
			}
		} else {
			$files = ['name' => [$name], 'type' => [$type], 'tmp_name' => [$tmp_name], 'error' => [$error], 'size' => [$size]];
		}
		return $files;
	}



	// 默认内容
	public static function defaultContent() {
		if (empty(self::$_g['_SERVER']['CONTENT_TYPE']) || self::$_g['_SERVER']['CONTENT_TYPE'] != 'multipart/form-data' || empty(self::$_g['_SERVER']['CONTENT_LENGTH']) || self::$_g['_SERVER']['CONTENT_LENGTH'] < 1) {
			return false;
		}
		return 'php://input';
	}



	// 默认IP
	public static function defaultIP() {
		$IP = isset(self::$_g['_SERVER']['REMOTE_ADDR']) ? self::$_g['_SERVER']['REMOTE_ADDR'] : '127.0.0.1';
		if (empty($_SERVER['LOLI']['IP'])) {

		} elseif (isset(self::$_g['_SERVER']['HTTP_CLIENT_IP']) && filter_var(self::$_g['_SERVER']['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
			$IP = self::$_g['_SERVER']['HTTP_CLIENT_IP'];
		} elseif (isset(self::$_g['_SERVER']['HTTP_X_FORWARDED_FOR'])) {
			foreach (explode(',', self::$_g['_SERVER']['HTTP_X_FORWARDED_FOR']) as $v) {
				$v = trim($v);
				if (filter_var($v, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) && (empty(self::$_g['_SERVER']['SERVER_ADDR']) || self::$_g['_SERVER']['SERVER_ADDR'] != $v)) {
					$IP = $v;
					break;
				}
			}
		}

		if ($IP) {
			$IP = inet_ntop(inet_pton($IP));
			// 兼容请求地址
			if (preg_match('/\:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/', $IP, $matches)) {
				$IP = $matches[1];
			}
		}
		return $IP;
	}



	public function getScheme() {
		return $this->_scheme;
	}

	public function setScheme($scheme) {
		if (!in_array($scheme, ['http', 'https'])) {
			if (!in_array($method = strtoupper($method), self::$_methodsList)) {
				throw new Exception('Set scheme:' . $method);
			}
		}
		$this->_scheme = $scheme;
		$_SERVER['REQUEST_SCHEME'] = $scheme;
		if ($scheme == 'https') {
			$_SERVER['HTTPS'] = 'on';
		} else {
			unset($_SERVER['HTTPS']);
		}
		return $this;
	}

	public function getVersion() {
		return $this->_version;
	}

	public function setVersion($version) {
		$this->_version = $version == 1.0 ? 1.0 : 1.1;
		$_SERVER['SERVER_PROTOCOL'] = 'HTTP/' . ($this->_version == 1.0 ? '1.0' : '1.1');
		return $this;
	}

	// 获方法
	public function getMethod() {
		return $this->_method;
	}

	// 设置方法
	public function setMethod($method) {
		if (!in_array($method = strtoupper($method), self::$_methodsList)) {
			throw new Exception('Set token:' . $method);
		}
		$this->_method = $method;
		$_SERVER['REQUEST_METHOD'] = $method;
		return $this;
	}

	public function getHost() {
		return $this->getHeader('Host');
	}

	public function setHost($host) {
		return $this->setHeader('Host', $host);
	}




	public function getPath() {
		return $this->_path;
	}

	public function getQuerys() {
		return $this->_querys;
	}

	public function getQuery($name, $defaultValue = null) {
		return isset($this->_querys[$name]) ? $this->_querys[$name] : $defaultValue;
	}

	public function getURI() {
		return $this->_URI;
	}

	public function setURI($URI) {
		list($path, $queryString) = explode('?', $URI, 2) + [1 => ''];
		$path = preg_replace('/[\x00-\x1F?#]+/x', '', $path);
		$path = preg_replace('/[\/\\\\]+/', '/', $path);
		$path = preg_replace('/\/\.+\//', '/', $path);
		$path =  '/' . ltrim(trim($path), '/');
		$querys = $queryString ? parse_string($queryString) : [];
		$queryString = merge_string($querys);
		$URI = $path . ($queryString ? '?' . $queryString : '');


		$this->_URI = $URI;
		$this->_path = $path;
		$this->_querys = $querys;

		$_REQUEST = array_merge($querys, $this->_posts);
		$_GET = $querys;
		$_SERVER['REQUEST_URI'] = $URI;
		$_SERVER['QUERY_STRING'] = $queryString;

		return $this;
	}


	public function getURL() {
		return $this->_scheme . '://' . $this->getHeader('Host') . $this->_URI;
	}


	public function getHeaders() {
		return $this->_headers;
	}

	public function getHeader($name, $defaultValue = null) {
		return isset($this->_headers[$name]) ? $this->_headers[$name] : $defaultValue;
	}

	public function setHeaders(array $headers) {
		foreach($_SERVER as $key => $value) {
			if (in_array($key, ['PHP_AUTH_USER', 'PHP_AUTH_PW', 'ORIG_PATH_INFO', 'REDIRECT_QUERY_STRING', 'REDIRECT_URL', 'SERVER_PORT_SECURE', 'CONTENT_TYPE', 'CONTENT_LENGTH', 'UNENCODED_URL']) || substr($key, 0, 5) == 'HTTP_') {
				unset($_SERVER[$key]);
			}
		}
		$this->_headers = [];
		$this->_API = $this->_mobile = $this->_ranges = null;
		$_COOKIE = [];
		foreach($headers as $name => $value) {
			$this->setHeader($name, $value);
		}
		if (empty($this->_headers['Host'])) {
			$this->_headers['Host'] = self::defaultHeaders()['Host'];
		}
		return $this;
	}

	public function setHeader($name, $value) {
		$nameKey = strtoupper(strtr($name, '-', '_'));
		if ($value === null && $name !== 'Host') {
			unset($this->_headers[$name]);
			unset($_SERVER['HTTP_'. $nameKey]);
			if (in_array($name, ['Content-Type', 'Content-length'])) {
				unset($_SERVER[$nameKey]);
			} elseif ($name == 'Cookie') {
				$this->_cookies = $_COOKIE = [];
			} elseif ($name == 'Range') {
				$this->_ranges = null;
			} elseif ($name == 'User-Agent') {
				$this->_API = $this->_mobile = null;
			} elseif ($name == 'Authorization') {
				$this->_user = $this->_password = false;
			}
		} else {
			if (in_array($name, ['Content-Type'])) {
				$value = strtolower(trim(explode(';', $value, 2)[0]));
			}
			$value = rtrim(trim((string)$value), ';');
			if (in_array($name, ['Content-Type', 'Content-length'])) {
				$_SERVER[$nameKey] = $value;
			} elseif ($name == 'Cookie') {
				$this->_cookies = $_COOKIE = parse_string(preg_replace('/;\s*/', '&', $value));
			} elseif ($name == 'Range') {
				$this->_ranges = null;
			} elseif ($name == 'User-Agent') {
				$this->_API = $this->_mobile = null;
			} elseif ($name == 'Host') {
				$value = $value ? strtolower($value) : self::$_defaultHost;
			} elseif ($name == 'Authorization') {
				$this->_user = $this->_password = false;
				unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
				if (count($auth = explode(' ', $value, 2)) == 2 && $auth[1] && ($auth = base64_decode(trim($auth[1])))) {
					$auth = explode(':', $auth, 2);
					$_SERVER['PHP_AUTH_USER'] = $this->_user = $auth[0];
					$_SERVER['PHP_AUTH_PW'] = $this->_password = isset($auth[1]) ? $auth[1] : '';
				}
			}
			$_SERVER['HTTP_'. $nameKey] = $value;
			$this->_headers[$name] = $value;
		}
		return $this;
	}

	public function getCookies() {
		return $this->_cookies;
	}

	public function getCookie($key, $defaultValue = null) {
		return isset($this->_cookies[$key]) ? $this->_cookies[$key] : $defaultValue;
	}

	public function setCookies(array $cookies) {
		//=,; \t\r\n\013\014
		$_COOKIE = $this->_cookies = parse_string($cookies);
		$_SERVER['HTTP_COOKIE'] = http_build_query($_COOKIE, null, '; ');
		return $this;
	}

	public function setCookie($name, $value) {
		if ($value === null || !empty($this->_cookies[$name])) {
			$this->setCookies([$name => $value]+ $this->_cookies);
		} else {
			$_COOKIE = $this->_cookies[$name] = is_array($value) || is_object($value) ? parse_string($value) : $value;
			$value = http_build_query([$key => $value], null, '; ');
			if (empty($this->_headers['Cookie'])) {
				$this->_headers['Cookie'] = $value;
			} else {
				$this->_headers['Cookie'] .= '; ' . $value;
			}
			$_SERVER['HTTP_COOKIE'] = $this->_headers['Cookie'];
		}
		return $this;
	}

	public function getPosts() {
		return $this->_posts;
	}

	public function getPost($name, $defaultValue = null) {
		return isset($this->_posts[$name]) ? $this->_posts[$name] : $defaultValue;
	}

	public function setPosts(array $posts) {
		$_POST = $this->_posts = parse_string($posts);
		$_REQUEST = array_merge($this->_querys, $this->_posts, $this->_params);
		return $this;
	}

	public function setPost($name, $value) {
		if ($value === null) {
			unset($this->_posts[$name], $_POST[$name]);
			$_REQUEST = array_merge($this->_querys, $this->_posts, $this->_params);
		} else {
			$this->_posts[$name] = is_array($value) || is_object($value) ? parse_string($value) : $value;
			$_REQUEST = array_merge($this->_querys, $this->_posts, $this->_params);
		}
		return $this;
	}

	public function getFiles() {
		return $this->_files;
	}

	public function getFile($key) {
		return empty($this->_files[$key]) ? false : $this->_files[$key];
	}

	public function setFiles(array $files) {
		$_FILES = [];
		$this->_files = [];
		foreach ($files as $key => $value) {
			$this->setFile($key, $value);
		}
		return $this;
	}

	public function setFile($key, array $value) {
		$this->_files[$key] = [];
		unset($_FILES[$key]);
		if ($value) {
			if (empty($value['tmp_name'])) {
				throw new Exception('Set file path can not be empty');
			}
			if (is_array($value['tmp_name'])) {
				foreach($value['tmp_name'] as $k => $v) {
					$this->addFile($key, $value['tmp_name'][$k], empty($value['name'][$k]) ? false : $value['name'][$k], empty($value['type'][$k]) ? false : $value['type'][$k], isset($value['error'][$k]) ? $value['error'][$k] : UPLOAD_ERR_OK, isset($value['size'][$k]) ? $value['size'][$k] : false);
				}
			} else {
				$this->addFile($key, $value);
			}
		}
		return $this;
	}

	public function addFile($key, $tmp_name, $name, $type, $error = UPLOAD_ERR_OK, $size = false) {
		if (!is_string($tmp_name)) {
			throw new Exception('Add file path can not be empty');
		}
		$error = abs((int) $error);
		if (!$error || $error === UPLOAD_ERR_OK) {
			if (!is_file($tmp_name)) {
				throw new Exception('File does not exist');
			}
		}
		$size = $size === false && $error === UPLOAD_ERR_OK ? filesize($tmp_name) : abs((int)$size);
		$name = $name ? (string) pathinfo((string) $name, PATHINFO_BASENAME) : 'Unknown';
		$type = $type ? (string) $type : ($error === UPLOAD_ERR_OK && ($mime = File::mime($tmp_name)) ? $mime['type'] : 'application/octet-stream');
		$file = ['tmp_name' => $tmp_name, 'name' => $name, 'type' => $type, 'error' => $error];
		foreach ($file as $k => $v) {
			$this->_files[$key][$k] = array_merge(isset($this->_files[$key][$k]) ? (array) $this->_files[$key][$k] : [], [$v]);
		}
		return $this;
	}



	public function getContent() {
		return $this->_content;
	}

	public function setContent($content) {
		$this->_content = $content;
		return $this;
	}

	public function getIP() {
		return $this->_IP;
	}

	public function setIP($IP) {
		if($IP && !($value = filter_var($IP, FILTER_VALIDATE_IP))) {
			throw new Exception('Set IP:' . $IP);
		}
		if ($IP) {
			$IP = inet_ntop(inet_pton($IP));
			// 兼容请求地址
			if (preg_match('/\:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/', $IP, $matches)) {
				$IP = $matches[1];
			}
		}
		$this->_IP = (string) $IP;
		return $this;
	}


	public function getParams() {
		return $this->_params;
	}

	public function getParam($name, $defaultValue = null) {
		return isset($this->_params[$name]) ? $this->_params[$name] : $defaultValue;
	}

	public function setParams(array $params) {
		$this->_params = parse_string($params);
		$_REQUEST = array_merge($this->_querys, $this->_posts, $this->_params);
		return $this;
	}

	public function setParam($name, $value = null) {
		if ($value === null) {
			unset($this->_params[$name], $_POST[$name]);
			$_REQUEST = array_merge($this->_querys, $this->_posts, $this->_params);
		} else {
			$this->_params[$name] = is_array($value) || is_object($value) ? parse_string($value) : $value;
			$_REQUEST = array_merge($this->_querys, $this->_posts, $this->_params);
		}
		return $this;
	}




	public function createToken() {
		$token = uniqid();
		$token .= mb_rand(16 - strlen($token), '0123456789qwertyuiopasdfghjklzxcvbnm');
		$token .= Code::key(__CLASS__ . self::TOKEN_HEADER . $token, 16);
		return $token;
	}

	public function getToken($isKey = false) {
		if ($this->_token === null) {
			($token = $this->getHeader(self::TOKEN_HEADER)) || ($token = $this->getCookie(self::TOKEN_COOKIE));
			try {
				$this->setToken($token);
			} catch (Exception $e) {
				$this->setToken($this->createToken(), true);
			}
		}
		return $key ? $this->_token : substr($this->_token, 0, 16);
	}

	public function isNewToken() {
		return $this->_newToken;
	}

	public function setToken($token, $isNew = false) {
		if (!is_string($token) || strlen($token) != 32 || Code::key(__CLASS__ . self::TOKEN_HEADER . substr($token, 0, 16), 16) !== substr($token, 16)) {
			throw new Exception('Set token:' . $token);
		}
		$this->_newToken = $isNew;
		$this->_token = $token;
		return $this;
	}


	public function isAjax() {
		if ($this->_ajax === null) {
			if ($param = $this->getParam(self::AJAX_PARAM)) {
				$this->_ajax = (string) $param;
			} elseif (in_array($extension = strtolower(pathinfo($this->getPath(), PATHINFO_EXTENSION)), ['json', 'xml'])) {
				$this->_ajax = $extension;
			} elseif (in_array($accept = $this->getAccept() ? explode('/', $this->getAccept()[0])[1] : '', ['json', 'xml'])) {
				$this->_ajax = $accept;
			} elseif (strtolower($this->getHeader('X-Requested-with')) == 'xmlhttprequest') {
				$this->_ajax = 'json';
			} else {
				$this->_ajax = false;
			}
		}
		return $this->_ajax;
	}

	public function setAjax($ajax) {
		$this->_ajax = (string) $ajax;
		return $this;
	}

	public function getRanges() {
		if ($this->_ranges === null) {
			$this->_ranges = [];
			if (($range = $this->getHeader('Range')) && preg_match('/bytes=\s*([0-9-,]+)/i', $range, $matches)) {
				foreach (explode(',', $matches[1]) as $subject) {
					if (preg_match('/(\-?\d+)(?:\-(\d+)?)?/', $subject, $matches)) {
						$offset = intval($matches[1]);
						$length = isset($matches[2]) ? $matches[2] - $offset + 1 : false;
						if ($length === false || $length > 0) {
							$this->_ranges[] = ['offset' => $offset, 'length' => $length];
						}
					}
				}
			}
		}
		return $this->_ranges;
	}


	public function getUser() {
		return $this->_user;
	}


	public function getPassword() {
		return $this->_password;
	}


	public function isMobile() {
		if ($this->_mobile === null) {
			$this->_mobile = false;
			if (!$userAgent = $this->getHeader('User-Agent')) {
			} elseif (strpos($userAgent, 'Mobile') !== false || strpos($userAgent, 'Android') !== false || strpos($userAgent, 'Silk/') !== false || strpos($userAgent, 'Kindle') !== false || strpos($userAgent, 'BlackBerry') !== false || strpos($userAgent, 'Opera Mini') !== false || strpos($userAgent, 'Opera Mobi') !== false) {
				$this->_mobile = true;
			}
		}
		return false;
	}


	public function isAPI() {
		if ($this->_API === null) {
			$this->_API = false;
			if (!$userAgent = $this->getHeader('User-Agent')) {
				return false;
			} elseif (preg_match('/(^| )'. self::API_USER_AGENT .'\/([0-9a-z._-]+)/i', trim($userAgent), $matches)) {
				$this->_API = $matches[1];
			}
		}
		return $this->_API;
	}


	public function clear() {
		$this->_scheme = 'http';
		$this->_version = 1.1;
		$this->_method = 'GET';
		$this->_URI = '/';
		$this->_path = '/';
		$this->_querys = [];
		$this->_headers = [];
		$this->_cookies = [];
		$this->_posts = [];
		$this->_params = [];
		$this->_files = [];
		$this->_content = false;
		$this->_IP = false;
		$this->_token = null;
		$this->_newToken = false;
		$this->_ajax = null;
		$this->_ranges = null;
		$this->_mobile = null;
		$this->_API = null;
		$servers = $GLOBALS['_SERVER'];
		unset($GLOBALS['_SERVER'], $GLOBALS['_GET'], $GLOBALS['_POST'], $GLOBALS['_COOKIE'], $GLOBALS['_FILES'], $GLOBALS['_ENV'], $GLOBALS['_SESSION']);
		extract(self::$_g);
		if (!empty($servers['LOLI'])) {
			$GLOBALS['_SERVER']['LOLI'] = $servers['LOLI'];
		}
		return $this;
	}





	public function __construct($method = 'GET', $URI = false, $headers = []) {
		// 写入 方法
		$this->setMethod($method);

		// 写入 URI
		$this->setURI($URI || is_string($URI) ? $URI : self::defaultURI());

		// 写入 header 头
		$this->setHeaders($headers ? $headers : self::defaultHeaders());
	}



}
Request::start();















		/*self::_start();
		$version = $version ? $version : self::defaultValue();
		$method = $method ? $method : self::defaultMethod();
		$_SESSION = $_GET = $_POST = $_COOKIE = $_FILES = $_ENV = [];
		$_SERVER = self::$_g[0];
		foreach ($_SERVER as $name => $value) {
			if ((substr($name, 0, 5) === 'HTTP_') || in_array(['UNENCODED_URL', 'X_ORIGINAL_URL', 'HTTP_X_ORIGINAL_URL', 'IIS_WasUrlRewritten'])) {
				unset($_SERVER[$name]);
			}
		}
		//$_SERVER['SERVER_PROTOCOL'] = 'HTTP/' . $version;

		//self::init();
		//$this->defaultContent;
	}
}

//echo setcookie('test', 'asdiouasdxczxs', time() + 86400, '/');

//$uri = urldecode(
//	parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
//);
//print_r($uri);
Request::start();
//echo Request::defaultURI();
/*
class Request{

	private static $_g, $_content;

	private static function _start() {
		if (!self::$_g) {
			self::$_g = [$_SERVER, $_GET, $_POST, $_COOKIE, $_FILES, $_ENV, isset($_SESSION) ? $_SESSION : []];
		}
		return true;
	}

	public static function end() {
		if (self::$_g) {
			unset($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES, $_ENV, $_SESSION);
			list($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES, $_ENV, $_SESSION) = self::$_g;
		}
		return true;
	}

	// 默认版本
	public static function defaultVersion() {
		self::_start();
		return empty(self::$_g[0]['SERVER_PROTOCOL']) || self::$_g[0]['SERVER_PROTOCOL'] == 'HTTP/1.0' ? 1.0 : 1.1;
	}

	// 默认方法
	public static function defaultMethod() {
		self::_start();
		if (isset(self::$_g[0]['REQUEST_METHOD'])) {
			$method = self::$_g[0]['REQUEST_METHOD'];
		} else {
			$method = 'GET';
		}
		$method = strtoupper($method);
		if (!preg_match('/^[A-Z]+$/', $method)) {
			$method = 'GET';
		}
		return $method;
	}


	// 默认协议
	public static function defaultScheme() {
		self::_start();
		if (isset(self::$_g[0]['HTTPS']) && ('on' == strtolower(self::$_g[0]['HTTPS']) || '1' == self::$_g[0]['HTTPS'])) {
			$scheme = 'https';
		} elseif (isset(self::$_g[0]['SERVER_PORT']) && '443' == self::$_g[0]['SERVER_PORT']) {
			$scheme = 'https';
		} elseif (isset(self::$_g[0]['SERVER_PORT_SECURE']) && self::$_g[0]['SERVER_PORT_SECURE'] == '1') {
			$scheme = 'https';
		} else {
			$scheme = 'http';
		}
		return $scheme;
	}


	// 默认HOST
	public static function defaultHost() {
		self::_start();
		if (isset(self::$_g[0]['HTTP_HOST'])) {
			$host = self::$_g[0]['HTTP_HOST'];
		} elseif (isset(self::$_g[0]['SERVER_NAME'])) {
			$host = self::$_g[0]['SERVER_NAME'];
			if (isset(self::$_g[0]['SERVER_PORT']) && !in_array(self::$_g[0]['SERVER_PORT'], ['80', '443'])) {
				$host .= ':' . self::$_g[0]['SERVER_PORT'];
			}
		} else {
			$host = 'localhost';
		}
		return preg_replace('/[^0-9a-z:._-]/', '', strtolower($host));
	}

	// 默认路径
	public static function defaultPath() {
		self::_start();
		if (isset(self::$_g[0]['REQUEST_URI'])) {
			$path = explode('?', self::$_g[0]['REQUEST_URI'])[0];
		} elseif (isset(self::$_g[0]['HTTP_X_ORIGINAL_URL'])) {
			$path = explode('?', self::$_g[0]['HTTP_X_REWRITE_URL'])[0];
		} elseif (isset(self::$_g[0]['PATH_INFO']) && isset(self::$_g[0]['SCRIPT_NAME'])) {
			if (self::$_g[0]['PATH_INFO'] == self::$_g[0]['SCRIPT_NAME']) {
				$path = self::$_g[0]['PATH_INFO'];
			} else {
				$path = self::$_g[0]['SCRIPT_NAME'] . self::$_g[0]['PATH_INFO'];
			}
		} elseif (isset(self::$_g[0]['ORIG_PATH_INFO']) && isset(self::$_g[0]['SCRIPT_NAME'])) {
			if (self::$_g[0]['ORIG_PATH_INFO'] == self::$_g[0]['SCRIPT_NAME']) {
				$path = self::$_g[0]['ORIG_PATH_INFO'];
			} else {
				$path = self::$_g[0]['SCRIPT_NAME'] . self::$_g[0]['ORIG_PATH_INFO'];
			}
		} else {
			$path = '/';
		}
		$path = urldecode($path);
		$path = preg_replace('/[\x00-\x1F?]+/x', '', $path);
		$path = preg_replace('/[\/\\\\]+/', '/', $path);
		$path = preg_replace('/\/\.+\//', '/', $path);
		return '/'. ltrim($path, '/');
	}

	// 默认 Query
	public static function defaultQuery() {
		self::_start();
		return isset(self::$_g[0]['QUERY_STRING']) ? self::$_g[0]['QUERY_STRING'] : merge_string(self::$_g[1]);
	}


	// 默认 headers 头
	public static function defaultHeaders() {
		self::_start();
		if (function_exists('getallheaders')) {
			$headers = getallheaders();
		} elseif (function_exists('http_get_request_headers')) {
			$headers = http_get_request_headers();
		} else {
			$headers = [];
			foreach (self::$_g[0] as $name => $value) {
				if (substr($name, 0, 5) === 'HTTP_') {
					$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
				}
			}
		}
		return $headers;
	}

	// 默认内容
	public static function defaultContentParams() {
		//return $content;
	}


	// 默认内容
	public static function defaultContentParams() {
		//return $content;
	}

	// 默认内容
	//public static function defaultContent() {
		//self::_start();
		//if (self::$_content === null) {
		//	$content = empty(self::$_g[0]['CONTENT_TYPE']) || empty(self::$_g[0]['CONTENT_LENGTH']) || strpos(strtolower(self::$_g[0]['CONTENT_TYPE']), 'multipart/form-data') !== false || ? false : fopen('php://input', 'rb');
		//}
		//return $content;
	//}


	// 解析内容
	//$uri, $method = 'GET', $parameters = array(), $cookies = array(), $files = array(), $server = array(), $content = null

	public function __construct($method = 'GET', $path = false, $headers = [], $files = [], $content = null) {
		self::_start();
		$version = $version ? $version : self::defaultValue();
		$method = $method ? $method : self::defaultMethod();
		$_SESSION = $_GET = $_POST = $_COOKIE = $_FILES = $_ENV = [];
		$_SERVER = self::$_g[0];
		foreach ($_SERVER as $name => $value) {
			if ((substr($name, 0, 5) === 'HTTP_') || in_array(['UNENCODED_URL', 'X_ORIGINAL_URL', 'HTTP_X_ORIGINAL_URL', 'IIS_WasUrlRewritten'])) {
				unset($_SERVER[$name]);
			}
		}
		$_SERVER['SERVER_PROTOCOL'] = 'HTTP/' . $version;

		//self::init();
		//$this->defaultContent;
	}*/
//}


/*
class Request{
	private $_version, $_headers, $_content, $_method, $_scheme, $_host, $_path, $_queryParams, $_bodyParams, $_params, $_cookies, $_url, $_contentType, $_IP, $_accept, $_ajax, $_token, $_ranges;
	private $_rewriteParams = [];

	public $ajaxParam = 'ajax';

	public $cookiePrefix = '';



	const TOKEN_HEADER = 'X-Token';

	public $tokenParam = 'token';




	const CSRF_HEADER = 'X-Csrf';

	public $csrfParam = 'csrf';


	const API_USER_AGENT = 'API';

	public function getVersion() {
		if ($this->_version === null) {
			$this->_version = empty($_SERVER['SERVER_PROTOCOL']) || $_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.0' ? 1.0 : 1.1;
		}
		return $this->_version;
	}

	public function setVersion($version) {
		$this->_version = $version == 1.1 ? 1.1 : 1.0;
	}


	public function getHeaders() {
		if ($this->_headers !== null) {
		} elseif (function_exists('getallheaders')) {
			$this->_headers = getallheaders();
		} elseif (function_exists('http_get_request_headers')) {
			$this->_headers = http_get_request_headers();
		} else {
			$this->_headers = [];
			foreach ($_SERVER as $name => $value) {
				if (substr($name, 0, 5) === 'HTTP_') {
					$this->_headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
				}
			}
		}
		return $this->_headers;
	}

	public function setHeaders(array $headers) {
		$this->_headers = [];
		$this->_ranges = $this->_accept = null;
		foreach ($headers as $name => $value) {
			if ($value === null) {
				continue;
			}
			$this->_headers[$name] = (string) $value;
		}
		return $this;
	}

	public function getHeader($name, $defaultValue = null) {
		$this->_headers === null && $this->getHeaders();
		return isset($this->_headers[$name]) ? $this->_headers[$name] : $defaultValue;
	}

	public function setHeader($name, $value) {
		$this->_headers === null && $this->getHeaders();
		if ($name == 'Accept') {
			$this->_accept = null;
		} elseif ($name == 'Range') {
			$this->_ranges = null;
		}

		if ($value === null) {
			unset($this->_headers[$name]);
		} else {
			$this->_headers[$name] = (string) $value;
		}
		return $this;
	}


	public function getMethod() {
		if (!$this->_method) {
			if (isset($_SERVER['REQUEST_METHOD'])) {
				$this->_method = strtoupper($_SERVER['REQUEST_METHOD']);
			} elseif ($method = $this->getHeader('X-Http-Method-Override')) {
				$this->_method = strtoupper($method);
			} else {
				$this->_method = 'GET';
			}
			if (!preg_match('/^[A-Z]+$/', $this->_method)) {
				$this->_method = 'GET';
			}
		}
		return $this->_method;
	}


	public function setMethod($method) {
		if (!preg_match('/^[A-Z]+$/', $method = strtoupper($method))) {
			throw new Exception('Set method: ' . $method);
		}
		if ($method == 'GET') {
			$this->_bodyParams = [];
		}
		$this->_method = $method;
		return $this;
	}



	public function getScheme() {
		if ($this->_scheme === null) {
			if (isset($_SERVER['HTTPS']) && ('on' == strtolower($_SERVER['HTTPS']) || '1' == $_SERVER['HTTPS'])) {
				$this->_scheme = 'https';
			} elseif (isset($_SERVER['SERVER_PORT']) && '443' == $_SERVER['SERVER_PORT']) {
				$this->_scheme = 'https';
			} else {
				$this->_scheme = 'http';
			}
		}
		return $this->_scheme;
	}

	public function setScheme($scheme) {
		$this->_url = null;
		if (!preg_match('/^[a-z]+$/', $scheme = strtolower($scheme))) {
			throw new Exception('Set scheme: ' . $scheme);
		}
		$this->_scheme = $scheme;
		return $this;
	}





	public function getHost() {
		if ($this->_host === null) {
			if ($host = $this->getHeader('Host')) {
				$this->_host = strtolower($host);
			} elseif(isset($_SERVER['SERVER_NAME'])) {
				$this->_host = strtolower($_SERVER['SERVER_NAME']);
				if (isset($_SERVER['SERVER_PORT']) && !in_array($_SERVER['SERVER_PORT'], [80, 443])) {
					$this->_host .= ':' . $_SERVER['SERVER_PORT'];
				}
			} else {
				throw new Exception('Get host is empty');
			}
		}
		return $this->_host;
	}

	public function setHost($host) {
		$this->_url = null;
		if (!preg_match('/([0-9a-z_-]+\.[0-9a-z_-]+)(\:\d+)?/', $host = strtolower($host))) {
			throw new Exception('Set Host:' . $host);
		}
		$this->_host = $host;
		return $this;
	}




	public function getPath() {
		if ($this->_path === null) {
			$path = '/';
			if (isset($_SERVER['REQUEST_URI'])) {
				$path = explode('?', $_SERVER['REQUEST_URI'])[0];
			} elseif (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
				$path = urldecode(explode('?', $_SERVER['HTTP_X_ORIGINAL_URL'])[0]);
			} elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
				$path = explode('?', $_SERVER['HTTP_X_REWRITE_URL'])[0];
			} elseif (isset($_SERVER['PATH_INFO']) && isset($_SERVER['SCRIPT_NAME'])) {
				if ($_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME']) {
					$path = $_SERVER['PATH_INFO'];
				} else {
					$path = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
				}
			} elseif (isset($_SERVER['ORIG_PATH_INFO']) && isset($_SERVER['SCRIPT_NAME'])) {
				if ($_SERVER['ORIG_PATH_INFO'] == $_SERVER['SCRIPT_NAME']) {
					$path = $_SERVER['ORIG_PATH_INFO'];
				} else {
					$path = $_SERVER['SCRIPT_NAME'] . $_SERVER['ORIG_PATH_INFO'];
				}
			} else {
				$path = '/';
			}
			$this->setPath($path);
		}
		return $this->_path;
	}


	public function setPath($path) {
		$this->_url = null;
		$path = preg_replace('/[\x00-\x1F]+/x', '', $path);
		$path = preg_replace('/[\/\\\\]+/', '/', $path);
		$path = preg_replace('/\/\.+\//', '/', $path);
		$path = '/'. ltrim($path, '/');
		$this->_path = urldecode($path);
		return $this;
	}

	public function getContent($isResource = true) {
 if ($this->_content === null) {
 	if (in_array($this->getMethod(), ['GET', 'OPTIONS'])) {
 		$this->_content = false;
 	} elseif ($isResource) {
				$this->_content = fopen('php://input', 'rb');
			} else {
				$this->_content = file_get_contents('php://input');
			}
		}
		if ($this->_content === false) {
			return false;
		}
		if (($isResource && is_resource($this->_content)) || (!$isResource && is_string($this->_content))) {
			return $this->_content;
		}
 return false;
    }

    public function setContent($content) {
    	$this->_content = is_resource($content) || $content === false ? $content : (string) $content;
    	return $this;
    }


	public function getContentType() {
		if ($this->_contentType === null) {
			if (in_array($this->getMethod(), ['GET', 'OPTIONS'])) {
				$this->_contentType = '';
			} elseif (isset($_SERVER["CONTENT_TYPE"])) {
				$this->_contentType = explode($_SERVER["CONTENT_TYPE"], ';')[0];
			} elseif ($contentType = $this->getHeader('Content-Type')) {
				$this->_contentType = explode($contentType, ';')[0];
			} else {
				$this->_contentType = '';
			}
		}
		return $this->_contentType;
	}

	public function setContentType($contentType) {
		$this->_contentType = strtolower($contentType);
		return $this;
	}


	public function getQueryParams() {
		if ($this->_queryParams === null) {
			$this->_queryParams = $_GET;
		}
		return $this->_queryParams;
	}

	public function setQueryParams(array $params) {
		$this->_url = $this->_params = null;
		$this->_queryParams = to_array(array_unnull($params));
		return $this;
	}


	public function getQueryParam($name, $defaultValue = null) {
		$this->_queryParams === null && $this->getQueryParams();
		return isset($this->_queryParams[$name]) ? $this->_queryParams[$name] : $defaultValue;
	}


	public function setQueryParam($name, $value) {
		$this->_url = $this->_params = null;
		$this->_queryParams === null && $this->getQueryParams();
		if ($value === null) {
			unset($this->_queryParams[$name]);
		} else {
			$this->_queryParams[$name] = is_array($value) || is_object($value) ? to_array($value) : $value;
		}
		return  $this;
	}


	public function getBodyParams() {
		if ($this->_bodyParams === null) {
			if (in_array($this->getMethod(), ['GET', 'OPTIONS'])) {
				$this->_bodyParams = [];
			} elseif (in_array($this->getContentType(), ['application/json', 'text/json'])) {
				if (!$this->_bodyParams = json_decode($this->getContent(false), true)) {
					$this->_bodyParams = [];
				}
			} elseif (in_array($this->getContentType(), ['application/xml', 'text/xml'])) {
				$this->_bodyParams = $_POST;
			} else {
				$this->_bodyParams = $_POST;
			}
		}
		return $this->_bodyParams;
	}


	public function setBodyParams(array $params) {
		$this->_params = null;
		$this->_bodyParams = to_array(array_unnull($params));
		return $this;
	}

	public function getBodyParam($name, $defaultValue = null) {
		$this->_bodyParams === null && $this->getBodyParams();
		return isset($this->_bodyParams[$name]) ? $this->_bodyParams[$name] : $defaultValue;
	}

	public function setBodyParam($name, $value) {
		$this->_bodyParams === null && $this->getBodyParams();
		$this->_params = null;
		if ($value === null) {
			unset($this->_bodyParams[$name]);
		} else {
			$this->_bodyParams[$name] = is_array($value) || is_object($value) ? to_array($value) : $value;
		}
		return  $this;
	}






	public function getRewriteParams() {
		return $this->_rewriteParams;
	}

	public function setRewriteParams(array $params) {
		$this->_params = null;
		$this->_rewriteParams = to_array(array_unnull($params));
		return $this;
	}

	public function getRewriteParam($name, $defaultValue = null) {
		return isset($this->_rewriteParams[$name]) ? $this->_rewriteParams[$name] : $defaultValue;
	}

	public function setRewriteParam($name, $value) {
		$this->_rewriteParams === null && $this->getRewriteParams();
		$this->_params = null;
		if ($value === null) {
			unset($this->_rewriteParams[$name]);
		} else {
			$this->_rewriteParams[$name] = is_array($value) || is_object($value) ? to_array($value) : $value;
		}
		return  $this;
	}



	public function getParams() {
		if ($this->_params === null) {
			$this->_params = array_merge($this->getQueryParams(), $this->getBodyParams(), $this->getRewriteParams());
		}
		return $this->_params;
	}

	public function getParam($name, $defaultValue = null) {
		$this->_params === null && $this->getParams();
		return isset($this->_params[$name]) ? $this->_params[$name] : $defaultValue;
	}





	public function getCookies() {
		if ($this->_cookies === null) {
			$this->_cookies = [];
			$length = strlen($this->cookiePrefix);
			foreach ($_COOKIE as $name => $value) {
				if (substr($name, 0, $length) === $this->cookiePrefix) {
					$this->cookie[substr($name, $length)] = $value;
				}
			}
		}
		return $this->_cookies;
	}


	public function setCookies(array $cookies) {
		$this->_cookies = to_array(array_unnull($cookies));
		return $this;
	}

	public function getCookie($name, $defaultValue = null) {
		$this->_cookies === null && $this->getCookies();
		return isset($this->_cookies[$name]) ? $this->_cookies[$name] : $defaultValue;
	}

	public function setCookie($name, $value) {
		$this->_cookies === null && $this->getCookies();
		if ($value === null) {
			unset($this->_cookies[$name]);
		} else {
			$this->_cookies[$name] = is_array($value) || is_object($value) ? to_array($value) : $value;
		}
		return $this;
	}










	public function getUrl() {
		if ($this->_url === null) {
			$url = '';
			if ($host = $this->getHost()) {
				if ($scheme = $this->getScheme()) {
					$url = $scheme . ':';
				}
				$url .= '//';
				$url .= $host;
			}
			if ($path = $this->getPath()) {
				$url .= $this->getPath();
			}
			if ($params = $this->getQueryParams) {
				$this->_url .= '?' . merge_string($params);
			}
		}
		return $this->_url;
	}







	public function getAccept() {
		if ($this->_accept === null) {
			$this->_accept = [];
			if ($accept = $this->getHeader('Accept')) {
				foreach (explode(',', $accept) as $value) {
					if (!$value) {
						continue;
					}
					if (!$value = explode($value, ';')[0]) {
						continue;
					}
					if (!strpos($value, '/')) {
						continue;
					}
					$this->_accept[] = strtolower(trim($value));
				}
			}
		}
		return $this->_accept;
	}

	public function getToken($key = false) {
		if ($this->_token === null) {
			($token = $this->getHeader(self::TOKEN_HEADER)) || ($this->tokenParam && ($token = $this->getCookie($this->tokenParam)));
			try {
				$this->setToken($token);
			} catch (Exception $e) {
				$token = uniqid();
				$token .= mb_rand(16 - strlen($token), '0123456789qwertyuiopasdfghjklzxcvbnm');
				$token .= Code::key(__CLASS__ . self::TOKEN_HEADER . $token, 16);
				$this->setToken($token);
			}
		}
		return $key ? $this->_token : substr($this->_token, 0, 16);
	}

	public function setToken($token) {
		if (!is_string($token) || strlen($token) != 32 || Code::key(__CLASS__ . self::TOKEN_HEADER . substr($token, 0, 16), 16) != substr($token, 16)) {
			throw new Exception('Set token:' . $token);
		}
		return $this;
	}



	public function isCSRF() {
		$CSRF = call_user_func_array([$this, 'getCSRF'], func_get_args());
		return $this->getHeader(self::CSRF_HEADER) === $CSRF || ($this->csrfParam && $CSRF === $this->getCookie($this->csrfParam));
	}

	public function getCSRF() {
		$args = [];
		foreach (func_get_args() as $arg) {
			$args[] = is_array($arg) ? implode('/', $arg) : (string) $arg;
		}
		return Code::key($args ? implode('', $args) : $args, $this->getToken());
	}



	public function isAjax() {
		if ($this->_ajax === null) {
			if ($this->ajaxParam && ($param = $this->getParam($this->ajaxParam))) {
				$this->_ajax = (string) $param;
			} elseif (in_array($extension = strtolower(pathinfo($this->getPath(), PATHINFO_EXTENSION)), ['json', 'xml'])) {
				$this->_ajax = $extension;
			} elseif (in_array($accept = $this->getAccept() ? explode('/', $this->getAccept()[0])[1] : '', ['json', 'xml'])) {
				$this->_ajax = $accept;
			} elseif (strtolower($this->getHeader('X-Requested-with')) == 'xmlhttprequest') {
				$this->_ajax = 'json';
			} else {
				$this->_ajax = false;
			}

			if ($this->_ajax !== false){
				$this->_ajax = strtolower($this->_ajax);
			}
		}
		return $this->_ajax;
	}


	public function setAjax($ajax) {
		$this->_ajax = $ajax === false ? $ajax : (string) $ajax;
		return $this;
	}


	public function getIP() {
		if ($this->_IP === null) {
			$this->_IP = false;
			if (!empty($_SERVER['REMOTE_ADDR'])) {
				$this->_IP = $_SERVER['REMOTE_ADDR'];
			} elseif (($IP = $this->getHeader('Client-Ip')) && filter_var($IP, FILTER_VALIDATE_IP)) {
				$this->_IP = $IP;
			} elseif ($IPs = $this->getHeader('X-Forwarded-For')) {
				foreach (explode(',', $IPs) as $IP) {
					$IP = trim($IP);
					if (filter_var($IP = trim($IP), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) &&(empty($_SERVER['SERVER_ADDR']) || $_SERVER['SERVER_ADDR'] != $IP)) {
						$this->_IP = $IP;
						break;
					}
				}
			}

			if ($this->_IP) {
				$this->_IP = inet_ntop(inet_pton($this->_IP));
				// 兼容请求地址
				if (preg_match('/^\:\:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/', $this->_IP, $matches)) {
					$this->_IP = $matches[1];
				}
			}
		}
		return $this->_IP;
	}


	public function setIP($IP) {
		if ($IP !== false) {
			if (!$inet = inet_pton($IP)) {
				throw new Exception('Set IP:' . $IP);
			}
			$IP = inet_ntop($inet);
			if (preg_match('/^\:\:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/', $IP, $matches)) {
				$IP = $matches[1];
			}
		}
		$this->_IP = $IP;
		return $this;
	}



	public function getRanges() {
		if ($this->_ranges === null) {
			$this->_ranges = [];
			if (($range = $this->getHeader('Range')) && preg_match('/bytes=\s*([0-9-,]+)/', $range, $matches)) {
				foreach (explode(',', $matches[1]) as $subject) {
					if (preg_match('/(\-?\d+)(?:\-(\d+)?)?/', $subject, $matches)) {
						$offset = intval($matches[1]);
						$length = isset($matches[2]) ? $matches[2] - $offset + 1 : false;
						if ($length === false || $length > 0) {
							$this->_ranges[] = ['offset' => $offset, 'length' => $length];
						}
					}
				}
			}
		}
		return  $this->_ranges;
	}


	public function isMobile() {
		if (!$userAgent = $this->getHeader('User-Agent')) {
			return false;
		}
		if (strpos($userAgent, 'Mobile') !== false || strpos($userAgent, 'Android') !== false || strpos($userAgent, 'Silk/') !== false || strpos($userAgent, 'Kindle') !== false || strpos($userAgent, 'BlackBerry') !== false || strpos($userAgent, 'Opera Mini') !== false || strpos($userAgent, 'Opera Mobi') !== false) {
			return true;
		}
		return false;
	}


	public function isAPI() {
		if (!$userAgent = $this->getHeader('User-Agent')) {
			return false;
		}
		if (preg_match('/(^| )'. self::API_USER_AGENT .'\/([0-9a-z._-]+)/i', trim($userAgent), $matches)) {
			return true;
		}
		return $matches[1];
	}
}
*/