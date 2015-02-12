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
/*	Updated: UTC 2015-02-12 06:37:58
/*
/* ************************************************************************** */
namespace Loli;

class Request{


	private static $_g, $_postsLength = 2097152, $_defaultHost = 'localhost', $_methodsList = ['OPTIONS', 'HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

	private $_version = null;
	private $_method = null;

	private $_URL = null;
	private $_scheme = null;
	private $_host = null;
	private $_path = null;
	private $_querys = null;

	private $_headers = [];
	private $_posts = [];
	private $_files = [];
	private $_content = false;


	public static function start() {
		if (!self::$_g) {
			self::$_g = array_intersect_key($GLOBALS, ['_SERVER' => '', '_GET' => '', '_POST' => '', '_COOKIE' => '', '_FILES' => '', '_ENV' => '', '_SESSION' => '']);
		}
		return true;
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

	public static function defaultHost(){
		if (isset(self::$_g['_SERVER']['HTTP_HOST'])) {
			$host = self::$_g['_SERVER']['HTTP_HOST'];
		}  elseif (isset(self::$_g['_SERVER']['SERVER_NAME'])) {
			$host = self::$_g['_SERVER']['SERVER_NAME'];
			if (isset(self::$_g['_SERVER']['SERVER_PORT']) && !in_array(self::$_g['_SERVER']['SERVER_PORT'], ['80', '443'])) {
				$host = ':' . self::$_g['_SERVER']['SERVER_PORT'];
			}
		} else {
			$host = self::$_defaultHost;
		}
		return $host;
	}


	// 默认 URL
	public static function defaultURL() {
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

		$host = self::defaultHost();

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
		return $scheme .'://'. $host . '/' . ltrim($URI, '/');
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
					$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
				}
			}
			if (!empty(self::$_g['_SERVER']['CONTENT_TYPE'])) {
				$headers['Content-Type'] = self::$_g['_SERVER']['CONTENT_TYPE'];
			}
			if (!empty(self::$_g['_SERVER']['CONTENT_LENGTH']) && self::$_g['_SERVER']['CONTENT_LENGTH'] > 0) {
				$headers['Content-Length'] = self::$_g['_SERVER']['CONTENT_LENGTH'];
			}
		}
		$headers['Host'] = self::defaultHost();
		return $headers;
	}


	// 默认内容数据
	public static function defaultPosts() {
		static $posts;
		if (!isset($posts)) {
			if (empty(self::$_g['_SERVER']['CONTENT_TYPE']) || empty(self::$_g['_SERVER']['CONTENT_LENGTH']) || self::$_g['_SERVER']['CONTENT_LENGTH'] < 1 || self::$_g['_SERVER']['CONTENT_LENGTH'] > self::$_postsLength) {
				$posts = self::$_g['_POST'];
			} elseif (in_array($method = self::defaultMethod(), ['POST', 'PUT', 'PATCH', 'DELETE']) && strpos(self::$_g['_SERVER']['CONTENT_TYPE'], '/json') !== false) {
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
				$files[] = array_merge($name, self::_files($name[$key], $type[$key], $tmp_name[$key], $error[$key], $size[$key]));
			}
		} else {
			$files[] = ['name' => $name, 'type' => $type, 'tmp_name' => $tmp_name, 'error' => $error, 'size' => $size];
		}
		return $files;
	}



	// 默认内容
	public static function defaultContent() {
		if (empty(self::$_g['_SERVER']['CONTENT_TYPE']) || empty(self::$_g['_SERVER']['CONTENT_LENGTH']) || self::$_g['_SERVER']['CONTENT_LENGTH'] < 1) {
			return false;
		}
		return fopen('php://input', 'rb');
	}



	// 默认IP
	public static function defaultIP() {
		$ip = isset(self::$_g['_SERVER']['REMOTE_ADDR']) ? self::$_g['_SERVER']['REMOTE_ADDR'] : '127.0.0.1';
		if (empty($_SERVER['LOLI']['IP'])) {

		} elseif (isset(self::$_g['_SERVER']['HTTP_CLIENT_IP']) && filter_var(self::$_g['_SERVER']['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
			$ip = self::$_g['_SERVER']['HTTP_CLIENT_IP'];
		} elseif (isset(self::$_g['_SERVER']['HTTP_X_FORWARDED_FOR'])) {
			foreach (explode(',', self::$_g['_SERVER']['HTTP_X_FORWARDED_FOR']) as $v) {
				$v = trim($v);
				if (filter_var($v, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) && (empty(self::$_g['_SERVER']['SERVER_ADDR']) || self::$_g['_SERVER']['SERVER_ADDR'] != $v)) {
					$ip = $v;
					break;
				}
			}
		}

		if ($ip) {
			$ip = inet_ntop(inet_pton($ip));
			// 兼容请求地址
			if (preg_match('/\:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/', $ip, $matches)) {
				$ip = $matches[1];
			}
		}
		return $ip;
	}


	public function getVersion(){
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

	// 设置 GET
	public function getURL() {
		return $this->_URL;
	}

	// 设置 url
	public function setURL($URL) {
		$parse = parse_url($URL);
		unset($parse['user'], $parse['pass'], $parse['fragment']);

		if (!empty($parse['scheme'])) {
			$parse['scheme'] = strtolower(trim($parse['scheme']));
		}
		if (!empty($parse['host'])) {
			$parse['host'] = strtolower($parse['host']);
			if (!preg_match('/^[0-9a-z_.:-]+$/', $parse['host'])) {
				throw new Exception('Set Host:' . $host);
			}
		}

		if (!empty($parse['port'])) {
			$parse['port'] = (string) abs((int)$parse['port']);
			if (in_array($parse['port'], ['80', '443'])) {
				unset($parse['port']);
			}
		}


		if (!empty($parse['path'])) {
			$parse['path'] = preg_replace('/[\x00-\x1F?#]+/x', '', $parse['path']);
			$parse['path'] = preg_replace('/[\/\\\\]+/', '/', $parse['path']);
			$parse['path'] = preg_replace('/\/\.+\//', '/', $parse['path']);
			$parse['path'] =  '/' . rtrim(trim($parse['path']), '/');
		}




		if ($this->_URL) {
			foreach($parse as $key => $value) {
				if (!$value && in_array($key, ['scheme', 'host', 'port', 'path'])){
					unset($parse[$key]);
				}
			}
			$parse += parse_url($this->_URL);
		}

		if (!empty($parse['scheme']) || $this->_scheme === null) {
			$this->_scheme = $parse['scheme'];
			$_SERVER['REQUEST_SCHEME'] = $this->_scheme;
			if ($this->_scheme == 'https') {
				$_SERVER['HTTPS'] = 'on';
			} else {
				unset($_SERVER['HTTPS']);
			}
		}


		if (!empty($parse['host']) || !empty($parse['port']) || $this->_host === null) {
			$this->_host = empty($parse['host']) ? ($this->_host ? $this->_host : self::_defaultHost()) : $this->_host;
			$_SERVER['SERVER_PORT'] = empty($parse['port']) ? ($this->_scheme == 'https' ? '443' : '80') : $parse['port'];
			$_SERVER['SERVER_HOST'] = $this->_host . (empty($parse['port']) || in_array($parse['port'], ['80', '443']) ? '' : ':' . $parse['port']);
		}


		if (!$this->_path || (isset($parse['path']) && $parse['path'] !== $this->_path)) {
			$this->_path = empty($parse['path']) ? '/' : $parse['path'];
		}


		if (isset($parse['query']) || $this->_querys === null) {
			$this->_querys = empty($parse['query']) ? parse_string($parse['query']) : [];
		}
		$queryString = merge_string($this->_querys);
		


		$URI = $this->_path . ($queryString ? '?' . $queryString : '');
		$_SERVER['REQUEST_URI'] = $URI;
		$_SERVER['QUERY_STRING'] = $queryString;

		// URL 地址
		$this->_URL = $this->_scheme . '://' . $this->_host . $this->_path . $URI;

		return $this;
		//if () {
		 //+ parse_url($this->_URL);
		//}

		/*

		 + parse_url($this->_URL);
		unset($parse['user'], $parse['pass'], $parse['fragment']);

		$parse['host'] = strtolower(empty($parse['host']) ? self::$_defaultHost : $parse['host']);
		$parse['scheme'] = empty($parse['scheme']) ? 'http' : strtolower($parse['scheme']);
		if (empty($parse['path'])) {
			$path = preg_replace('/[\x00-\x1F?]+/x', '', $path);
			$path = preg_replace('/[\/\\\\]+/', '/', $path);
			$path = preg_replace('/\/\.+\//', '/', $path);
		} else {
			$parse['path'] = '';
		}
		$parse['path'] = isset($parse['path']) ? '/'. rtrim(strtr($parse['path'], ['#' => '', '?' => '', ' ' => '%20']), '/') : '/';


		$this->_URL = mrege_url($parse);
		$this->_scheme = $parse['scheme'];
		$this->_host = $parse['host'] . (empty($parse['port']) || in_array($parse['port'], ['80', '443']) ? '' : ':' . $parse['port']);
		$this->_path = $parse['path'];
		$_GET = $this->_querys = parse_string($parse['query']);

		$_REQUEST = array_merge($this->_querys, $this->_posts);

		$_SERVER['REQUEST_SCHEME'] = $this->_scheme;
		$_SERVER['SERVER_HOST'] = $this->_host;
		$_SERVER['SERVER_PORT'] = empty($parse['port']) ? ($this->_scheme == 'http' ? '80' : '443') : $parse['port'];
		if ($this->_scheme == 'https') {
			$_SERVER['HTTPS'] = 'on';
		} else {
			unset($_SERVER['HTTPS']);
		}
		$_SERVER['REQUEST_URI'] = strtr($this->_path, ['#' => '', '?' => '', ' ' => '%20']);
		$_SERVER['QUERY_STRING'] = $this->_querys? merge_string($this->_querys) : '';

		return $this;
		/*if (!in_array($method = strtoupper($method), ['OPTIONS', 'HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
			throw new Exception('Set token:' . $method);
		}
		$this->_method = $method;
		return $this;*/
	}

	public function getScheme() {
		return $this->_Scheme;
	}

	public function getHost() {
		return $this->_host;
	}

	public function getPath() {
		return $this->_path;
	}

	public function getQuerys() {
		return $this->_querys;
	}

	public function getQuery($name, $defaultValue = null) {
		return isset($this->_Querys[$name]) ? $this->_Querys[$name] : $defaultValue;
	}


	public function getHeaders() {
		return $this->_headers;
	}

	public function getHeader($name, $defaultValue = null) {
		return isset($this->_headers[$name]) ? $this->_headers[$name] : $defaultValue;
	}

	public function setHeader($name, $value) {
		if ($value === null) {
			unset($this->_headers[$name]);
		} else {
			$this->_headers[$name] = rtrim(trim((string)$value), ';');
		}
		return $this;
	}

	public function getPosts() {
		return $this->_posts;
	}

	public function getPost($name, $defaultValue = null) {
		return isset($this->_posts[$name]) ? $this->_posts[$name] : $defaultValue;
	}

	public function setPosts($posts) {
		$_POST = $this->_posts = to_array($posts);
		$_REQUEST = array_merge($this->_querys, $this->_posts);
		return $this;
	}

	public function setPost($name, $value) {
		if ($value === null) {
			unset($this->_posts[$name], $_POST[$name]);
			$_REQUEST = array_merge($this->_querys, $this->_posts);
		} else {
			$this->_posts[$name] = is_array($value) || is_object($value) ? to_array($value) : $value;
			$_REQUEST = array_merge($this->_querys, $this->_posts);
		}
		return $this;
	}

	public function getFiles($name) {
		return $this->_files;
	}



	public function addFiles(array $files) {
		foreach ($files as $key => $value) {
			foreach ($value as $file) {
				if (empty($file['tmp_name'])) {
					throw new Exception('File address can not be empty');
				}
				$file['error'] = isset($file['error']) ? $file['error'] : UPLOAD_ERR_OK;
				if ($file['error'] === UPLOAD_ERR_OK) {
					if (!is_file($file['tmp_name'])) {
						throw new Exception('File does not exist');
					}
					$file['name'] = empty($file['name']) ? 'Unknown' : (string) $file['name'];
					$file['type'] = empty($file['type']) ? (($mime = Storage::mime($file['tmp_name'])) ? $mime['type'] : 'application/octet-stream') : (string) $file['type'];
					$file['size'] = filesize($file['tmp_name']);
				} else {
					$file += ['name' => 'Unknown', 'type' => 'application/octet-stream', 'size' => 0];
				}
				$this->_files[$key][] = $file;
			}
		}
		return $this;
	}


	public function addFile($key, array $file) {
		return $this->addFiles([$key => [$file]]);
		if (empty($file['tmp_name'])) {
			throw new Exception('File address can not be empty');
		}
		$file['error'] = isset($file['error']) ? $file['error'] : UPLOAD_ERR_OK;
		
		if ($file['error'] === UPLOAD_ERR_OK) {
			if (!is_file($file['tmp_name'])) {
				throw new Exception('File does not exist');
			}
			$file['name'] = empty($file['name']) ? 'Unknown' : (string) $file['name'];
			$file['type'] = empty($file['type']) ? (($mime = Storage::mime($file['tmp_name'])) ? $mime['type'] : 'application/octet-stream') : (string) $file['type'];
			$file['size'] = filesize($file['tmp_name']);
		} else {
			$file += ['name' => 'Unknown', 'type' => 'application/octet-stream', 'size' => 0];
		}
		if (empty($this->_files[$name])) {
			$this->_files[$name] = $file;
		} else {
			if (!is_int(key($this->_files[$name]))) {
				$this->_files[$name];
			}
			$this->_files[$name] = array_merge($file);
		}

		/*if (is_int(key($files))) {
			foreach ($files as $file) {
				$this->addFiles($key, $file);
			}
		} else {
			$file = $files;
			if (empty($file['tmp_name'])) {
				throw new Exception('File address can not be empty');
			}
			$file['error'] = isset($file['error']) ? $file['error'] : UPLOAD_ERR_OK;
			if ($file['error'] === UPLOAD_ERR_OK) {
				if (!is_file($file['tmp_name'])) {
					throw new Exception('File does not exist');
				}
				$file['name'] = empty($file['name']) ? 'Unknown' : $file['name'];
				$file['type'] = empty($file['type']) ? (($mime = Storage::mime($file['tmp_name'])) ? $mime['type'] : 'application/octet-stream') : $file['type'];
				$file['size'] = filesize($file['tmp_name']);
			} else {
				$file += ['name' => 'Unknown', 'type' => 'application/octet-stream', 'size' => 0];
			}

			if (empty($this->_files[$name])) {
				$this->_files[$name] = $file;
			} else {
				if (!is_int(key($this->_files[$name]))) {
					$this->_files[$name];
				}
				$this->_files[$name] = array_merge($file);
			}*/
		//}
		return $this;
	}


	// 设置 headers


	public function __construct($method = 'GET', $URL = false, $headers = null, $posts = null, $files = null, $params = []) {
		foreach($_SERVER as $key => $value) {
			if (in_array($key, ['REDIRECT_QUERY_STRING', 'REDIRECT_URL']) || substr($key, 0, 5) == 'HTTP_') {
				unset($_SERVER[$key]);
			}
		}
		$this->setMethod($method);
		$this->setURL(self::defaultURL());
		$URL && $this->setURL($URL);
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

	private static $_g, $_input;

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
		//if (self::$_input === null) {
		//	$content = empty(self::$_g[0]['CONTENT_TYPE']) || empty(self::$_g[0]['CONTENT_LENGTH']) || strpos(strtolower(self::$_g[0]['CONTENT_TYPE']), 'multipart/form-data') !== false ||  ? false : fopen('php://input', 'rb');
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
		return  $this;
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
				$this->_method  = 'GET';
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