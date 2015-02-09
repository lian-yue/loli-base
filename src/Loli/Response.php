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
/*	Updated: UTC 2015-02-09 14:18:29
/*
/* ************************************************************************** */
namespace Loli;
class Response{

	private $_status = 200;

	private $_headers = [], $_caches = [], $_cookies = [], $_messages = [];

	private $_content;

	private $_request;

	private $_token = '';


	public $ajaxJS = false;


	public $cookiePath = '/';

	public $cookieDomain = false;

	public $cookieSecure = false;

	public $cookieHttponly = false;

	public function __construct(Request &$request) {
		$this->_request = &$request;
	}

	public function getStatus() {
		return $this->_status;
	}

	public function setStatus($status) {
		$this->_status = $status ? (int) $status : 200;
		return $this;
	}

    public function getCookies() {
    	return $this->_cookies;
	}

	public function setCookies(array $cookies) {
		$this->_cookies = [];
		foreach ($cookies as $name => $cookie) {
			$cookie += ['value' => null, 'ttl' => 0, 'httponly' => null, $secure => null, 'path' => null, 'domain' => null];
			$this->setCookie($name, $cookie['value'], $cookie['ttl'], $cookie['httponly'], $cookie['secure'], $cookie['path'], $cookie['domain']);
		}
		return $this;
	}

	public function getCookie($name, $defaultValue = null) {
		return isset($this->_cookies[$name]) ? $this->_cookies[$name] : $defaultValue;
	}

	public function addCookie($name, $value, $ttl = 0,  $httponly = null, $secure = null, $path = null, $domain = null) {
		if (empty($this->_cookies[$name])) {
			return call_user_func_array([$this, 'setCookie'], func_get_args());
		}
		return $this;
	}

	public function setCookie($name, $value, $ttl = 0,  $httponly = null, $secure = null, $path = null, $domain = null) {
		$this->_cookies[$name] = ['value' => is_array($value) || is_object($value) ? to_array($value) : $value, 'ttl' => $ttl, 'httponly' => $httponly, 'secure' => $secure, 'path' => $path, 'domain' => $domain];
		return $this;
	}


	protected function sendCookies() {
		foreach ($this->_cookies as $name => $cookie) {
			$this->_sendCookie($name, $cookie['value'], $cookie['ttl'], $cookie['httponly'], $cookie['secure'], $cookie['path'], $cookie['domain']);
		}
		return $this;
	}

	private function _sendCookie($name, $value, $ttl = 0,  $httponly = null, $secure = null, $path = null, $domain = null) {
		$httponly = $httponly === null ? $this->cookieHttponly : $httponly;
		$secure = $secure === null ? $this->cookieSecure : $secure;
		$path = $path === null ? $this->cookiePath : $path;
		$domain = $domain === null ? $this->cookieDomain : $domain;
		if (is_array($value)) {
			foreach ($value as $key => $_value) {
				$this->_sendCookie($name . '['. $key .']', $_value, $ttl,  $httponly, $secure, $path, $domain);
			}
		} else {
			if ($value === null) {
				$value = 'deleted';
				$ttl = 1;
			} else {
				$ttl = $ttl ? time() + $ttl : $ttl;
			}
			setcookie($this->request->cookiePrefix . $name, $value, $ttl, $path, $domain, $secure, $httponly);
		}
	}



	public function getCaches() {
		return $this->_caches;
	}
	public function setCaches(array $caches) {
		$this->_caches = [];
		foreach ($caches as $name => $value) {
			if ($value === null) {
				continue;
			}
			$this->_caches[$name] = (string) $value;
		}
		return $this;
	}

	public function getCache($name, $defaultValue = null) {
		return isset($this->_caches[$name]) ? $this->_caches[$name] : null;
	}

	public function addCache($name, $value) {
		if ($value === null || isset($this->_caches[$name])) {
			return $this;
		}
		$this->_caches[$name] = (string) $value;
		return $this;
	}


	public function setCache($name, $value) {
		if ($value === null) {
			unset($this->_caches[$name]);
		} else {
			$this->_caches[$name] = (string) $value;
		}
		return $this;
	}

	protected function sendCaches() {
		$values = [];
		foreach ($this->_caches as $name => $value) {
			if (substr($name, 0, 3) == 'no-') {
				// no-cache no-store
				$values[] = $value ? $name . '=' . $value : $name;
				$name == 'no-cache' && !$value && $this->setHeader('Expires', -1)->$this->setHeader('Pragma', -1);
			} elseif ($name == 'max-age') {
				// max-age
				$values[] = $name . '=' . $value;
				$name == 'max-age' && $this->setHeader('Expires', $value ? gmdate('D, d M Y H:i:s', $value).' GMT' : null);
			} elseif (in_array($name, ['public', 'private']) && in_array($values, ['public', 'private'])) {
				// public  和 private 只能选一个
			} elseif ($value) {
				$values[] = $name;
			}
		}
		$this->setHeader('Cache-Control', $values ? implode(', ', $values) : null);
		return $this;
	}



