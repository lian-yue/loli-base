<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-16 13:21:40
/*	Updated: UTC 2015-02-24 05:49:18
/*
/* ************************************************************************** */
namespace Loli\HMVC;
use Iterator, Loli\Exception, Loli\Lang;
class Message extends Exception implements Iterator{
	protected $code = 200;
	protected $message;
	protected $data;
	protected $redirect;
	protected $refresh;
	protected $args;
	protected $title = 'Messages';

	protected $hosts = [];

	protected $results = [];
	public function __construct($message = [], $data = [], $redirect = true, $refresh = 3, Message $previous = null) {
		$this->hosts = empty($_SERVER['LOLI']['MESSAGE']['hosts']) ? [] : (array) $_SERVER['LOLI']['MESSAGE']['hosts'];


		$message = $message ? (array) $message : [$this->code];

		// previous　变量自动缩进
		foreach(['data' => [], 'redirect' => false, 'refresh' => 3] as $key => $value) {
			if ($$key instanceof Message) {
				$previous = $$key;
				$$key = $value;
				break;
			}
		}

		// data 换成数组
		$data = (array) $data;







		// args
		$this->args = $message;
		reset($this->args);
		unset($this->args[key($this->args)]);


		// code
		$code = reset($message);

		// message
		$message = self::lang($message);

		// 重定向
		if (isset($data['redirect']) && (is_string($data['redirect']) || is_bool($data['redirect']))) {
			$redirect = $data['redirect'];
		} else {
			if (isset($data['redirect'])) {
				$redirect = $data['redirect'];
			}
			if ($redirect === false || $redirect === true || $redirect === null) {
				$redirect = (bool) $redirect;
			} else {
				$redirect = get_redirect($redirect, $this->hosts);
			}
		}
		$this->redirect = $redirect;

		// refresh 刷新
		$this->refresh = (int) (isset($data['refresh']) ? $data['refresh'] : $refresh);

		// data
		$this->data = $data;

		// 注册父级
		parent::__construct($message, $code, $previous);

		// 循环所有消息
		$message = $this;
		do {
			$this->results[$message->getCode()] = $message;
		} while ($message = $message->getPrevious());
		$this->results = array_reverse($this->results, true);
	}


	public function hasCode($codes = []) {
		if (!$codes) {
			return true;
		}
		$codes = (array) $codes;
		$message = $this;
		do {
			if (in_array($message->getCode(), $codes)) {
				return true;
			}
		} while ($message = $message->getPrevious());
		return false;
	}



	public function getArgs() {
		return $this->args;
	}

	public function getRedirect() {
		return $this->redirect;
	}
	public function getRefresh() {
		return $this->refresh;
	}

	public function getData() {
		return $this->data;
	}

	public function getTitle() {
		$title = $this->title;
		$message = $this;
		do {
			if (($data = $message->getData()) && !empty($data['title'])) {
				$title = $data['title'];
			}
		} while ($message = $message->getPrevious());
		return self::lang($title);
	}


	public static function lang($message) {
		return Lang::get($message, ['message', 'default']);
	}



	public function rewind() {
		reset($this->results);
	}

	public function current() {
		return current($this->results);
	}

	public function key() {
		return key($this->results);
	}

	public function next() {
		return next($this->results);
	}

	public function valid() {
		$key = key($this->results);
		return ($key !== null && $key !== false);
	}






	/*public function __construct($message = [], array $data = [], $redirect = false, $refresh = 0) {
		$message = $message ? (array) $message : [200];


		// args
		$args = $message;
		reset($args);
		unset($args[key($args)]);

		// code
		$code = reset($message);

		// message
		$message = self::lang($message);



		$data = ['message' => $message, 'code' => $code, 'args' => $args] + $data;

		$data['title'] = self::lang(empty($data['title']) ? 'Messages' : $data['title']);

		if (!isset($data['redirect'])) {
			if ($redirect === false || $redirect === null) {
				$data['redirect'] = false;
			} elseif ($redirect === true) {
				$data['redirect'] = true;
			} else {
				$data['redirect'] = $this->getRedirect($redirect);
			}
		}
		if (!isset($data['refresh'])) {
			$data['refresh'] = $refresh;
		}
		$this->_view = new View('messages', $data);
	}


	public static function lang($message) {
		return Lang::get($message, ['message', 'default']);
	}

	/*public function getRedirect($redirects = [], $defaults = []) {
		$path = $this->request->getPath();
		$host = $this->request->getHost();
		$redirects = (array) $redirects;
		$defaults = $defaults ? (array) $defaults : [];
		$defaults = array_merge($defaults, ['http://' . $host]);
		if ($redirect = $this->request->getParam('redirect')) {
			$redirects[] = $redirect;
		}
		if (in_array('referer',  $redirects) && !($referer = $this->request->getHeader('Referer'))) {
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
	}*/
}