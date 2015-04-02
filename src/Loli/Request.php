<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-03-24 14:13:03
/*	Updated: UTC 2015-04-01 14:00:41
/*
/* ************************************************************************** */
namespace Loli;
class Request{


	const TOKEN_HEADER = 'X-Token';

	const TOKEN_COOKIE = 'token';

	const AJAX_HEADER = 'X-Ajax';

	const AJAX_PARAM = 'ajax';

	const PJAX_HEADER = 'X-Pjax';

	private static $_schemes = ['http', 'https'];
	private static $_methods = ['OPTIONS', 'HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
	private static $_defaultHost = 'localhost';
	private static $_postLength = 2097152;
	private static $_content = 'php://input';
	private static $_newToken = false;
	private static $_token = NULL;
	private static $_ajax = NULL;
	private static $_pjax = NULL;


	public static function init() {
		if (!empty($_SERVER['REQUEST_SCHEME'])) {
			$scheme = $_SERVER['REQUEST_SCHEME'];
		} elseif (isset($_SERVER['HTTPS']) && ('on' === strtolower($_SERVER['HTTPS']) || '1' === $_SERVER['HTTPS'])) {
			$scheme = 'https';
		} elseif (isset($_SERVER['SERVER_PORT']) && '443' === $_SERVER['SERVER_PORT']) {
			$scheme = 'https';
		} elseif (isset($_SERVER['SERVER_PORT_SECURE']) && '1' === $_SERVER['SERVER_PORT_SECURE']) {
			$scheme = 'https';
		} else {
			$scheme = 'http';
		}

		if (!empty($_SERVER['UNENCODED_URL'])) {
			$public static function defaultScheme() {
		if (!empty($_SERVER['REQUEST_SCHEME'])) {
			$scheme = $_SERVER['REQUEST_SCHEME'];
		} elseif (isset($_SERVER['HTTPS']) && ('on' === strtolower($_SERVER['HTTPS']) || '1' === $_SERVER['HTTPS'])) {
			$scheme = 'https';
		} elseif (isset($_SERVER['SERVER_PORT']) && '443' === $_SERVER['SERVER_PORT']) {
			$scheme = 'https';
		} elseif (isset($_SERVER['SERVER_PORT_SECURE']) && '1' === $_SERVER['SERVER_PORT_SECURE']) {
			$scheme = 'https';
		} else {
			$scheme = 'http';
		}
		return $scheme;
	} = $_SERVER['UNENCODED_URL'];
		} elseif (!empty($_SERVER['HTTP_X_ORIGINAL_URL'])) {
			$URI= $_SERVER['HTTP_X_ORIGINAL_URL'];
		} elseif (!empty($_SERVER['REQUEST_URI'])) {
			$URI= $_SERVER['REQUEST_URI'];
		} elseif (isset($_SERVER['PATH_INFO']) && isset($_SERVER['SCRIPT_NAME'])) {
			if ($_SERVER['PATH_INFO'] === $_SERVER['SCRIPT_NAME']) {
				$URI = $_SERVER['PATH_INFO'];
			} else {
				$URI = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
			}
		} else {
			$URI = '/';
		}




		if (function_exists('getallheaders')) {
			$headers = [];
			foreach (getallheaders() as $name => $value) {
				$headers[strtoupper($name)] = $value;
			}
		} elseif (function_exists('http_get_request_headers')) {
			$headers = [];
			foreach (http_get_request_headers() as $name => $value) {
				$headers[strtoupper($name)] = $value;
			}
		} else {
			$headers = [];
			foreach ($_SERVER as $name => $value) {
				if (substr($name, 0, 5) === 'HTTP_') {
					$headers[substr($name, 5)] = $value;
				}
			}
			if (isset($_SERVER['CONTENT_TYPE'])) {
				$headers['CONTENT_TYPE'] = $_SERVER['CONTENT_TYPE'];
			}
			if (isset($_SERVER['CONTENT_LENGTH'])) {
				$headers['CONTENT_LENGTH'] = $_SERVER['CONTENT_LENGTH'];
			}
		}
		if (empty($headers['HOST'])) {
			if (isset($_SERVER['HTTP_HOST'])) {
				$headers['HOST'] = $_SERVER['HTTP_HOST'];
			}  elseif (isset($_SERVER['SERVER_NAME'])) {
				$_SERVER['HOST'] = $_SERVER['SERVER_NAME'];
				if (isset($_SERVER['SERVER_PORT']) && !in_array($_SERVER['SERVER_PORT'], ['80', '443'])) {
					$_SERVER['HOST'] = $_SERVER['SERVER_PORT'];
				}
			} else {
				$headers['HOST'] = self::$_defaultHost;
			}
		}
		unset($headers['X_ORIGINAL_URL']);


		if (!empty($_POST)) {
			$posts = $_POST;
		} elseif (!empty($_SERVER['CONTENT_LENGTH']) && !empty($_SERVER['REQUEST_METHOD']) && !empty($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_LENGTH'] > 1 && $_SERVER['CONTENT_LENGTH'] > self::$_postLength && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH', 'DELETE']) && (stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false || stripos($_SERVER['CONTENT_TYPE'], 'text/json') !== false)) {
			$posts = ($jsons = json_decode(trim(file_get_contents('php://input', 'rb')), true)) ? $jsons : [];
		} else {
			$posts = [];
		}





		$IP = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
		if (empty($_SERVER['LOLI']['IP'])) {

		} elseif (isset($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
			$IP = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) as $value) {
				if (filter_var($value = trim($value), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) && (empty($_SERVER['SERVER_ADDR']) || $_SERVER['SERVER_ADDR'] != $value)) {
					$IP = $value;
					break;
				}
			}
		}


