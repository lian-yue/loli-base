<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-07 05:34:04
/*	Updated: UTC 2015-04-03 06:35:38
/*
/* ************************************************************************** */
namespace Loli\HTML;
class Response{

	protected $status = 200;

	protected $headers = [], $caches = [], $cookies = [];

	protected $content;

	protected $request;


	public $ajaxJS = false;


	public $cookiePath = '/';

	public $cookieDomain = false;

	public $cookieSecure = false;

	public $cookieHttponly = false;

	public function __construct(Request &$request) {
		$this->request = &$request;
		$this->ajaxJS = $request->getToken() === $request->getParam('_token');
	}

	public function getStatus() {
		return $this->status;
	}

	public function setStatus($status) {
		$this->status = $status ? (int) $status : 200;
		return $this;
	}

    public function getCookies() {
    	return $this->cookies;
	}

	public function getCookie($name) {
		return isset($this->cookies[$name]) ? $this->cookies[$name] : false;
	}

	public function setCookies(array $cookies) {
		$this->cookies = [];
		foreach ($cookies as $name => $cookie) {
			$cookie += ['value' => NULL, 'ttl' => 0, 'httponly' => NULL, $secure => NULL, 'path' => NULL, 'domain' => NULL];
			$this->setCookie($name, $cookie['value'], $cookie['ttl'], $cookie['httponly'], $cookie['secure'], $cookie['path'], $cookie['domain']);
		}
		return $this;
	}

	public function setCookie($name, $value, $ttl = 0,  $httponly = NULL, $secure = NULL, $path = NULL, $domain = NULL) {
		$this->cookies[$name] = ['value' => is_array($value) || is_object($value) ? parse_string($value) : $value, 'ttl' => $ttl, 'httponly' => $httponly, 'secure' => $secure, 'path' => $path, 'domain' => $domain];
		return $this;
	}

	public function addCookie($name, $value, $ttl = 0,  $httponly = NULL, $secure = NULL, $path = NULL, $domain = NULL) {
		if (empty($this->cookies[$name])) {
			return call_user_func_array([$this, 'setCookie'], func_get_args());
		}
		return $this;
	}


	protected function sendCookies() {
		foreach ($this->cookies as $name => $cookie) {
			$this->_sendCookie($name, $cookie['value'], $cookie['ttl'], $cookie['httponly'], $cookie['secure'], $cookie['path'], $cookie['domain']);
		}
		return $this;
	}

	private function _sendCookie($name, $value, $ttl = 0,  $httponly = NULL, $secure = NULL, $path = NULL, $domain = NULL) {
		$httponly = $httponly === NULL ? $this->cookieHttponly : $httponly;
		$secure = $secure === NULL ? $this->cookieSecure : $secure;
		$path = $path === NULL ? $this->cookiePath : $path;
		$domain = $domain === NULL ? $this->cookieDomain : $domain;
		if (is_array($value) || is_object($value)) {
			foreach ($value as $key => $_value) {
				$this->_sendCookie($name . '['. rawurlencode($key) .']', $_value, $ttl,  $httponly, $secure, $path, $domain);
			}
		} else {
			if ($value === NULL || $ttl < -1) {
				$value = 'deleted';
				$ttl = 1;
			} else {
				$ttl = $ttl ? time() + ($ttl == -1 ? 86400 * 365 * 20 : $ttl) : $ttl;
			}
			setcookie($name, $value, $ttl, $path, $domain, $secure, $httponly);
		}
	}



	public function getCaches() {
		return $this->caches;
	}

	public function getCache($name) {
		return isset($this->caches[$name]) ? $this->caches[$name] : false;
	}

	public function setCaches(array $caches) {
		$this->caches = [];
		foreach ($caches as $name => $value) {
			if ($value === NULL) {
				continue;
			}
			$this->caches[$name] = (string) $value;
		}
		return $this;
	}

	public function addCache($name, $value) {
		if ($value === NULL || isset($this->caches[$name])) {
			return $this;
		}
		$this->caches[$name] = (string) $value;
		return $this;
	}


	public function setCache($name, $value) {
		if ($value === NULL) {
			unset($this->caches[$name]);
		} else {
			$this->caches[$name] = (string) $value;
		}
		return $this;
	}

	protected function sendCaches() {
		$values = [];
		foreach ($this->caches as $name => $value) {
			if (substr($name, 0, 3) == 'no-') {
				// no-cache no-store
				$values[] = $value ? $name . '=' . $value : $name;
				$name == 'no-cache' && !$value && $this->setHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', 0));
			} elseif ($name == 'max-age') {
				// max-age
				$values[] = $name . '=' . $value;
				$name == 'max-age' && $this->setHeader('Expires', $value ? gmdate('D, d M Y H:i:s \G\M\T', time() + $value) : NULL);
			} elseif (in_array($name, ['public', 'private']) && in_array($values, ['public', 'private'])) {
				// public  和 private 只能选一个
			} elseif ($value) {
				$values[] = $name;
			}
		}
		$this->setHeader('Cache-Control', $values ? implode(', ', $values) : NULL);
		return $this;
	}


	protected function sendToken() {
		$request = $this->request;
		if ($request->isNewToken()) {
			$this->setCookie($request::TOKEN_COOKIE, $request->getToken(true), -1);
			$this->setHeader($request::TOKEN_HEADER, $request->getToken(true));
		}
		return $this;
	}


