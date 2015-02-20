<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-20 13:28:45
/*	Updated: UTC 2015-02-20 13:52:16
/*
/* ************************************************************************** */
namespace Loli\HMVC;
use Iterator;
class Messages implements Iterator {

	private $_messages = [];

	public function all() {
		return $this->_messages;
	}

	public function get($code) {
		return empty($this->_messages[$code]) ? false : $this->_messages[$code];
	}

	public function add($error, $data = [], $severity = E_USER_WARNING, $file = __FILE__, $line = __LINE__) {
		try {
			throw new Message($error, $data, $severity, $file, $line);
		} catch (Message $e) {
			if (empty($this->_messages[$e->getCode()])) {
				$this->_messages[$e->getCode()] = $e;
			}
		}
		return $this;
	}

	public function set($error, $data = [], $severity = E_USER_WARNING, $file = __FILE__, $line = __LINE__) {
		try {
			throw new Message($error, $data, $severity, $file, $line);
		} catch (Message $e) {
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









	public function current() {
		return current($this->var);
	}

	public function key() {
		return key($this->var);
	}

	public function next() {
		return next($this->var);
	}

	public function rewind() {
		reset($this->_messages);
	}

	public function valid() {
		return $this->current() !== false;
	}
}