	public function getToken($token) {
		return $this->_token;
	}

	public function addToken($token) {
		if (!$this->_token) {
			$this->_token = $token;
		}
		return $this;
	}

	public function setToken($token) {
		$this->token = $token;
		return $this;
	}

	public function sendToken() {
		if ($this->_token) {
			$this->_request->tokenParam && $this->setCookie($this->_request->tokenParam, $this->_token, 86400 * 365 * 3);
			$request = $this->_request;
			$this->setHeader($request::TOKEN_HEADER, $this->_token);
		}
		return $this;
	}


	public function getHeaders() {
		return $this->_headers;
	}

	public function setHeaders(array $headers) {
		$this->_headers = [];
		foreach ($headers as $name => $value) {
			if ($value === null) {
				continue;
			}
			$this->_headers[$name] = (string) $value;
		}
		return $this;
	}

	public function getHeader($name, $defaultValue = null) {
		return isset($this->_headers[$name]) ? $this->_headers[$name] : $defaultValue;
	}

	public function addHeader($name, $value) {
		if(!isset($this->_headers[$name]) && $value !== null) {
			$this->_headers[$name] = (string) $value;
		}
		return $this;
	}

	public function setHeader($name, $value) {
		if ($value === null) {
			unset($this->_headers[$name]);
		} else {
			$this->_headers[$name] = (string) $value;
		}
		return $this;
	}


