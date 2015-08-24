<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-04-03 14:34:47
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
/*	Created: UTC 2015-02-06 14:16:56
/*	Updated: UTC 2015-04-03 14:34:33
/*
/* ************************************************************************** */
namespace Loli\HTTP;
use Loli\Code, Loli\Storage;
class Request{
	const TOKEN_HEADER = 'X-Token';

	const TOKEN_COOKIE = 'token';

	const AJAX_HEADER = 'X-Ajax';

	const AJAX_PARAM = 'ajax';

	const PJAX_HEADER = 'X-Pjax';

	protected static $schemesList = ['http', 'https'];

	protected static $methodsList = ['OPTIONS', 'GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'PATCH'];

	protected static $defaultHost = 'localhost';



	protected $postLength = 2097152;

	protected $addr = false;

	protected $port = 0;



	protected $IP = '127.0.0.1';






	protected $scheme = 'http';

	protected $version = 1.1;

	protected $method = 'GET';

	protected $URI = '/';

	protected $querys = [];

	protected $headers = [];

	protected $cookies = [];

	protected $username = false;

	protected $password = false;

	protected $posts = [];

	protected $files = [];

	protected $content = 'php://input';





	protected $newToken = false;

	protected $token = NULL;

	protected $ajax = NULL;

	protected $time;


	public function __construct($method = NULL, $URI = NULL, array $headers = NULL, array $posts = NULL, array $files = NULL) {

		$this->time = empty($_SERVER['REQUEST_TIME_FLOAT']) ? microtime(true) : $_SERVER['REQUEST_TIME_FLOAT'];

		// addr
		$addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 0;

		// port
		$port = isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : 0;

		// IP
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


		// 协议
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



		// 方法
		$method = $method === NULL ? (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET') : $method;



		// URI
		if ($URI === NULL) {
			if (!empty($_SERVER['UNENCODED_URL'])) {
				$URI = $_SERVER['UNENCODED_URL'];
			} elseif (!empty($_SERVER['HTTP_X_ORIGINAL_URL'])) {
				$URI = $_SERVER['HTTP_X_ORIGINAL_URL'];
			} elseif (!empty($_SERVER['REQUEST_URI'])) {
				$URI = $_SERVER['REQUEST_URI'];
			} elseif (isset($_SERVER['PATH_INFO']) && isset($_SERVER['SCRIPT_NAME'])) {
				if ($_SERVER['PATH_INFO'] === $_SERVER['SCRIPT_NAME']) {
					$URI = $_SERVER['PATH_INFO'];
				} else {
					$URI = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
				}
			} else {
				$URI = '/';
			}
		}


		// 版本
		$version = !empty($_SERVER['SERVER_PROTOCOL']) && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.0' ? 1.0 : ($_SERVER['SERVER_PROTOCOL'] === 'HTTP/2.0' ? 2.0 : 1.1);


		if ($headers !== NULL) {

		} elseif (function_exists('getallheaders')) {
			$headers = getallheaders();
		} elseif (function_exists('http_get_request_headers')) {
			$headers = http_get_request_headers();
		} else {
			$headers = [];
			foreach ($_SERVER as $name => $value) {
				if (substr($name, 0, 5) === 'HTTP_') {
					$headers[strtr(ucwords(strtolower(strtr(substr($name, 5), '_', ' '))), ' ', '-')] = $value;
				}
			}
			if (isset($_SERVER['CONTENT_TYPE'])) {
				$headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
			}
			if (isset($_SERVER['CONTENT_LENGTH'])) {
				$headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
			}
		}
		unset($headers['X-Original-Url']);


		if (empty($headers['Host'])) {
			if (isset($_SERVER['HTTP_HOST'])) {
				$headers['Host'] = $_SERVER['HTTP_HOST'];
			}  elseif (isset($_SERVER['SERVER_NAME'])) {
				$_SERVER['Host'] = $_SERVER['SERVER_NAME'];
				if (isset($_SERVER['SERVER_PORT']) && !in_array($_SERVER['SERVER_PORT'], ['80', '443'], true)) {
					$_SERVER['Host'] .= ':' . $_SERVER['SERVER_PORT'];
				}
			} else {
				$headers['Host'] = self::$defaultHost;
			}
		}




		if ($posts !== NULL) {
		} elseif ($_POST || empty($_SERVER['CONTENT_TYPE']) || empty($_SERVER['CONTENT_LENGTH']) || $_SERVER['CONTENT_LENGTH'] < 1 || $_SERVER['CONTENT_LENGTH'] > $this->postLength) {
			$posts = $_POST;
		} elseif (in_array($_SERVER['CONTENT_TYPE'], ['application/json', 'text/json'])) {
			$posts = ($jsons = json_decode(trim(file_get_contents('php://input', 'rb')), true)) ? $jsons : [];
		} else {
			$posts = [];
		}


		// 文件
		if ($files === NULL) {
			$files = $_FILES;
		}



		//  地址
		$this->setAddr($addr);

		// 端口
		$this->setPort($port);

		// ip
		$this->setIP($IP);

		// 方法
		$this->setMethod($method);

		// URI
		$this->setURI($URI);

		// 版本
		$this->setversion($version);

		// headers
		$this->setHeaders($headers);

		// 表单
		$this->setPosts($posts);

		// 文件
		$this->setFiles($files);

		// 设置 param
		$this->setParams(array_merge($this->getQuerys(), $this->getPosts()));
	}



	public function getTime() {
		return $this->time;
	}

	public function setTime() {
		$this->time = microtime(true);
		return true;
	}

	public function processing($decimal = 4) {
		return number_format(microtime(true) - $this->time, $decimal);
	}



	public function getIP() {
		return $this->IP;
	}



	public function setIP($IP) {
		if(!$IP = inet_pton($IP)) {
			throw new Exception('IP is not legitimate');
		}
		$IP = inet_ntop($IP);

		// 兼容请求地址
		if (preg_match('/\:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/', $IP, $matches)) {
			$IP = $matches[1];
		}
		$this->IP = $IP;
		return $this;
	}





	public function getAddr($port = false) {
		return $this->addr;
	}


	public function setAddr($addr) {
		if ($addr) {
			if(!$addr = inet_pton($addr)) {
				throw new Exception('Addr is not legitimate');
			}
			$addr = inet_ntop($addr);
		} else {
			$addr = false;
		}
		$this->addr = $addr;
		return $this;
	}


	public function getPort() {
		return $this->port;
	}

	public function setPort($port) {
		if($port > 65535 || $port < 0){
			throw new Exception('Port is not legitimate');
		}
		$this->port = (int) $port;
		return $this;
	}




	public function getScheme() {
		return $this->scheme;
	}

	public function setScheme($scheme) {
		if (!in_array($scheme, self::$schemesList, true)) {
			throw new Exception('The scheme does not allow', 400);
		}
		$this->scheme = $scheme;
		return $this;
	}






	public function getVersion() {
		return $this->version;
	}

	public function setVersion($version) {
		$this->version = $version == 1.0 ? 1.0 : ($version == 2.0 ? 2.0 : 1.1);
		return $this;
	}


	public function getMethod() {
		return $this->method;
	}

	public function setMethod($method) {
		if (!in_array($method, self::$methodsList, true)) {
			throw new Exception('The method does not allow', 501);
		}
		$this->method = $method;
		return $this;
	}






	public function getURI() {
		return $this->URI;
	}

	public function setURI($URI) {
		$URI = '/' . ltrim(trim($URI), '/');
		list($path, $queryString) = explode('?', $URI, 2) + [1 => ''];
		$this->URI = $URI;
		$this->querys = $queryString ? parse_string($queryString) : [];
		return $this;
	}


	public function getQuerys() {
		return $this->querys;
	}

	public function getQuery($name, $defaultValue = NULL) {
		if (isset($this->querys[$name])) {
			if ($defaultValue === NULL) {
				return $this->querys[$name];
			}
			$value = $this->querys[$name];
			settype($value, gettype($defaultValue));
			return $value;
		} else {
			return $defaultValue;
		}
	}

	public function setQuerys(array $querys) {
		$this->querys = parse_string($queryString = merge_string($querys));
		$this->URI = explode('?', $this->URI, 2)[0] . ($queryString ? '?' . $queryString : '');
		return $this;
	}

	public function setQuery($name, $value) {
		if ($value === NULL) {
			isset($this->querys[$name]) && $this->setQuerys([$name => NULL] + $this->querys);
		} else {
			$this->querys[$name] = is_array($value) || is_object($value) ? parse_string(merge_string($value)) : (string) $value;
			$this->URI .= (strpos($this->URI, '?') === false ? '?' : '&') . merge_string([$name => $value]);
		}
		return $this;
	}













	public function getHeaders() {
		return $this->headers;
	}

	public function getHeader($name, $defaultValue = NULL) {
		return isset($this->headers[$name]) ? $this->headers[$name] : $defaultValue;
	}

	public function setHeaders(array $headers) {
		$this->cookies = [];
		$this->username = $this->password = false;
		$this->setHeader('Host', self::$defaultHost);
		foreach($headers as $name => $value) {
			$this->setHeader($name, $value);
		}
		return $this;
	}


	public function setHeader($name, $value) {
		if ($value === NULL) {
			switch ($name) {
				case 'Host':
					throw new Exception('You can not remove host');
					break;
				case 'Cookie':
					$this->cookies = [];
					break;
				case 'Authorization':
					$this->username = $this->password = false;
					break;
			}
			unset($this->headers[$name]);
		} else {
			$value = rtrim(trim((string)$value), ';');
			switch ($name) {
				case 'Host':
					$value = $value ? strtolower($value) : self::$defaultHost;
					if (!preg_match('/^([0-9a-z_-]+\.)*[0-9a-z_-]+$/', $value)) {
						throw new Exception('Host name error', 400);
					}
					break;
				case 'Content-Length':
					$value = (string) abs((int) $value);
					break;
				case 'Cookie':
					$this->cookies = parse_string(preg_replace('/;\s*/', '&', $value));
					$value = http_build_query($this->cookies, NULL, '; ');
					break;
				case 'Authorization':
					$this->username = $this->password = false;
					if (count($auth = explode(' ', $value, 2)) === 2 && $auth[1] && ($auth = base64_decode(trim($auth[1])))) {
						$auth = explode(':', $auth, 2);
						$this->username = $auth[0];
						$this->password = isset($auth[1]) ? $auth[1] : '';
					}
					break;
			}
			$this->headers[$name] = $value;
		}
		return $this;
	}











	public function getCookies() {
		return $this->cookies;
	}

	public function getCookie($name, $defaultValue = NULL) {
		if (isset($this->cookies[$name])) {
			if ($defaultValue === NULL) {
				return $this->cookies[$name];
			}
			$value = $this->cookies[$name];
			settype($value, gettype($defaultValue));
			return $value;
		} else {
			return $defaultValue;
		}
	}


	public function setCookies(array $cookies) {
		//=,; \t\r\n\013\014
		$this->cookies = parse_string(merge_string($cookies));
		$this->headers['Cookie'] = http_build_query($this->cookies, NULL, '; ');
		return $this;
	}


	public function setCookie($name, $value) {
		if ($value === NULL) {
			isset($this->cookies[$name]) && $this->setCookies([$name=>NULL] + $this->cookies);
		} else {
			$this->cookies[$name] = is_array($value) || is_object($value) ? parse_string(merge_string($value)) : (string) $value;
			$this->headers['Cookie'] = $this->headers['Cookie'] . (empty($this->headers['Cookie']) ? '; ' : '') . merge_string([$name=>$value]);
		}
		return $this;
	}



	public function getUsername() {
		return $this->username;
	}


	public function setUsername($username) {
		return $this->setHeader('Authorization', 'Basic ' . base64_encode($username .':' . $this->getPassword()));
	}

	public function getPassword() {
		return $this->password;
	}

	public function setPassword($password) {
		return $this->setHeader('Authorization', 'Basic ' . base64_encode($this->getUsername() .':' . $password));
	}








	public function getPosts() {
		return $this->posts;
	}

	public function getPost($name, $defaultValue = NULL) {
		if (isset($this->posts[$name])) {
			if ($defaultValue === NULL) {
				return $this->posts[$name];
			}
			$value = $this->posts[$name];
			settype($value, gettype($defaultValue));
			return $value;
		} else {
			return $defaultValue;
		}
	}

	public function setPosts(array $requests) {
		$this->posts = parse_string(merge_string($requests));
		return true;
	}

	public function setPost($name, $value) {
		if ($value === NULL) {
			unset($this->posts[$name]);
		} else {
			$this->posts[$name] = is_array($value) || is_object($value) ? parse_string(merge_string($value)) : (string) $value;
		}
		return true;
	}








	public function setParams(array $params) {
		$this->params = parse_string(merge_string($params));
		return $this;
	}

	public function setParam($name, $defaultValue = NULL) {
		if ($value === NULL) {
			unset($this->params[$name]);
		} else {
			$this->params[$name] = is_array($value) || is_object($value) ? parse_string(merge_string($value)) : (string) $value;
		}
		return true;
	}


	public function getParams() {
		return $this->params;
	}


	public function getParam($name, $defaultValue = NULL) {
		if (isset($this->params[$name])) {
			if ($defaultValue === NULL) {
				return $this->params[$name];
			}
			$value = $this->params[$name];
			settype($value, gettype($defaultValue));
			return $value;
		} else {
			return $defaultValue;
		}
	}



	public function getFiles($size = 0, array $mimeType = NULL, $multiple = 1) {
		$files = [];
		foreach ($this->files as $key => $values) {
			foreach ($values as $value) {
				if ($multiple > 0 || $multiple == -1) {
					if ($multiple != -1) {
						--$multiple;
					}
					$files[$key][] = $this->_getFile($value, $size, $mimeType);
				}
			}
		}
		return $files;
	}


	public function getFile($key, $size = 0, array $mimeType = NULL, $multiple = 1) {
		if (empty($this->files[$key])) {
			return [];
		}
		$files = [];
		foreach ($this->files[$key] as $value) {
			if ($multiple > 0 || $multiple == -1) {
				if ($multiple != -1) {
					--$multiple;
				}
				$files[] = $this->_getFile($value, $size, $mimeType);
			}
		}
		return $files;
	}

	private function _getFile($file, $size, array $mimeType) {
		$file += pathinfo($file['name']) + ['dirname' =>'', 'basename' => '', 'extension' => '', 'filename' => '', 'encoding' => ''];

		if ($file['error'] !== UPLOAD_ERR_OK) {
			// 有错误的
		} elseif (!is_file($file['tmp_name'])) {
			// 文件不存在
			$file['error'] = UPLOAD_ERR_NO_FILE;
		} elseif (!$mime = Storage::mime($file['tmp_name'])) {
			// 后缀
			$file['error'] = UPLOAD_ERR_EXTENSION;
		} elseif ($mimeType && !in_array($mime['type'], $mimeType) && !in_array(strtolower($file['extension']), $mimeType)) {
			// 后缀
			$file['error'] = UPLOAD_ERR_EXTENSION;
		} elseif ($size && $file['size'] > $size) {
			// 文件大小
			$file['error'] = UPLOAD_ERR_FORM_SIZE;
		}
		// 合并
		if (!empty($mime)) {
			$file = $mime + $file;
		}
		return $file;
	}



	public function setFiles(array $files) {
		$this->files = [];
		foreach ($this->files as $key => $value) {
			if (!$value) {
				continue;
			}
			if (empty($value['tmp_name'])) {
				throw new Exception('Set file path can not be empty');
			}
			$this->addFile($key, $value['tmp_name'], empty($value['name']) ? NULL : $value['name'], empty($value['type']) ? NULL : $value['type'], isset($value['error']) ? $value['error'] : UPLOAD_ERR_OK, isset($value['size']) ? $value['size'] : NULL);
		}
	}

	public function setFile($key, $tmp_name, $name, $type = NULL, $error = UPLOAD_ERR_OK, $size = NULL) {
		unset($this->files[$key]);
		return call_user_func_array([$this, 'addFile'], func_get_args());
	}

	public function addFile($key, $tmp_name, $name, $type = NULL, $error = UPLOAD_ERR_OK, $size = NULL) {
		if (is_array($tmp_name)) {
			foreach ($tmp_name as $k => $value) {
				$this->addFile($key, $value, $name ? (is_array($name) ? (isset($name[$k]) ? $name[$k] : NULL) : $name) : NULL, $type ? (is_array($type) ? (isset($type[$k]) ? $type[$k] : NULL) : $type) : NULL, $error ? (is_array($error) ? (isset($error[$k]) ? $error[$k] : UPLOAD_ERR_OK) : $error) : UPLOAD_ERR_OK, $size ? (is_array($size) ? (isset($size[$k]) ? $size[$k] : UPLOAD_ERR_OK) : $size) : UPLOAD_ERR_OK);
			}
		} else {
			$error = abs((int) $error);
			$error = $error ? $error : UPLOAD_ERR_OK;
			if ($error === UPLOAD_ERR_OK && !is_file($tmp_name)) {
				throw new Exception('File does not exist');
			}
			$size = $size === false || $size === NULL && $error === UPLOAD_ERR_OK ? filesize($tmp_name) : abs((int)$size);
			$name = $name ? (string) pathinfo((string) $name, PATHINFO_BASENAME) : 'Unknown';
			$type = $type ? (string) $type : 'application/octet-stream';
			$this->files[$key][] = ['tmp_name' => $tmp_name, 'name' => $name, 'type' => $type, 'error' => $error];
		}
		return $this;
	}





	public function getContent() {
		if (!$this->content) {
			return false;
		}
		if (!is_resource($this->content)) {
			$this->content = fopen($this->content, 'rb');
		} else {
			fseek($this->content, 0);
		}
		return $this->content;
	}


	public function setContent($content) {
		$this->content = $content;
		return $this;
	}












	public function getQueryString() {
		return merge_string($this->querys);
	}


	public function getPath() {
		return explode('?', $this->getURI(), 2)[0];
	}

	public function getURL() {
		return $this->getScheme() . '://' . $this->getHeader('Host') . $this->URI;
	}




	public function newToken() {
		$token = uniqid();
		$token .= Code::rand(16 - strlen($token), '0123456789qwertyuiopasdfghjklzxcvbnm');
		$token .= Code::key(__CLASS__ . self::TOKEN_HEADER . $token, 16);
		return $token;
	}

	public function isNewToken() {
		return $this->newToken;
	}



	public function getToken($isKey = false, $isNew = true) {
		if ($this->token === NULL) {
			($token = $this->getHeader(self::TOKEN_HEADER)) || ($token = $this->getCookie(self::TOKEN_COOKIE));
			if ($token === NULl) {
				$isNew && $this->setToken($this->newToken(), true);
			} else {
				try {
					$this->setToken($token);
				} catch (Exception $e) {
					$isNew && $this->setToken($this->newToken(), true);
				}
			}
		}
		return $this->token ? ($isKey ? $this->token : substr($this->token, 0, 16)) : $this->token;
	}

	public function setToken($token, $newToken = false) {
		if ($token === NULL || $token === false) {
			$this->token = NULL;
		} else {
			if (!is_string($token) || strlen($token) != 32 || Code::key(__CLASS__ . self::TOKEN_HEADER . substr($token, 0, 16), 16) !== substr($token, 16)) {
				throw new Exception('Access token is invalid', 403);
			}
			$this->token = $token;
		}
		$this->newToken = $newToken;
		return true;
	}






	public function getAjax() {
		if ($this->ajax !== NULL) {

		} elseif ($header = $this->getHeader(self::AJAX_HEADER)) {
			$this->ajax = $header;
		} elseif ($param = $this->getParam(self::AJAX_PARAM, '')) {
			$this->ajax = $param;
		} elseif (in_array($extension = strtolower(pathinfo($this->getPath(), PATHINFO_EXTENSION)), ['json', 'xml'])) {
			$this->ajax = $extension;
		} elseif (($accepts = $this->getAccepts()) && in_array($extension = trim(explode('/', reset($accepts), 2)[0]), ['json', 'xml'])) {
			$this->ajax = $extension;
		} elseif (strtolower($this->getHeader('X-Requested-With')) === 'xmlhttprequest') {
			$this->ajax = 'json';
		} else {
			$this->ajax = '';
		}
		return $this->ajax;
	}

	public function setAjax($ajax) {
		$this->ajax = $ajax === NULL ? NULL : (string) $ajax;
		return true;
	}


	public function isPjax() {
		return $this->getHeader(self::PJAX_HEADER);
	}


	public function getRanges() {
		$ranges = [];
		if (($range = $this->getHeader('Range')) && preg_match('/bytes=\s*([0-9-,]+)/i', $range, $matches)) {
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


	public function getAccepts() {
		return $this->parseAccept('Access', '/([a-z]+|*)\/([0-9a-z._+-]+|*)^$/', 'strtolower');
	}


	public function getAcceptLanguages() {
		return $this->parseAccept('Access-Language', '/^([a-z]{2})(?:[_-]([a-z]{3-4}))?(?:[_-]([a-z]{2}))?$/', function($arg1, $arg2, $arg3, $arg4) {
			$array[] = strtolower($arg2);
			if ($arg3) {
				$array[] = ucwords($arg3);
			}
			if ($arg4) {
				$array[] = strtoupper($arg4);
			}
			return implode('-', $array);
		});
	}

	protected function parseAccept($name, $pattern, $callback = NULL) {
		$arrays = [];
		foreach (explode(',', $this->getHeader($name)) as $value) {
			if (!$value) {
				continue;
			}
			$value = explode(';', $value);
			if (!preg_match($pattern, $value[0], $matches)) {
				continue;
			}
			if (empty($value[1])) {
				$q = 1;
			} else {
				$value[1] = explode('q=', $value[1], 2) + [1 => 1];
				$q = (float) $value[1][1];
			}

			$accept = $callback ? call_user_func_array($callback, $matches) : $matches[0];
			if ($accept) {
				$arrays[$q][] = $accept;
			}
		}
		krsort($arrays);
		$accepts = [];
		foreach ($arrays as $value) {
			$accepts = array_merge($accepts, $value);
		}
		return $accepts;
	}


	public function flush() {
		$this->addr = false;
		$this->port = 0;
		$this->IP = '127.0.0.1';
		$this->scheme = 'http';
		$this->version = 1.1;
		$this->method = 'GET';
		$this->URI = '/';
		$this->querys = [];
		$this->headers = [];
		$this->cookies = [];
		$this->username = false;
		$this->password = false;
		$this->posts = [];
		$this->files = [];
		$this->content = 'php://input';
		$this->newToken = false;
		$this->token = NULL;
		$this->ajax = NULL;
		$this->time = microtime(true);
		return $this;
	}

}












/*
class Request{
	const TOKEN_HEADER = 'X-Token';

	const TOKEN_COOKIE = 'token';

	const AJAX_HEADER = 'X-Ajax';

	const AJAX_PARAM = 'ajax';

	const PJAX_HEADER = 'X-Pjax';

	protected $schemesList = ['http', 'https'];

	protected $methodsList = ['OPTIONS', 'HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

	protected $defaultHost = 'localhost';

	protected $postLength = 2097152;


	protected $addr = false;

	protected $port = 0;


	protected $IP = '127.0.0.1';




	protected $scheme = 'http';

	protected $version = 1.1;

	protected $method = 'GET';

	protected $URI = '/';

	protected $querys = [];

	protected $headers = [];

	protected $cookies = [];

	protected $username = false;

	protected $password = false;

	protected $posts = [];

	protected $files = [];

	protected $content = 'php://input';


	protected $newToken = false;

	protected $token = NULL;

	protected $ajax = NULL;


	public function __construct($method = NULL, $URI = NULL, array $headers = NULL, array $posts = NULL, array $files = NULL) {


		// addr
		$addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 0;

		// port
		$port = isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : 0;

		// IP
		$IP = self::defaultIP();

		// 协议
		$scheme = self::defaultScheme();

		// 方法
		$method = $method === NULL ? (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET') : $method;

		// URI
		$URI = $URI === NULL ? self::defaultURI() : $URI;

		// 版本
		$version = !empty($_SERVER['SERVER_PROTOCOL']) && $_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.0' ? 1.0 : 1.1;

		// headers
		$headers = $headers === NULL ? self::defaultHeaders() : $headers;

		// 表单
		$posts = $posts === NULL ? self::defaultPosts() : $posts;

		// 文件
		$files = $files === NULL ? $_FILES : $files;


		//  地址
		$this->setAddr($addr);

		// 端口
		$this->setPort($port);

		// ip
		$this->setIP($IP);

		// 方法
		$this->setMethod($method);

		// URI
		$this->setURI($URI);

		// 版本
		$this->setversion($version);

		// headers
		$this->setHeaders($headers);

		// 表单
		$this->setPosts($posts);

		// 文件
		$this->setFiles($files);

		// 设置 param
		$this->setParams(array_merge($this->getQuerys(), $this->getPosts()));
	}


	public static function defaultIP() {
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
		return $IP;
	}

	public static function defaultScheme() {
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
	}


	public static function defaultURI() {
		if (!empty($_SERVER['UNENCODED_URL'])) {
			$URI = $_SERVER['UNENCODED_URL'];
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
		return $URI;
	}

	public static function defaultHeaders() {
		if (function_exists('getallheaders')) {
			$headers = getallheaders();
		} elseif (function_exists('http_get_request_headers')) {
			$headers = http_get_request_headers();
		} else {
			$headers = [];
			foreach ($_SERVER as $name => $value) {
				if (substr($name, 0, 5) === 'HTTP_') {
					$headers[strtr(ucwords(strtolower(strtr(substr($name, 5), '_', ' '))), ' ', '-')] = $value;
				}
			}
			if (isset($_SERVER['CONTENT_TYPE'])) {
				$headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
			}
			if (isset($_SERVER['CONTENT_LENGTH'])) {
				$headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
			}
		}
		if (empty($headers['Host'])) {
			if (isset($_SERVER['HTTP_HOST'])) {
				$headers['Host'] = $_SERVER['HTTP_HOST'];
			}  elseif (isset($_SERVER['SERVER_NAME'])) {
				$_SERVER['Host'] = $_SERVER['SERVER_NAME'];
				if (isset($_SERVER['SERVER_PORT']) && !in_array($_SERVER['SERVER_PORT'], ['80', '443'])) {
					$_SERVER['Host'] .= ':' . $_SERVER['SERVER_PORT'];
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
		if (isset($posts)) {
		} elseif ($_POST || empty($_SERVER['CONTENT_TYPE']) || empty($_SERVER['CONTENT_LENGTH']) || $_SERVER['CONTENT_LENGTH'] < 1 || $_SERVER['CONTENT_LENGTH'] > $this->postLength) {
			$posts = $_POST;
		} elseif (in_array($_SERVER['CONTENT_TYPE'], ['application/json', 'text/json'])) {
			$posts = ($jsons = json_decode(trim(file_get_contents('php://input', 'rb')), true)) ? $jsons : [];
		} else {
			$posts = [];
		}
		return $posts;
	}














	public function getIP() {
		return $this->IP;
	}



	public function setIP($IP) {
		if(!$IP = inet_pton($IP)) {
			throw new Exception('IP is not legitimate');
		}
		$IP = inet_ntop($IP);

		// 兼容请求地址
		if (preg_match('/\:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/', $IP, $matches)) {
			$IP = $matches[1];
		}
		$this->IP = $IP;
		return $this;
	}





	public function getAddr($port = false) {
		return $this->addr;
	}


	public function setAddr($addr) {
		if ($addr) {
			if(!$addr = inet_pton($addr)) {
				throw new Exception('Addr is not legitimate');
			}
			$addr = inet_ntop($addr);
		} else {
			$addr = false;
		}
		$this->addr = $addr;
		return $this;
	}


	public function getPort() {
		return $this->port;
	}

	public function setPort($port) {
		if($port > 65535 || $port < 0){
			throw new Exception('Port is not legitimate');
		}
		$this->port = (int) $port;
		return $this;
	}






	public function getScheme() {
		return $this->scheme;
	}

	public function setScheme($scheme) {
		if (!in_array($scheme, $this->schemesList, true)) {
			throw new Exception('The scheme does not allow');
		}
		$this->scheme = $scheme;
		return $this;
	}






	public function getVersion() {
		return $this->version;
	}

	public function setVersion($version) {
		$this->version = $version == 1.0 ? 1.0 : 1.1;
		return $this;
	}










	public function getMethod() {
		return $this->method;
	}



	public function setMethod($method) {
		if (!in_array($method = strtoupper($method), $this->methodsList)) {
			throw new Exception('The method does not allow');
		}
		$this->method = $method;
		return $this;
	}













	public function getURI() {
		return $this->URI;
	}

	public function setURI($URI) {
		$URI = ltrim(trim($URI), '/');
		list($path, $queryString) = explode('?', $URI, 2) + [1 => ''];
		$this->URI = '/'. $URI;
		$this->querys = parse_string($queryString);
		return $this;
	}









	public function getQuerys() {
		return $this->querys;
	}

	public function getQuery($name, $defaultValue = NULL) {
		return isset($this->querys[$name]) ? ($defaultValue === NULL ? $this->querys[$name] : settype($this->querys[$name], gettype($defaultValue))) : $defaultValue;
	}

	public function setQuerys(array $querys) {
		$this->querys = parse_string($queryString = merge_string($querys));
		implode('?', array_filter([1=> $queryString] + explode('?', $this->URI, 2)));
		return $this;
	}

	public function setQuery($name, $value) {
		if ($value === NULL || $value === false) {
			isset($this->querys[$name]) && $this->setQuerys([$name=>NULL] + $this->querys);
		} else {
			$this->querys[$name] = is_array($value) || is_object($value) ? parse_string(merge_string($value)) : (string) $value;
			$this->URI .= (strpos($this->URI, '?') === false ? '?' : '&') . merge_string([$name=>$value]);
		}
		return $this;
	}








	public function getHeaders() {
		return $this->headers;
	}

	public function getHeader($name, $defaultValue = NULL) {
		return isset($this->headers[$name]) ? $this->headers[$name] : $defaultValue;
	}

	public function setHeaders(array $headers) {
		$this->cookies = [];
		$this->username = $this->password = false;
		$this->setHeader('Host', $this->defaultHost);
		foreach($headers as $name => $value) {
			$this->setHeader($name, $value);
		}
		return $this;
	}


	public function setHeader($name, $value) {
		if ($value === NULL || $value === false) {
			switch ($name) {
				case 'Host':
					throw new Exception('You can not remove host');
					break;
				case 'Cookie':
					$this->cookies = [];
					break;
				case 'Authorization':
					$this->username = $this->password = false;
					break;
			}
			unset($this->headers[$name]);
		} else {
			$value = rtrim(trim((string)$value), ';');
			switch ($name) {
				case 'Host':
					$value = $value ? strtolower($value) : $this->defaultHost;
					break;
				case 'Content-Length':
					$value = (string) abs((int) $value);
					break;
				case 'Cookie':
					$this->cookies = parse_string(preg_replace('/;\s*.........................../', '&', $value));
					$value = http_build_query($this->cookies, NULL, '; ');
					break;
				case 'Authorization':
					$this->username = $this->password = false;
					if (count($auth = explode(' ', $value, 2)) === 2 && $auth[1] && ($auth = base64_decode(trim($auth[1])))) {
						$auth = explode(':', $auth, 2);
						$this->username = $auth[0];
						$this->password = isset($auth[1]) ? $auth[1] : '';
					}
					break;
			}
			$this->headers[$name] = $value;
		}
		return $this;
	}











	public function getCookies() {
		return $this->cookies;
	}

	public function getCookie($name, $defaultValue = NULL) {
		return isset($this->cookies[$name]) ? ($defaultValue === NULL ? $this->cookies[$name] : settype($this->cookies[$name], gettype($defaultValue))) : $defaultValue;
	}


	public function setCookies(array $cookies) {
		//=,; \t\r\n\013\014
		$this->cookies = parse_string(merge_string($cookies));
		$this->headers['Cookie'] = http_build_query($this->cookies, NULL, '; ');
		return $this;
	}


	public function setCookie($name, $value) {
		if ($value === NULL || $value === false) {
			isset($this->cookies[$name]) && $this->setCookies([$name=>NULL] + $this->cookies);
		} else {
			$this->cookies[$name] = is_array($value) || is_object($value) ? parse_string(merge_string($value)) : (string) $value;
			$this->headers['Cookie'] = $this->headers['Cookie'] . (empty($this->headers['Cookie']) ? '; ' : '') . merge_string([$name=>$value]);
		}
		return $this;
	}





	public function getUsername() {
		return $this->username;
	}


	public function setUsername($username) {
		return $this->setHeader('Authorization', 'Basic ' . base64_encode($username .':' . $this->getPassword()));
	}

	public function getPassword() {
		return $this->password;
	}

	public function setPassword($password) {
		return $this->setHeader('Authorization', 'Basic ' . base64_encode($this->getUsername() .':' . $password));
	}








	public function getPosts() {
		return $this->posts;
	}

	public function getPost($name, $defaultValue = NULL) {
		return isset($this->posts[$name]) ? ($defaultValue === NULL ? $this->posts[$name] : settype($this->posts[$name], gettype($defaultValue))) : $defaultValue;
	}

	public function setPosts(array $requests) {
		$this->posts = parse_string(merge_string($requests));
		return true;
	}

	public function setPost($name, $value) {
		if ($value === NULL || $value === false) {
			unset($this->posts[$name]);
		} else {
			$this->posts[$name] = is_array($value) || is_object($value) ? parse_string(merge_string($value)) : (string) $value;
		}
		return true;
	}








	public function setParams(array $params) {
		$this->params = parse_string(merge_string($params));
		return $this;
	}

	public function setParam($name, $defaultValue = NULL) {
		if ($value === NULL || $value === false) {
			unset($this->params[$name]);
		} else {
			$this->params[$name] = is_array($value) || is_object($value) ? parse_string(merge_string($value)) : (string) $value;
		}
		return true;
	}


	public function getParams() {
		return $this->params;
	}


	public function getParam($name, $defaultValue = NULL) {
		return isset($this->params[$name]) ? ($defaultValue === NULL ? $this->params[$name] : settype($this->params[$name], gettype($defaultValue))) : $defaultValue;
	}



	public function getFiles($size = 0, array $mimeType = NULL, $multiple = 1) {
		$files = [];
		foreach ($this->files as $key => $values) {
			foreach ($values as $value) {
				if ($multiple > 0 || $multiple == -1) {
					if ($multiple != -1) {
						--$multiple;
					}
					$files[$key][] = $this->_getFile($value, $size, $mimeType);
				}
			}
		}
		return $files;
	}


	public function getFile($key, $size = 0, array $mimeType = NULL, $multiple = 1) {
		if (empty($this->files[$key])) {
			return [];
		}
		$files = [];
		foreach ($this->files[$key] as $value) {
			if ($multiple > 0 || $multiple == -1) {
				if ($multiple != -1) {
					--$multiple;
				}
				$files[] = $this->_getFile($value, $size, $mimeType);
			}
		}
		return $files;
	}

	private function _getFile($file, $size, array $mimeType) {
		$file += pathinfo($file['name']) + ['dirname' =>'', 'basename' => '', 'extension' => '', 'filename' => '', 'encoding' => ''];

		if ($file['error'] !== UPLOAD_ERR_OK) {
			// 有错误的
		} elseif (!is_file($file['tmp_name'])) {
			// 文件不存在
			$file['error'] = UPLOAD_ERR_NO_FILE;
		} elseif (!$mime = Storage::mime($file['tmp_name'])) {
			// 后缀
			$file['error'] = UPLOAD_ERR_EXTENSION;
		} elseif ($mimeType && !in_array($mime['type'], $mimeType) && !in_array(strtolower($file['extension']), $mimeType)) {
			// 后缀
			$file['error'] = UPLOAD_ERR_EXTENSION;
		} elseif ($size && $file['size'] > $size) {
			// 文件大小
			$file['error'] = UPLOAD_ERR_FORM_SIZE;
		}
		// 合并
		if (!empty($mime)) {
			$file = $mime + $file;
		}
		return $file;
	}



	public function setFiles(array $files) {
		$this->files = [];
		foreach ($this->files as $key => $value) {
			if (!$value) {
				continue;
			}
			if (empty($value['tmp_name'])) {
				throw new Exception('Set file path can not be empty');
			}
			$this->addFile($key, $value['tmp_name'], empty($value['name']) ? NULL : $value['name'], empty($value['type']) ? NULL : $value['type'], isset($value['error']) ? $value['error'] : UPLOAD_ERR_OK, isset($value['size']) ? $value['size'] : NULL);
		}
	}

	public function setFile($key, $tmp_name, $name, $type = NULL, $error = UPLOAD_ERR_OK, $size = NULL) {
		unset($this->files[$key]);
		return call_user_func_array([$this, 'addFile'], func_get_args());
	}

	public function addFile($key, $tmp_name, $name, $type = NULL, $error = UPLOAD_ERR_OK, $size = NULL) {
		if (is_array($tmp_name)) {
			foreach ($tmp_name as $k => $value) {
				$this->addFile($key, $value, $name ? (is_array($name) ? (isset($name[$k]) ? $name[$k] : NULL) : $name) : NULL, $type ? (is_array($type) ? (isset($type[$k]) ? $type[$k] : NULL) : $type) : NULL, $error ? (is_array($error) ? (isset($error[$k]) ? $error[$k] : UPLOAD_ERR_OK) : $error) : UPLOAD_ERR_OK, $size ? (is_array($size) ? (isset($size[$k]) ? $size[$k] : UPLOAD_ERR_OK) : $size) : UPLOAD_ERR_OK);
			}
		} else {
			$error = abs((int) $error);
			$error = $error ? $error : UPLOAD_ERR_OK;
			if ($error === UPLOAD_ERR_OK && !is_file($tmp_name)) {
				throw new Exception('File does not exist');
			}
			$size = $size === false || $size === NULL && $error === UPLOAD_ERR_OK ? filesize($tmp_name) : abs((int)$size);
			$name = $name ? (string) pathinfo((string) $name, PATHINFO_BASENAME) : 'Unknown';
			$type = $type ? (string) $type : 'application/octet-stream';
			$this->files[$key][] = ['tmp_name' => $tmp_name, 'name' => $name, 'type' => $type, 'error' => $error];
		}
		return $this;
	}





	public function getContent() {
		if (!$this->content) {
			return false;
		}
		if (!is_resource($this->content)) {
			$this->content = fopen($this->content, 'rb');
		} else {
			fseek($this->content, 0);
		}
		return $this->content;
	}


	public function setContent($content) {
		$this->content = $content;
		return $this;
	}












	public function getQueryString() {
		return merge_string($this->querys);
	}


	public function getPath() {
		return explode('?', $this->getURI(), 2)[0];
	}

	public function getURL() {
		return $this->scheme . '://' . $this->getHeader('Host') . $this->URI;
	}





	public function newToken() {
		$token = uniqid();
		$token .= mb_rand(16 - strlen($token), '0123456789qwertyuiopasdfghjklzxcvbnm');
		$token .= Code::key(__CLASS__ . self::TOKEN_HEADER . $token, 16);
		return $token;
	}
	public function hasNewToken() {
		return $this->newToken;
	}



	public function getToken($isKey = false) {
		if ($this->token === NULL) {
			($token = $this->getHeader(self::TOKEN_HEADER)) || ($token = $this->getCookie(self::TOKEN_COOKIE));
			try {
				$this->setToken($this->token);
			} catch (Exception $e) {
				$this->setToken($this->newToken(), true);
			}
		}
		return $isKey ? $this->token : substr($this->token, 0, 16);
	}

	public function setToken($token, $newToken = false) {
		if ($token === NULL || $token === false) {
			$this->token = NULL;
		} else {
			if (!is_string($token) || strlen($token) != 32 || Code::key(__CLASS__ . self::TOKEN_HEADER . substr($token, 0, 16), 16) !== substr($token, 16)) {
				throw new Exception('Access token is invalid');
			}
			$this->token = $token;
		}
		$this->newToken = $newToken;
		return true;
	}






	public function getAjax() {
		if ($this->ajax === NULL) {
			if ($header = $this->getHeader(self::AJAX_HEADER)) {
				$this->ajax = $header;
			} elseif ($param = $this->getParam(self::AJAX_PARAM, '')) {
				$this->ajax = $param;
			} elseif (in_array($extension = strtolower(pathinfo($this->getPath(), PATHINFO_EXTENSION)), ['json', 'xml'])) {
				$this->ajax = $extension;
			} elseif (($accepts = $this->getAccepts()) && in_array($extension = trim(explode('/', reset($accepts), 2)[0]), ['json', 'xml'])) {
				$this->ajax = $extension;
			} elseif (strtolower($this->getHeader('X-Requested-With')) === 'xmlhttprequest') {
				$this->ajax = 'json';
			} else {
				$this->ajax = '';
			}
		}
		return $this->ajax;
	}

	public function setAjax($ajax) {
		$this->ajax = $ajax === NULL ? NULL : (string) $ajax;
		return true;
	}


	public function isPjax() {
		return $this->getHeader(self::PJAX_HEADER);
	}


	public function getRanges() {
		$ranges = [];
		if (($range = $this->getHeader('Range')) && preg_match('/bytes=\s*([0-9-,]+)/i', $range, $matches)) {
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


	public function getAccepts() {
		return $this->parseAccept('Access', '/([a-z]+|*)\/([0-9a-z._+-]+|*)^$/', 'strtolower');
	}


	public function getAcceptLanguages() {
		$this->parseAccept('Access-Language', '/^([a-z]{2})(?:[_-]([a-z]{3-4}))?(?:[_-]([a-z]{2}))?$/', function($arg1, $arg2, $arg3, $arg4) {
			$array[] = strtolower($arg2);
			if ($arg3) {
				$array[] = ucwords($arg3);
			}
			if ($arg4) {
				$array[] = strtoupper($arg4);
			}
			return implode('-', $array);
		});
	}

	protected function parseAccept($name, $pattern, callback $callback = NULL) {
		$arrays = [];
		foreach (explode(',', $this->getHeader($name)) as $value) {
			if (!$value) {
				continue;
			}
			$value = explode(';', $value);
			if (!preg_match($pattern, $value[0], $matches)) {
				continue;
			}
			if (empty($value[1])) {
				$q = 1;
			} else {
				$value[1] = explode('q=', $value[1], 2) + [1 => 1];
				$q = (float) $value[1][1];
			}

			$accept = $callback ? call_user_func_array($callback, $matches) : $matches[0];
			if ($accept) {
				$arrays[$q][] = $accept;
			}
		}
		krsort($arrays);
		$accepts = [];
		foreach ($arrays as $value) {
			$accepts = array_merge($accepts, $value);
		}
		return $accepts;
	}


	public function flush() {
		$this->addr = false;
		$this->port = 0;
		$this->IP = '127.0.0.1';
		$this->scheme = 'http';
		$this->version = 1.1;
		$this->method = 'GET';
		$this->URI = '/';
		$this->querys = [];
		$this->headers = [];
		$this->cookies = [];
		$this->username = false;
		$this->password = false;
		$this->posts = [];
		$this->files = [];
		$this->content = 'php://input';
		$this->newToken = false;
		$this->token = NULL;
		$this->ajax = NULL;
		return $this;
	}
}*/