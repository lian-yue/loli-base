<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-03-26 12:39:04
/*	Updated: UTC 2015-03-27 05:52:10
/*
/* ************************************************************************** */
namespace Loli;
class Response{
	private static $_status = 200;

	private static $_headers = [];

	private static $_cookies = [];

	public static $cookiePath = '/';

	public static $cookieDomain = false;

	public static $cookieSecure = false;

	public static $cookieHttponly = false;

	public static function getStatus() {
		return self::$_status;
	}

	public static function setStatus($status) {
		self::$_status = $status ? (int) $status : 200;
		return true;
	}


	public static function getHeaders() {
		return self::$_headers;
	}

	public static function getHeader($name, $defaultValue = NULL) {
		return $defaultValue === NULL ? isset(self::$_headers[$name]) ? end(self::$_headers[$name]) : $defaultValue : (isset(self::$_headers[$name]) ? self::$_headers[$name] : []);
	}


	public static function setHeaders(array $headers) {
		self::$_headers = [];
		foreach ($headers as $name => $values) {
			if ($values === NULL || $values === false) {
				continue;
			}
			foreach ((array)$values as $value) {
				self::$_headers[$name][] = (string) $value;
			}
		}
		return true;
	}

	public static function setHeader($name, $values) {
		unset(self::$_headers[$name]);
		if ($values !== NULL && $values !== false) {
			foreach ((array)$values as $value) {
				self::$_headers[$name][] = (string) $value;
			}
		}
		return true;
	}

	public static function addHeader($name, $values, $exists = true) {
		if ($exists || empty(self::$_headers[$name])) {
			foreach ((array)$values as $value) {
				self::$_headers[$name][] = (string) $value;
			}
		}
		return true;
	}




	protected static function sendHeaders() {
		if (headers_sent()) {
			return false;
		}
		self::addHeader('Content-Type', 'text/html', false);
		http_response_code(self::$_status);
		self::sendToken();
		self::sendCaches();
		foreach (self::getHeaders() as $name => $values) {
			$replace = true;
			foreach ($values as $value) {
				switch ($values) {
					case 'Content-Type':
						// 内容类型自动添加编码
						if (strpos($value, ';') === false && ($arrays = explode('/', strtolower($value))) && (in_array($arrays[0], ['text']) || (isset($arrays[1]) && in_array($arrays[1], ['javascript', 'x-javascript', 'js', 'plain', 'html', 'xml', 'css'])))) {
							$value = $value . '; charset=UTF-8';
						}
						break;
					case 'Content-Disposition':
						// 描述自动兼容ie 等
						if (preg_match('/\s*(?:([0-9a-z_-]+)\s*;)?\s*filename\s*=\s*("[^"]+"|[^;]+)/i', $value, $matches)) {
							$type = trim($matches[1]);
							$filename = trim($matches[2], " \t\n\r\0\x0B\"");
							if (!($userAgent = Request::getHeader('User-Agent')) || strpos($userAgent, 'MSIE ') !== false || (strpos($userAgent, 'Trident/') !== false && strpos($userAgent, 'rv:') !== false && strpos($userAgent, 'opera') === false)) {
								$filename = strtr(urlencode($filename), ['+'=>'%20', '"' => '']);
							}
							$value = [$type];
							if ($filename) {
								$value[] = 'filename="' . $filename . '"';
								$value[] = 'filename*=UTF-8 \'\'"'.$filename.'"';
							}
							$value = implode('; ', array_filter($value));
						}
						break;
				}
				header($name . ': '. $value, $replace);
				$replace = false;
			}
		}
		self::sendCookies();
		return true;
	}




	public static function getCookies() {
		return self::$_cookies;
	}

	public static function getCookie($name) {
		return isset(self::_$cookies[$name]) ? self::_$cookies[$name] : false;
	}

	public static function addCookies(array $cookies) {
		foreach ($cookies as $name => $cookie) {
			$cookie += ['value' => NULL, 'ttl' => 0, 'httponly' => NULL, $secure => NULL, 'path' => NULL, 'domain' => NULL];
			self::addCookie($name, $cookie['value'], $cookie['ttl'], $cookie['httponly'], $cookie['secure'], $cookie['path'], $cookie['domain']);
		}
		return true;
	}
	public static function addCookie($name, $value, $ttl = 0,  $httponly = NULL, $secure = NULL, $path = NULL, $domain = NULL) {
		if (empty(self::$_cookies[$name])) {
			return call_user_func_array([__CLASS__, 'setCookie'], func_get_args());
		}
		return true;
	}

	public static function setCookies(array $cookies) {
		self::$_cookies = [];
		return self::addCookies($cookies);
	}

	public static function setCookie($name, $value, $ttl = 0,  $httponly = NULL, $secure = NULL, $path = NULL, $domain = NULL) {
		self::$_cookies[$name] = ['value' => is_array($value) || is_object($value) ? parse_string(merge_string($value)) : $value, 'ttl' => $ttl, 'httponly' => $httponly, 'secure' => $secure, 'path' => $path, 'domain' => $domain];
		return true;
	}


