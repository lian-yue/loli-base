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
/*	Updated: UTC 2015-03-25 01:52:53
/*
/* ************************************************************************** */
namespace Loli;
class Request{


	const TOKEN_HEADER = 'X-Token';

	const TOKEN_COOKIE = 'token';

	const AJAX_HEADER = 'X-Ajax';

	const AJAX_PARAM = 'ajax';

	private $_methods = ['OPTIONS', 'HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE']

	public static function getScheme() {
		if (isset($_SERVER['REQUEST_SCHEME'])) {
			return $_SERVER['REQUEST_SCHEME'];
		}

		if (isset($_SERVER['HTTPS']) && ('on' == strtolower($_SERVER['HTTPS']) || '1' == $_SERVER['HTTPS'])) {
			$scheme = 'https';
		} elseif (isset($_SERVER['SERVER_PORT']) && '443' == $_SERVER['SERVER_PORT']) {
			$scheme = 'https';
		} elseif (isset($_SERVER['SERVER_PORT_SECURE']) && '1' == $_SERVER['SERVER_PORT_SECURE']) {
			$scheme = 'https';
		} else {
			$scheme = 'http';
		}
		$_SERVER['HTTPS'] = substr($scheme, -1) == 's' ? 'on' : 'off';
		return $_SERVER['REQUEST_SCHEME'] = $scheme;
	}


	public static function setScheme($scheme) {
		if (!in_array($scheme = strtolower($scheme), ['http', 'https'])) {
			throw new Exception('Unknown scheme link request');
		}
		$_SERVER['REQUEST_SCHEME'] = $scheme;
		$_SERVER['HTTPS'] = substr($scheme, -1) == 's' ? 'on' : 'off';
		return true;
	}


	public static function getVersion() {
		if (empty($_SERVER['SERVER_PROTOCOL'])) {
			$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
		}
		return (float) substr($_SERVER['SERVER_PROTOCOL'], 5);
	}

	public static function setVersion($version) {
		$_SERVER['SERVER_PROTOCOL'] = 'HTTP/' . ($version == 1.0 ? '1.0' : '1.1');
		return true;
	}

	public static function getMethod() {
		if (empty($_SERVER['REQUEST_METHOD'])) {
			$_SERVER['REQUEST_METHOD'] = 'GET';
		}
		return $_SERVER['REQUEST_METHOD'];
	}


	public static function setMethod($method) {
		if (!in_array($method = strtoupper($method), self::$_methods)) {
			throw new Exception('Set Method:' . $method);
		}
		$_SERVER['REQUEST_METHOD'] = $method;
		return true;
	}


	public static function getHost() {
		return $this->getHeader('Host');
	}

	public static function setHost($host) {
		return $this->setHeader('Host', $host);
	}

	public static function getPath() {
		return $this->_path;
	}

	public function getQuerys() {
		return $_GET;
	}

	public function getQuery($name, $defaultValue = NULL) {
		return isset($_GET[$name]) ? $_GET[$name] : $defaultValue;
	}

	public function getURI() {
		if (!empty($_SERVER['REQUEST_URI'])) {

		} elseif (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
			$parse = parse_url($_SERVER['HTTP_X_ORIGINAL_URL']);
			$_SERVER['REQUEST_URI'] = empty($parse['path']) ? '/' : urlencode($parse['path']) . (empty($parse['query']) ? '' : '?' . merge_string(parse_string($parse['query'])));
		} elseif (isset($_SERVER['PATH_INFO']) && isset($_SERVER['SCRIPT_NAME'])) {
			if ($_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME']) {
				$_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
			} else {
				$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
			}
		} else {
			$_SERVER['REQUEST_URI'] = '/';
		}
		return $_SERVER['REQUEST_URI'];
	}


	public static function getHeaders() {
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
			if (!empty($_SERVER['CONTENT_TYPE'])) {
				$headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
			}
			if (!empty($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
				$headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
			}
		}
		if (!empty($headers['Content-Type'])) {
			$headers['Content-Type'] = strtolower(trim(explode(';', $headers['Content-Type'], 2)[0]));
		}

		if (empty($headers['Host'])) {
			if (isset($_SERVER['HTTP_HOST'])) {
				$headers['Host'] = $_SERVER['HTTP_HOST'];
			}  elseif (isset($_SERVER['SERVER_NAME'])) {
				$headers['Host'] = $_SERVER['SERVER_NAME'];
				if (isset($_SERVER['SERVER_PORT']) && !in_array($_SERVER['SERVER_PORT'], ['80', '443'])) {
					$headers['Host'] = ':' . $_SERVER['SERVER_PORT'];
				}
			} else {
				$headers['Host'] = self::$_defaultHost;
			}
		}
		unset($headers['X-Original-Url'], $headers['Cookie']);
		return $headers;
	}

	public function getHeader($name, $defaultValue = NULL) {
		return isset($this->_headers[$name]) ? $this->_headers[$name] : $defaultValue;
	}
}