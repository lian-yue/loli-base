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
use Loli\Lang, Loli\Router, Loli\Request, Loli\Response;
class Messages{
	private $_view;

	private $messages = [];
	public function __construct(Request &$request, Response &$response, array $messages, $title = 'Messages', array $data = [], $redirect = false, $refresh = 0) {
		/*$data['title'] = Lang::get($title ? $title : 'Messages',['message', 'default']);
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
		$this->_view = new View('messages', $data);*/
	}

	public function add($message, array $data = []) {
		$message = $message ? (array) $message : [0];
		if (empty($this->messages[reset($message)])) {
			$this->set($message, $data);
		}
		return $this;
	}

	public function set($message, array $data = []) {

		// message
		$message = $message ? (array) $message : [0];

		// args
		$args = $message;
		reset($args);
		unset($args[key($args)]);

		// code
		$code = reset($message);


		$this->messages[$code] = ['message' => Lang::get($message, ['message', 'default']), 'code' => $code, 'args' => $args, 'data' => $data];
		return $this;
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