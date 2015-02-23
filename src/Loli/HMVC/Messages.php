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
/*	Updated: UTC 2015-02-22 13:14:08
/*
/* ************************************************************************** */
namespace Loli\HMVC;
use Loli\URL;
class Messages{
	public function __construct($messages = [], array $data = [], $redirect = false, $refresh = 0, $title = 'Messages') {
		$messages = $messages ? (array) $messages : [200];
		$arrays = [];
		foreach ($messages as $message) {
			$message = (array) $message;

			// args
			$args = $message;
			reset($args);
			unset($args[key($args)]);


			// code
			$code = reset($message);

			// message
			$message = self::lang($message);

			$arrays[$code] = ['message' => $message, 'code' => $code, 'args' => $args];
		}

		$data['title'] = Lang::get($title ? $title : 'Messages',['message', 'default']);
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


	public function __call($name, array $params) {
		return call_user_func_array([$this->_view, $name], $params);
	}
}