	protected static function sendCookies() {
		foreach (self::$_cookies as $name => $cookie) {
			self::_sendCookie($name, $cookie['value'], $cookie['ttl'], $cookie['httponly'], $cookie['secure'], $cookie['path'], $cookie['domain']);
		}
		return true;
	}

	private static function _sendCookie($name, $value, $ttl = 0,  $httponly = NULL, $secure = NULL, $path = NULL, $domain = NULL) {
		if (headers_sent()) {
			return false;
		}
		$httponly = $httponly === NULL ? self::$cookieHttponly : $httponly;
		$secure = $secure === NULL ? self::$cookieSecure : $secure;
		$path = $path === NULL ? self::$cookiePath : $path;
		$domain = $domain === NULL ? self::$cookieDomain : $domain;
		if (is_array($value)) {
			foreach ($value as $key => $_value) {
				self::_sendCookie($name . '['. rawurlencode($key) .']', $_value, $ttl,  $httponly, $secure, $path, $domain);
			}
		} else {
			if ($value === NULL || $value === false) {
				$value = 'deleted';
				$ttl = 1;
			} else {
				$ttl = $ttl ? time() + $ttl : $ttl;
			}
			setcookie($name, $value, $ttl, $path, $domain, $secure, $httponly);
		}
	}



	public static function getContent($content) {
		return self::$_content;
	}


	public static function addContent($content) {
		if (self::$_content === NULL) {
			self::$_content = $content;
		}
		return true;
	}

	public static function setContent($content) {
		self::$_content = $content;
		return true;
	}


	public static function setCache($name, $value) {
		if ($value === NULL) {
			unset(self::$_caches[$name]);
		} else {
			self::$_caches[$name] = (string) $value;
		}
		return true;
	}

	protected static function sendContent() {
		// 204 205 304 和 HEAD 不发送内容
		if (in_array(self::$_status, [204, 205, 304]) || Request::getMethod() === 'OPTIONS' ||  (Request::getMethod() == 'HEAD' && self::getHeader('Content-Length'))) {
			return true;
		}
		// 小于200 的发送 空行
		if (self::$_status < 200) {
			return true;
		}
		if (is_array(self::$_content) || (is_object(self::$_content) && !method_exists(self::$_content, '__toString'))) {
			echo call_user_func(self::$_content);
		} else {
			echo self::$_content;
		}
		return true;
	}



	protected static function sendCaches() {
		$values = [];
		foreach (self::$_caches as $name => $value) {
			if (substr($name, 0, 3) == 'no-') {
				// no-cache no-store
				$values[] = $value ? $name . '=' . $value : $name;
				$name == 'no-cache' && !$value && self::setHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', 0));
			} elseif ($name == 'max-age') {
				// max-age
				$values[] = $name . '=' . $value;
				$name == 'max-age' && self::setHeader('Expires', $value ? gmdate('D, d M Y H:i:s \G\M\T', time() + $value) : NULL);
			} elseif (in_array($name, ['public', 'private']) && in_array($values, ['public', 'private'])) {
				// public  和 private 只能选一个
			} elseif ($value) {
				$values[] = $name;
			}
		}
		self::setHeader('Cache-Control', $values ? implode(', ', $values) : NULL);
		return true;
	}


	// 缓存状态码  304  206 200 412
	protected static function getCacheStatus() {
		if (self::$_status < 200 || self::$_status >= 300) {
			return self::$_status;
		}

		$etag = self::getHeader('Etag', '');
		$modified = self::getHeader('Last-Modified', '');


		// 没匹配到 412 文件已被改变
		if (($ifMatch = Request::getHeader('If-Match')) && (!$etag || !in_array($etag, array_map('trim', explode(',', $ifMatch))))) {
			return 412;
		}

		// 没匹配到 412 文件已被改变
		if (($ifUnmodifiedSince = Request::getHeader('If-Unmodified-Since')) && $ifUnmodifiedSince !== $modified) {
			return 412;
		}

		// 没匹配到 200 文件已被改变
		if (($IfNoneMatch = Request::getHeader('If-None-Match', '')) && (!$etag || !in_array($etag, array_map('trim', explode(',', $IfNoneMatch))))) {
			return 200;
		}

		// 没匹配到 200 文件已被改变
		if (($ifModifiedSince = Request::getHeader('If-Modified-Since', '')) && $ifModifiedSince !== $modified) {
			return 200;
		}

		// ifRange 已被修改
		if (($ifRange = Request::getHeader('If-Range')) && $ifRange !== $etag && $ifRange !== $modified) {
			return 200;
		}

		// 没有范围
		if (!Request::getRanges()) {
			return $IfNoneMatch || $ifModifiedSince ? 304 : 200;
		}

		// 不支持分段
		if (self::getHeader('Accept-Ranges', '') !== 'bytes') {
			return 200;
		}

		return 206;
	}

	public static function send() {
		self::sendHeaders();
		self::sendContent();
		return true;
	}

	public static function flush() {
		self::$_headers = self::$_caches = self::$_cookies = [];
		self::$_status = 200;
		self::$_content = NULL;
		return true;
	}
}