		unset($_SERVER['UNENCODED_URL'], $_SERVER['HTTP_X_ORIGINAL_URL'], $_SERVER['PATH_INFO'], $_SERVER['ORIG_PATH_INFO'], $_SERVER['QUERY_STRING'], $_SERVER['REDIRECT_QUERY_STRING'], $_SERVER['REDIRECT_URL'], $_SERVER['SERVER_PORT_SECURE']);


		self::setScheme($scheme);
		self::setVersion(empty($_SERVER['SERVER_PROTOCOL']) ? 1.1 : $_SERVER['SERVER_PROTOCOL']);
		self::setMethod(empty($_SERVER['REQUEST_METHOD']) ? 'GET' : $_SERVER['REQUEST_METHOD']);
		self::setURI($URI);
		self::setHeaders($headers);
		self::setPosts($posts);
		self::setParams(array_merge($_GET, $posts));
		self::setContent('php://input');
		self::setIP($IP);
	}





	public static function getScheme() {
		return $_SERVER['REQUEST_SCHEME'];
	}


	public static function setScheme($scheme) {
		if (!in_array($scheme = strtolower($scheme), self::$_schemes)) {
			throw new Exception('The scheme does not allow');
		}
		$_SERVER['REQUEST_SCHEME'] = $scheme;
		$_SERVER['HTTPS'] = substr($scheme, -1) === 's' ? 'on' : 'off';
		$_SERVER['SERVER_PORT_SECURE'] = $_SERVER['HTTPS'] === 'on' ? '1' : '0';
		return true;
	}


	public static function getVersion() {
		return substr($_SERVER['SERVER_PROTOCOL'], 5);
	}

	public static function setVersion($version) {
		$_SERVER['SERVER_PROTOCOL'] = 'HTTP/' . ($version == 1.0 || $version === 'HTTP/1.0' ? '1.0' : '1.1');
		return true;
	}

	public static function getMethod() {
		return $_SERVER['REQUEST_METHOD'];
	}


	public static function setMethod($method) {
		if (!in_array($method = strtoupper($method), self::$_methods)) {
			throw new Exception('Method Not Allowed');
		}
		$_SERVER['REQUEST_METHOD'] = $method;
		return true;
	}


	public static function getURI() {
		return $_SERVER['REQUEST_URI'];
	}

	public static function setURI($URI) {
		$URI = ltrim(trim($URI), '/');
		list($path, $queryString) = explode('?', $URI, 2) + [1 => ''];
		$_SERVER['REQUEST_URI'] = '/'.$URI;
		$_SERVER['QUERY_STRING'] = $queryString;
		$_GET = $queryString ? parse_string($queryString) : [];
		return true;
	}


	public static function getQuerys() {
		return $_GET;
	}

	public static function getQuery($name, $defaultValue = NULL) {
		return isset($_GET[$name]) ? ($defaultValue === NULL ? $_GET[$name] : settype($_GET[$name], gettype($defaultValue))) : $defaultValue;
	}

	public static function setQuerys(array $querys) {
		$_GET = parse_string($queryString = merge_string($querys));
		$_SERVER['REQUEST_URI'] = implode('?', array_filter([1=> $queryString] + explode('?', $_SERVER['REQUEST_URI'], 2)));
		$_SERVER['QUERY_STRING'] = $queryString;
		return true;
	}

	public static function setQuery($name, $value) {
		if ($value === NULL || $value === false) {
			isset($_GET[$name]) && self::setQuerys([$name=>NULL] + $_GET);
		} else {
			$_GET[$name] = is_array($value) || is_object($value) ? parse_string(merge_string($value)) : (string) $value;
			$_SERVER['QUERY_STRING'] .= ($_SERVER['QUERY_STRING'] ? '&' : '') . merge_string([$name=>$value]);
		}
		return true;
	}


	// 默认 headers 头
	public static function getHeaders() {
		$headers = [];
		foreach ($_SERVER as $name => $value) {
			if (substr($name, 0, 5) === 'HTTP_') {
				$headers[strtr(ucwords(strtolower(strtr(substr($name, 5), '_', ' '))), ' ', '-')] = $value;
			}
		}
		return $headers;
	}

	// 默认 headers 头
	public static function getHeader($name, $defaultValue = NULL) {
		return isset($_SERVER[$name = strtoupper(strtr($name, '-', '_'))]) ? $_SERVER[$name] : $defaultValue;
	}


	public static function setHeaders(array $headers) {
		$_SERVER['CONTENT_TYPE'] = '';
		$_SERVER['CONTENT_LENGTH'] = '0';
		unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
		$_COOKIE = [];
		foreach ($_SERVER as $name => $value) {
			if (substr($name, 0, 5) === 'HTTP_') {
				unset($_SERVER[$name]);
			}
		}
		self::setHeader('Host', self::$_defaultHost);
		foreach($headers as $name => $value) {
			self::setHeader($name, $value);
		}
		return true;
	}

	public static function setHeader($name, $value) {
		$name = strtoupper(strtr($name, '-', '_'));
		if ($value === NULL || $value === false) {
			switch ($name) {
				case 'HOST':
					throw new Exception('You can not remove host');
					break;
				case 'CONTENT_TYPE':
					$_SERVER['CONTENT_TYPE'] = '';
					break;
				case 'CONTENT_LENGTH':
					$_SERVER['CONTENT_LENGTH'] = '0';
					break;
				case 'COOKIE':
					$_COOKIE = [];
					break;
				case 'AUTHORIZATION':
					unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
					break;
			}
			unset($_SERVER['HTTP_' . $name]);
		} else {
			$value = rtrim(trim((string)$value), ';');
			switch ($name) {
				case 'HOST':
					$value = $value ? strtolower($value) : self::$_defaultHost;
					break;
				case 'CONTENT_TYPE':
					$_SERVER['CONTENT_TYPE'] = $value;
					break;
				case 'CONTENT_LENGTH':
					$value = (string) abs((int) $value);
					$_SERVER['CONTENT_LENGTH'] = $value;
					break;
				case 'COOKIE':
					$_COOKIE = parse_string(preg_replace('/;\s*/', '&', $value));
					$value = http_build_query($_COOKIE, NULL, '; ');
					break;
				case 'AUTHORIZATION':
					unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
					if (count($auth = explode(' ', $value, 2)) === 2 && $auth[1] && ($auth = base64_decode(trim($auth[1])))) {
						$auth = explode(':', $auth, 2);
						$_SERVER['PHP_AUTH_USER'] = $auth[0];
						$_SERVER['PHP_AUTH_PW'] = isset($auth[1]) ? $auth[1] : '';
					}
					break;
			}
			$_SERVER['HTTP_' . $name] = $value;
		}
		return true;
	}



	public static function getCookies() {
		return $_COOKIE;
	}

	public static function getCookie($name, $defaultValue = NULL) {
		return isset($_COOKIE[$name]) ? ($defaultValue === NULL ? $_COOKIE[$name] : settype($_COOKIE[$name], gettype($defaultValue))) : $defaultValue;
	}


	public static function setCookies(array $cookies) {
		//=,; \t\r\n\013\014
		$_COOKIE = parse_string(merge_string($cookies));
		$_SERVER['HTTP_COOKIE'] = http_build_query($_COOKIE, NULL, '; ');
		return true;
	}

	public static function setCookie($name, $value) {
		if ($value === NULL || $value === false) {
			isset($_COOKIE[$name]) && self::setCookies([$name=>NULL] + $_COOKIE);
		} else {
			$_COOKIE[$name] = is_array($value) || is_object($value) ? parse_string(merge_string($value)) : (string) $value;
			$_SERVER['HTTP_COOKIE'] .= (empty($_SERVER['HTTP_COOKIE']) ? '; ' : '') . merge_string([$name=>$value]);
		}
		return true;
	}


	public static function getPosts() {
		return $_POST;
	}

	public static function getPost($name, $defaultValue = NULL) {
		return isset($_POST[$name]) ? ($defaultValue === NULL ? $_POST[$name] : settype($_POST[$name], gettype($defaultValue))) : $defaultValue;
	}

	public static function setPosts(array $posts) {
		$_POST = parse_string(merge_string($posts));
		return true;
	}

	public static function setPost($name, $value) {
		if ($value === NULL || $value === false) {
			unset($_POST[$name]);
		} else {
			$_POST[$name] = is_array($value) || is_object($value) ? parse_string(merge_string($value)) : (string) $value;
		}
		return true;
	}


	public static function getContent() {
		return self::$_content;
	}

	public static function setContent($content) {
		return self::$_content = $content;
	}


	public static function getParams() {
		return $_REQUEST;
	}

	public static function getFiles(){
		return $_FILES;
	}

	public static function getFile($name) {
		return isset($_FILES[$name]) ? $name : false;
	}


	public static function setFiles(array $files) {
		$_FILES = [];
		foreach ($files as $key => $value) {
			self::setFile($key, $value);
		}
		return true;
	}

	public static function setFile($key, array $value) {
		unset($_FILES[$key]);
		if (!$value) {
			return true;
		}
		if (empty($value['tmp_name'])) {
			throw new Exception('Set file path can not be empty');
		}
		if (is_array($value['tmp_name'])) {
			foreach($value['tmp_name'] as $k => $v) {
				self::addFile($key, $value['tmp_name'][$k], empty($value['name'][$k]) ? false : $value['name'][$k], empty($value['type'][$k]) ? false : $value['type'][$k], isset($value['error'][$k]) ? $value['error'][$k] : UPLOAD_ERR_OK, isset($value['size'][$k]) ? $value['size'][$k] : false);
			}
		} else {
			self::addFile($key, $value['tmp_name'], empty($value['name']) ? false : $value['name'], empty($value['type']) ? false : $value['type'], isset($value['error']) ? $value['error'] : UPLOAD_ERR_OK, isset($value['size']) ? $value['size'] : false);
		}
		return true;
	}


	public static function addFile($key, $tmp_name, $name, $type, $error = UPLOAD_ERR_OK, $size = false) {
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
			$_FILES[$key][$k] = isset($_FILES[$key][$k]) ? array_merge((array) $_FILES[$key][$k], [$v]) : $v;
		}
		return true;
	}


	public static function getParam($name, $defaultValue = NULL) {
		return isset($_REQUEST[$name]) ? ($defaultValue === NULL ? $_REQUEST[$name] : settype($_REQUEST[$name], gettype($defaultValue))) : $defaultValue;
	}

	public static function setParams(array $params) {
		$_REQUEST = parse_string(merge_string($params));
		return true;
	}


	public static function getIP() {
		return $_SERVER['REMOTE_ADDR'];
	}
	public static function setIP($IP) {
		if(!$IP = inet_pton($IP)){
			throw new Exception('IP is not legitimate');
		}
		$IP = inet_ntop($IP);
		// 兼容请求地址
		if (preg_match('/\:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/', $IP, $matches)) {
			$IP = $matches[1];
		}
		$_SERVER['REMOTE_ADDR'] = $IP;
		return true;
	}


	public static function getUsername() {
		return isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : false;
	}

	public static function setUsername($username) {
		return self::setHeader('AUTHORIZATION', 'Basic ' . base64_encode($username .':' . self::getPassword()));
	}


	public static function getPassword() {
		return isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : false;
	}

	public static function setPassword($password) {
		return self::setHeader('AUTHORIZATION', 'Basic ' . base64_encode(self::getUsername() .':' . $password));
	}


	public static function getPath() {
		return explode('?', self::getURI(), 2)[0];
	}

	public static function newToken() {
		$token = uniqid();
		$token .= mb_rand(16 - strlen($token), '0123456789qwertyuiopasdfghjklzxcvbnm');
		$token .= Code::key(__CLASS__ . self::TOKEN_HEADER . $token, 16);
		return $token;
	}
	public static function hasNewToken() {
		return self::$_newToken;
	}

	public static function getToken($isKey = false) {
		if (self::$_token === NULL) {
			($token = self::getHeader(self::TOKEN_HEADER)) || ($token = self::getCookie(self::TOKEN_COOKIE));
			try {
				self::setToken(self::$_token);
			} catch (Exception $e) {
				self::setToken(self::newToken(), true);
			}
		}
		return $isKey ? self::$_token : substr(self::$_token, 0, 16);
	}

	public static function setToken($token, $newToken = false) {
		if ($token === NULL || $token === false) {
			self::$_token = NULL;
		} else {
			if (!is_string($token) || strlen($token) != 32 || Code::key(__CLASS__ . self::TOKEN_HEADER . substr($token, 0, 16), 16) !== substr($token, 16)) {
				throw new Exception('Access token is invalid');
			}
			self::$_token = $token;
		}
		self::$_newToken = $newToken;
		return true;
	}



	public static function getAjax() {
		if (self::$_ajax === NULL) {
			if ($header = self::getHeader(self::AJAX_HEADER)) {
				self::$_ajax = $header;
			} elseif ($param = self::getParam(self::AJAX_PARAM, '')) {
				self::$_ajax = $param;
			} elseif (in_array($extension = strtolower(pathinfo(self::getPath(), PATHINFO_EXTENSION)), ['json', 'xml'])) {
				self::$_ajax = $extension;
			} elseif (($accept = self::getHeader('ACCEPT')) && ($mimeType = explode(',', $accept, 2)[0]) && in_array($extension = strtolower(trim(explode('/', $mimeType, 2)[0])), ['json', 'xml'])) {
				self::$_ajax = $extension;
			} elseif (strtolower(self::getHeader('X_REQUESTED_WITH')) === 'xmlhttprequest') {
				self::$_ajax = 'json';
			} else {
				self::$_ajax = '';
			}
		}
		return self::$_ajax;
	}

	public static function setAjax($ajax) {
		self::$_ajax = $ajax === NULL ? NULL : (string) $ajax;
		return true;
	}


	public static function isPjax() {
		return self::getHeader(PJAX_HEADER);
	}


	public static function getRanges() {
		$ranges = [];
		if (($range = self::getHeader('RANGE')) && preg_match('/bytes=\s*([0-9-,]+)/i', $range, $matches)) {
			foreach (explode(',', $matches[1]) as $subject) {
				if (preg_match('/(\-?\d+)(?:\-(\d+)?)?/', $subject, $matches)) {
					$offset = intval($matches[1]);
					$length = isset($matches[2]) ? $matches[2] - $offset + 1 : false;
					if ($length === false || $length > 0) {
						$ranges[] = ['offset' => $offset, 'length' => $length];
					}
				}
			}
		}
		return $ranges;
	}



	public static function flush() {
		self::setScheme('http');
		self::setVersion(1.1);
		self::setMethod('GET');
		self::setURI('/');
		self::setHeaders(['Host' => self::$_defaultHost]);
		self::setPosts([]);
		self::setParams([]);
		self::setFiles([]);
		self::setContent('php://input');
		self::setIP('127.0.0.1');
		self::setToken(NULL);
		self::setAjax(NULL);
	}
}
Request::init();