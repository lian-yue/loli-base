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
/*	Updated: UTC 2015-02-22 04:24:16
/*
/* ************************************************************************** */
namespace Loli\HMVC;
use Loli\Lang;
class Messages {
	private $_view;
	public function __construct(array $messages, array $data = [], $redirect = false, $refresh = 0) {
		$data['title'] = empty($data['title']) ? [Lang::get(['Messages', ['message', 'default']])] : (array) $data['title'];
		if (!isset($data['redirect'])) {
			$data['redirect'] = $redirect === false || $redirect === null ? false : $this->getRedirect($redirect);
		}
		if (!isset($data['refresh'])) {
			$data['refresh'] = $refresh;
		}
		$this->_view = new View('messages', $data);
	}

	public function __call($name, array $params) {
		return call_user_func_array([$this->_view, $name], $params);
	}
}