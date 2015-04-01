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
/*	Updated: UTC 2015-04-01 04:45:36
/*
/* ************************************************************************** */
namespace Loli\HTTP;
use Loli\Code;
class Request{
	const TOKEN_HEADER = 'X-Token';

	const TOKEN_COOKIE = 'token';

	const AJAX_HEADER = 'X-Ajax';

	const AJAX_PARAM = 'ajax';

	const PJAX_HEADER = 'X-Pjax';

	private $_schemes = ['http', 'https'];
	private $_methods = ['OPTIONS', 'HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE'];


	private $_defaultHost = 'localhost';
	private $_postLength = 2097152;
	private $_content = 'php://input';
	private $_newToken = false;
	private $_token = NULL;
	private $_ajax = NULL;
	private $_pjax = NULL;


	public function getScheme() {
		return $this->_scheme;
	}

	public function setScheme($scheme) {
		if (!in_array($scheme, $this->_schemes, true)) {
			throw new Exception('The scheme does not allow');
		}
		$this->_scheme = $scheme;
		return $this;
	}






	public function getVersion() {
		return $this->_version;
	}

	public function setVersion($version) {
		$this->_version = $version == 1.0 ? 1.0 : 1.1;
		return $this;
	}










	public function getMethod() {
		return $this->_method;
	}



	public function setMethod($method) {
		if (!in_array($method = strtoupper($method), $this->_methods)) {
			throw new Exception('The method does not allow');
		}
		$this->_method = $method;
		return $this;
	}













	public function getURI() {
		return $this->_URI;
	}

	public function setURI($URI) {
		$URI = ltrim(trim($URI), '/');
		list($path, $queryString) = explode('?', $URI, 2) + [1 => ''];
		$this->_URI = '/'. $URI;
		$this->_querys = parse_string($queryString);
		return $this;
	}









	public function getQuerys() {
		return $this->_querys;
	}

	public function getQuery($name, $defaultValue = NULL) {
		return isset($this->_querys[$name]) ? ($defaultValue === NULL ? $this->_querys[$name] : settype($this->_querys[$name], gettype($defaultValue))) : $defaultValue;
	}

	public function setQuerys(array $querys) {
		$this->_querys = parse_string($queryString = merge_string($querys));
		implode('?', array_filter([1=> $queryString] + explode('?', $this->_URI, 2)));
		return $this;
	}