	public function getHeaders() {
		return $this->headers;
	}

	public function setHeaders(array $headers) {
		$this->headers = [];
		foreach ($headers as $name => $values) {
			if ($values === NULL) {
				continue;
			}
			foreach ((array)$values as $value) {
				$this->headers[$name][] = (string) $value;
			}
		}
		return $this;
	}

	public function getHeader($name) {
		return isset($this->headers[$name]) ? $this->headers[$name] : [];
	}

	public function addHeader($name, $values, $exists = true) {
		if ($exists || empty($this->headers[$name])) {
			foreach ((array)$values as $value) {
				$this->headers[$name][] = (string) $value;
			}
		}
		return $this;
	}

	public function setHeader($name, $values) {
		unset($this->headers[$name]);
		if ($values !== NULL) {
			foreach ((array)$values as $value) {
				$this->headers[$name][] = (string) $value;
			}
		}
		return $this;
	}


	protected function sendHeaders() {
		if (headers_sent()) {
			return $this;
		}
		$this->addHeader('Content-Type', 'text/html', false);


		http_response_code($this->status);

		$this->sendToken();
		$this->sendCaches();
		foreach ($this->getHeaders() as $name => $values) {
			$replace = true;
			foreach ($values as $value) {
				$value = trim($value,  " \t\n\r\0\x0B;");
				if ($name == 'Content-Type') {
					if (strpos($value, ';') === false && ($arrays = explode('/', strtolower($value))) && (in_array($arrays[0], ['text']) || (isset($arrays[1]) && in_array($arrays[1], ['javascript', 'x-javascript', 'js', 'plain', 'html', 'xml', 'css'])))) {
						$value = $value . '; charset=UTF-8';
					}
				} elseif ($name == 'Content-Disposition') {
					if (preg_match('/\s*(?:([0-9a-z_-]+)\s*;)?\s*filename\s*=\s*("[^"]+"|[^;]+)/i', $value, $matches)) {
						$type = trim($matches[1]);
						$filename = trim($matches[2], " \t\n\r\0\x0B\"");
						if (!($userAgent = $this->request->getHeader('User-Agent')) || strpos($userAgent, 'MSIE ') !== false || (strpos($userAgent, 'Trident/') !== false && strpos($userAgent, 'rv:') !== false && strpos($userAgent, 'opera') === false)) {
							$filename = strtr(urlencode($filename), ['+'=>'%20', '"' => '']);
						}

						$value = [$type];
						if ($filename) {
							$value[] = 'filename="' . $filename . '"';
							$value[] = 'filename*=UTF-8 \'\'"'.$filename.'"';
						}
						$value = implode('; ', array_filter($value));
					}
				}
				header($name . ': '. $value, $replace);
				$replace = false;
			}
		}
		$this->sendCookies();
		return $this;
	}






	public function getContent() {
		return $this->content;
	}

	public function setContent(callback $content = NULL) {
		$this->content = $content;
		return $this;
	}

	public function addContent(callback $content) {
		if ($this->content === NULL) {
			$this->content = $content;
		}
		return $this;
	}

	protected function sendContent() {
		// < 200 204 205 304
		if ($this->status < 200 || in_array($this->status, [204, 205, 304])) {
			return $this;
		}
		// OPTIONS 方法
		if ($this->request->getMethod() == 'OPTIONS') {
			return $this;
		}

		// HEAD 方法
		if ($this->request->getMethod() == 'HEAD' && !$this->getHeader('Content-Length')) {
			return $this;
		}

		echo call_user_func($this->content);

		return $this;
	}






	// 缓存状态码  304  206 200 412
	public function getCacheStatus() {
		if ($this->status < 200 || $this->status >= 300) {
			return $this->status;
		}

		$etag = $this->getHeader('Etag', '');
		$modified = $this->getHeader('Last-Modified', '');


		// 没匹配到 412 文件已被改变
		if (($ifMatch = $this->request->getHeader('If-Match')) && $etag != $ifMatch) {
			return 412;
		}

		// 没匹配到 412 文件已被改变
		if (($ifUnmodifiedSince = $this->request->getHeader('If-Unmodified-Since')) && $ifUnmodifiedSince != $modified) {
			return 412;
		}

		// 没匹配到 200 文件已被改变
		if (($IfNoneMatch = $this->request->getHeader('If-None-Match', '')) && $IfNoneMatch !=  $etag) {
			return 200;
		}

		// 没匹配到 200 文件已被改变
		if (($ifModifiedSince = $this->request->getHeader('If-Modified-Since', '')) && $ifModifiedSince != $modified) {
			return 200;
		}

		// ifRange 已被修改
		if (($ifRange = $this->request->getHeader('If-Range')) && $ifRange != $etag && $ifRange != $modified) {
			return 200;
		}

		// 没有范围
		if (!$this->request->getRanges()) {
			return $IfNoneMatch || $ifModifiedSince ? 304 : 200;
		}

		// 不支持分段
		if ($this->getHeader('Accept-Ranges') != 'bytes') {
			return 200;
		}

		return 206;
	}


	public function send() {
		$this->sendHeaders();
		$this->sendContent();
		return $this;
	}

	public function flush() {
		$this->headers = $this->caches = $this->cookies = [];
		$this->status = 200;
		$this->content = NULL;
		return $this;
	}
}