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
/*	Updated: UTC 2015-02-16 13:28:29
/*
/* ************************************************************************** */
namespace Loli\HMVC;
use Loli\Message as Message_;
class Message{
	public function all() {
		return $this->_messages;
	}

	public function get($code) {
		return empty($this->_messages[$code]) ? false : $this->_messages[$code];
	}

	public function add($error, $data = [], $severity = E_USER_WARNING, $file = __FILE__, $line = __LINE__) {
		try {
			throw new Message_($error, $data, $severity, $file, $line);
		} catch (Message_ $e) {
			if (empty($this->_messages[$e->getCode()])) {
				$this->_messages[$e->getCode()] = $e;
			}
		}
		return $this;
	}

	public function set($error, $data = [], $severity = E_USER_WARNING, $file = __FILE__, $line = __LINE__) {
		try {
			throw new Message_($error, $data, $severity, $file, $line);
		} catch (Message_ $e) {
			$this->_messages[$e->getCode()] = $e;
		}
		return $this;
	}

	public function has($codes = []) {
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

	public function remove($code) {
		if (!empty($this->_messages[$code])) {
			unset($this->_messages[$code]);
		}
		return $this;
	}


	public function clear() {
		$this->_messages = [];
		return $this;
	}
}