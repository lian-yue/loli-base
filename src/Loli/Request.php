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
/*	Updated: UTC 2015-02-09 15:13:21
/*
/* ************************************************************************** */
namespace Loli;
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
				$this->_token = false;
			}
		}
		if (!$this->_token) {
			return false;
		}
		return $key ? $this->_token : substr($this->_token, 0, 16);
	}

	public function addToken() {
		$token = uniqid();
		$token .= mb_rand(16 - strlen($token), '0123456789qwertyuiopasdfghjklzxcvbnm');
		$token .= Code::key(__CLASS__ . self::TOKEN_HEADER . $token, 16);
		return $this->setToken($token);
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