	protected function sendHeaders() {
		if (headers_sent()) {
			return $this;
		}
		$this->addHeader('Content-Type', 'text/html');
		http_response_code($this->_status);

		$this->sendToken();
		$this->sendCaches();
		foreach ($this->getHeaders() as $name => $value) {
			$value = trim($value,  " \t\n\r\0\x0B;");

			if ($name == 'Content-Type') {
				if (strpos($value, ';') === false && ($arrays = explode('/', strtolower($value))) && (in_array($arrays[0], ['text']) || (isset($arrays[1]) && in_array($arrays[1], ['javascript', 'x-javascript', 'js', 'plain', 'html', 'xml', 'css'])))) {
					$value = $value . '; charset=UTF-8';
				}
			} elseif ($name == 'Content-Disposition') {
				if (preg_match('/\s*(?:([0-9a-z_-]+)\s*;)?\s*filename\s*=\s*("[^"]+"|[^;]+)/i', $value, $matches)) {
					$type = trim($matches[1]);
					$filename = trim($matches[2], " \t\n\r\0\x0B\"");
					if (!($userAgent = $this->_request->getHeader('User-Agent')) || strpos($userAgent, 'MSIE ') !== false || (strpos($userAgent, 'Trident/') !== false && strpos($userAgent, 'rv:') !== false && strpos($userAgent, 'opera') === false)) {
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
			header($name . ': '. $value);
		}
		$this->sendCookies();
		return $this;
	}


	public function getMessages() {
		return $this->_messages;
	}

	public function getMessage($code) {
		return empty($this->_messages[$code]) ? false : $this->_messages[$code];
	}

	public function addMessage($error, $data = [], $severity = E_USER_WARNING, $file = __FILE__, $line = __LINE__) {
		try {
			throw new Message($error, $data, $severity, $file, $line);
		} catch (Message $e) {
			if (empty($this->_messages[$e->getCode()])) {
				$this->_messages[$e->getCode()] = $e;
			}
		}
		return $this;
	}

	public function setMessage($error, $data = [], $severity = E_USER_WARNING, $file = __FILE__, $line = __LINE__) {
		try {
			throw new Message($error, $data, $severity, $file, $line);
		} catch (Message $e) {
			$this->_messages[$e->getCode()] = $e;
		}
		return $this;
	}

	public function hasMessage($codes = []) {
		if (!$codes) {
			return !empty($this->_messages);
		}
		foreach ((array) $codes as $code) {
			if (!empty($this->_messages[$code])) {
				return true;
			}
		}
		return false;
	}

	public function removeMessage($code) {
		if (!empty($this->_messages[$code])) {
			unset($this->_messages[$code]);
		}
		return $this;
	}


	public function clearMessages() {
		$this->_messages = [];
		return $this;
	}

	public function getContent($content) {
		return $this->_content;
	}


	public function addContent($content) {
		if ($this->_content === null) {
			$this->_content = $content;
		}
		return $this;
	}

	public function setContent($content) {
		$this->_content = $content;
		return $this;
	}

	public function sendContent() {
		// 204 205 304 和 HEAD 不发送内容
		if (in_array($this->_status, [204, 205, 304]) || $this->_request->getMethod()  == 'OPTIONS' ||  ($this->_request->getMethod() == 'HEAD' && $this->getHeader('Content-Length') !== null)) {
			return $this;
		}

		// 小于200 的发送 空行
		if ($this->_status < 200) {
			return $this;
		}

		if (is_array($this->_content) || (is_object($this->_content) && !is_callable([$this->_content, '__toString']))) {
			echo call_user_func($this->_content);
		} else {
			echo $this->_content;
		}
		return $this;
	}






	// 缓存状态码  304  206 200 412
	public function getCacheStatus() {
		if ($this->_status < 200 || $this->_status >= 300) {
			return $this->_status;
		}

		$etag = $this->getHeader('Etag', '');
		$modified = $this->getHeader('Last-Modified', '');


		// 没匹配到 412 文件已被改变
		if (($ifMatch = $this->_request->getHeader('If-Match')) && $etag != $ifMatch) {
			return 412;
		}

		// 没匹配到 412 文件已被改变
		if (($ifUnmodifiedSince = $this->_request->getHeader('If-Unmodified-Since')) && $ifUnmodifiedSince != $modified) {
			return 412;
		}

		// 没匹配到 200 文件已被改变
		if (($IfNoneMatch = $this->_request->getHeader('If-None-Match', '')) && $IfNoneMatch !=  $etag) {
			return 200;
		}

		// 没匹配到 200 文件已被改变
		if (($ifModifiedSince = $this->_request->getHeader('If-Modified-Since', '')) && $ifModifiedSince != $modified) {
			return 200;
		}

		// ifRange 已被修改
		if (($ifRange = $this->_request->getHeader('If-Range')) && $ifRange != $etag && $ifRange != $modified) {
			return 200;
		}

		// 没有范围
		if (!$this->_request->getRanges()) {
			return $IfNoneMatch || $ifModifiedSince ? 304 : 200;
		}

		// 不支持分段
		if ($this->getHeader('Accept-Ranges') != 'bytes') {
			return 200;
		}

		return 206;
	}



	public function setAjax($data) {
		$this->setHeader('X-Ajax', 'true');
		$type = strtolower($this->_request->isAjax());

		if ($type == 'query') {
			$data = merge_string($data);
		} elseif($type == 'xml') {
			$function = function ($arrays) use(&$function) {
				$ret = $attr = '';
				 foreach ($arrays as $tag => $value) {
				 	if (!preg_match('/^[a-z][0-9a-z_]*$/i', $tag)) {
				 		$attr = ' k="' . htmlspecialchars($tag, ENT_QUOTES) . '"';
						$tag  = 'item';
				 	}
			        $ret .=  '<' . $tag . $attr.'>' .((is_array($value) || is_object($value)) ? $function($value) :  htmlspecialchars($value, ENT_QUOTES)) . '</' . $tag . '>' ."\n";
			    }
			    return $ret;
			};
			$this->setHeader('Content-Type', 'application/xml');
			$data = '<?xml version="1.0" encoding="UTF-8"?><root>'. $function($data) .'</root>';
		} elseif ($this->ajaxJS && !in_array($type, ['true', 'false', 'null', 'json']) && !intval(substr($type, 0, 1)) && ($function = preg_replace('/[^0-9a-z_.-]/i', '', $this->_ajax))) {
				$this->setHeader('Content-Type', 'application/x-javascript');
				$data = $function . '(' . json_encode($data) . ')';
		} else {
			if ($this->_request->getMethod() != 'POST'|| strtolower($this->_request->getHeader('X-Requested-with')) == 'xmlhttprequest') {
				$this->setHeader('Content-Type', 'application/json');
			}
			$data = json_encode($data);
		}
		return $this->setContent($data);
	}





	public function send() {
		$this->sendHeaders();
		$this->sendContent();
		return $this;
	}

	public function clear() {
		$this->_headers = $this->_caches = $this->_cookies = $this->_messages  = [];
		$this->_status = 200;
		$this->_content = null;
		return $this;
	}

	public function getRedirect($redirects = [], $defaults = []) {
		$path = $this->_request->getPath();
		$host = $this->_request->getHost();
		$redirects = (array) $redirects;
		$defaults = $defaults ? (array) $defaults : [];
		if ($host) {
			$defaults = array_merge($defaults, ['http://' . $host]);
		}
		if ($redirect = $this->_request->getParam('redirect')) {
			$redirects[] = $redirect;
		}
		if (in_array('referer',  $redirects) && !($referer = $this->_request->getHeader('Referer'))) {
			$redirects[] = $referer;
		}
		$ret = reset($defaults);
		$break = false;
		foreach ($redirects as $redirect) {
			if (!$redirect || !is_string($redirect) || in_array($redirect, ['referer'])) {
				continue;
			}
			if ($host && !preg_match('/^(https?\:)?\/\/\w+\.\w+/i', $redirect)) {
				if ($redirect{0} != '/') {
					if (!$path) {
					} elseif (substr($path, -1, 1) == '/') {
						$redirect = $path . $redirect;
					} else {
						$redirect = dirname($path) .'/'. $redirect;
					}
				}
				$redirect = '//'. $host . '/' . ltrim($redirect, '/');
			}
			foreach ($defaults as $default) {
				if ($break = domain_match($redirect, $default)) {
					break;
				}
			}
			if (!$default || $break) {
				$ret = $redirect;
				break;
			}
		}
		return $ret;
	}
}