	public function setQuery($name, $value) {
		if ($value === NULL || $value === false) {
			isset($this->_querys[$name]) && $this->setQuerys([$name=>NULL] + $this->_querys);
		} else {
			$this->_querys[$name] = is_array($value) || is_object($value) ? parse_string(merge_string($value)) : (string) $value;
			$this->_URI .= (strpos($this->_URI, '?') === false ? '?' : '&') . merge_string([$name=>$value]);
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
		$this->_cookies = [];
		$this->_username = $this->_password = false;
		$this->setHeader('Host', $this->_defaultHost);
		foreach($headers as $name => $value) {
			$this->setHeader($name, $value);
		}
		return $this;
	}


	public function setHeader($name, $value) {
		$name = strtoupper(strtr($name, '-', '_'));
		if ($value === NULL || $value === false) {
			switch ($name) {
				case 'Host':
					throw new Exception('You can not remove host');
					break;
				case 'Cookie':
					$this->_cookies = [];
					break;
				case 'Authorization':
					$this->_username = $this->_password = false;
					break;
			}
			unset($this->_headers[$name]);
		} else {
			$value = rtrim(trim((string)$value), ';');
			switch ($name) {
				case 'Host':
					$value = $value ? strtolower($value) : $this->_defaultHost;
					break;
				case 'Content-Length':
					$value = (string) abs((int) $value);
					break;
				case 'Cookie':
					$this->_cookies = parse_string(preg_replace('/;\s*/', '&', $value));
					$value = http_build_query($this->_cookies, NULL, '; ');
					break;
				case 'Authorization':
					$this->_username = $this->_password = false;
					if (count($auth = explode(' ', $value, 2)) === 2 && $auth[1] && ($auth = base64_decode(trim($auth[1])))) {
						$auth = explode(':', $auth, 2);
						$this->_username = $auth[0];
						$this->_password = isset($auth[1]) ? $auth[1] : '';
					}
					break;
			}
			$this->_headers[$name] = $value;
		}
		return $this;
	}











	public function getCookies() {
		return $this->_cookies;
	}

	public function getCookie($name, $defaultValue = NULL) {
		return isset($this->_cookies[$name]) ? ($defaultValue === NULL ? $this->_cookies[$name] : settype($this->_cookies[$name], gettype($defaultValue))) : $defaultValue;
	}


	public function setCookies(array $cookies) {
		//=,; \t\r\n\013\014
		$this->_cookies = parse_string(merge_string($cookies));
		$this->_headers['Cookie'] = http_build_query($this->_cookies, NULL, '; ');
		return $this;
	}

	public function setCookie($name, $value) {
		if ($value === NULL || $value === false) {
			isset($this->_cookies[$name]) && $this->setCookies([$name=>NULL] + $this->_cookies);
		} else {
			$this->_cookies[$name] = is_array($value) || is_object($value) ? parse_string(merge_string($value)) : (string) $value;
			$this->_headers['Cookie'] = $this->_headers['Cookie'] . (empty($this->_headers['Cookie']) ? '; ' : '') . merge_string([$name=>$value]);
		}
		return $this;
	}








	public function getPosts() {
		return $this->_posts;
	}

	public function getPost($name, $defaultValue = NULL) {
		return isset($this->_posts[$name]) ? ($defaultValue === NULL ? $this->_posts[$name] : settype($this->_posts[$name], gettype($defaultValue))) : $defaultValue;
	}

	public function setPosts(array $requests) {
		$this->_posts = parse_string(merge_string($requests));
		return true;
	}

	public function setPost($name, $value) {
		if ($value === NULL || $value === false) {
			unset($this->_posts[$name]);
		} else {
			$this->_posts[$name] = is_array($value) || is_object($value) ? parse_string(merge_string($value)) : (string) $value;
		}
		return true;
	}








	public function setParams(array $params) {
		$this->_params = parse_string(merge_string($params));
		return $this;
	}

	public function setParam($name, $defaultValue = NULL) {
		if ($value === NULL || $value === false) {
			unset($this->_params[$name]);
		} else {
			$this->_params[$name] = is_array($value) || is_object($value) ? parse_string(merge_string($value)) : (string) $value;
		}
		return true;
	}


	public function getParams() {
		return $this->_params;
	}


	public function getParam($name, $defaultValue = NULL) {
		return isset($this->_params[$name]) ? ($defaultValue === NULL ? $this->_params[$name] : settype($this->_params[$name], gettype($defaultValue))) : $defaultValue;
	}








	public function getContent() {
		if (!$this->_content) {
			return false;
		}
		if (!is_resource($this->_content)) {
			$this->_content = fopen($this->_content, 'rb');
		}
		return $this->_content;
	}


	public function setContent($content) {
		$this->_content = $content;
		return $this;
	}














	public function getUsername() {
		return $this->_username;
	}


	public function setUsername($username) {
		return $this->setHeader('Authorization', 'Basic ' . base64_encode($username .':' . $this->getPassword()));
	}

	public function getPassword() {
		return $this->_password;
	}

	public function setPassword($password) {
		return $this->setHeader('Authorization', 'Basic ' . base64_encode($this->getUsername() .':' . $password));
	}





	public function getPath() {
		return explode('?', $this->getURI(), 2)[0];
	}

	public function newToken() {
		$token = uniqid();
		$token .= mb_rand(16 - strlen($token), '0123456789qwertyuiopasdfghjklzxcvbnm');
		$token .= Code::key(__CLASS__ . self::TOKEN_HEADER . $token, 16);
		return $token;
	}
	public function hasNewToken() {
		return $this->_newToken;
	}

	public function getToken($isKey = false) {
		if ($this->_token === NULL) {
			($token = $this->getHeader(self::TOKEN_HEADER)) || ($token = $this->getCookie(self::TOKEN_COOKIE));
			try {
				$this->setToken($this->_token);
			} catch (Exception $e) {
				$this->setToken($this->newToken(), true);
			}
		}
		return $isKey ? $this->_token : substr($this->_token, 0, 16);
	}

	public function setToken($token, $newToken = false) {
		if ($token === NULL || $token === false) {
			$this->_token = NULL;
		} else {
			if (!is_string($token) || strlen($token) != 32 || Code::key(__CLASS__ . self::TOKEN_HEADER . substr($token, 0, 16), 16) !== substr($token, 16)) {
				throw new Exception('Access token is invalid');
			}
			$this->_token = $token;
		}
		$this->_newToken = $newToken;
		return true;
	}



	public static function getAjax() {
		if ($this->_ajax === NULL) {
			if ($header = self::getHeader(self::AJAX_HEADER)) {
				$this->_ajax = $header;
			} elseif ($param = self::getParam(self::AJAX_PARAM, '')) {
				$this->_ajax = $param;
			} elseif (in_array($extension = strtolower(pathinfo(self::getPath(), PATHINFO_EXTENSION)), ['json', 'xml'])) {
				$this->_ajax = $extension;
			} elseif (($accept = self::getHeader('ACCEPT')) && ($mimeType = explode(',', $accept, 2)[0]) && in_array($extension = strtolower(trim(explode('/', $mimeType, 2)[0])), ['json', 'xml'])) {
				$this->_ajax = $extension;
			} elseif (strtolower(self::getHeader('X_REQUESTED_WITH')) === 'xmlhttprequest') {
				$this->_ajax = 'json';
			} else {
				$this->_ajax = '';
			}
		}
		return $this->_ajax;
	}

	public static function setAjax($ajax) {
		$this->_ajax = $ajax === NULL ? NULL : (string) $ajax;
		return true;
	}


	public static function isPjax() {
		return self::getHeader(PJAX_HEADER);
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



	/*public function flush() {
		$this->setScheme('http');
		$this->setVersion(1.1);
		$this->setMethod('GET');
		$this->setURI('/');
		$this->setHeaders(['Host' => $this->_defaultHost]);
		$this->setPosts([]);
		$this->setParams([]);
		$this->setFiles([]);
		$this->setContent('php://input');
		$this->setIP('127.0.0.1');
		$this->setToken(NULL);
		$this->setAjax(NULL);
	}


	//public function getPath() {
		//return explode('?', self::getURI(), 2)[0];
	//}
}




