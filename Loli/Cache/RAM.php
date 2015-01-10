<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-12-17 05:59:42
/*	Updated: UTC 2015-01-09 08:39:41
/*
/* ************************************************************************** */
namespace Loli\Cache;
class RAM extends Base {
	public $_data = [];
	public function __construct($args, $key = '') {
	}

	public function get($key, $list = 'default', $mem = false) {
		++$this->count['get'];
		if (!isset($this->_data[$list][$key])) {
			return false;
		}
		if (is_object($this->_data[$list][$key])) {
			return clone $this->_data[$list][$key];
		}
		return $this->_data[$list][$key];
	}

	public function add($data, $key, $list = 'default', $ttl = 0) {
		++$this->count['add'];
		if ($data === null || $data === false || $this->get($key, $list) !== false) {
			return false;
		}
		$this->_data[$list][$key] = $data;
		return true;
	}

	public function set($data, $key, $list = 'default', $ttl = 0) {
		++$this->count['set'];
		if ($data === null || $data === false) {
			return false;
		}
		$this->_data[$list][$key] = $data;
		return true;
	}


	public function incr($n, $key, $list = 'default') {
		++$this->count['incr'];
		if (($n = intval($n)) < 1) {
			return false;
		}
		if (!isset($this->_data[$list][$key]) || $this->_data[$list][$key] === false) {
			return false;
		}
		$this->_data[$list][$key] += $n;
		return true;
	}

	public function decr($n, $key, $list = 'default') {
		++$this->count['decr'];
		if (($n = intval($n)) < 1) {
			return false;
		}
		if (!isset($this->_data[$list][$key]) || $this->_data[$list][$key] === false) {
			return false;
		}
		$this->_data[$list][$key] -= $n;
		return true;
	}


	public function delete($key, $list = 'default', $ttl = 0) {
		++$this->count['delete'];
		if (isset($this->_data[$list][$key]) && $this->_data[$list][$key] !== false) {
			if ($ttl < 1) {
				unset($this->_data[$list][$key]);
			}
			$unset = true;
		}
		return empty($unset);
	}

	public function ttl($key, $list = 'default') {
		if (isset($this->_data[$list][$key]) && $this->_data[$list][$key] !== false) {
			return 0;
		}
		return false;
	}


	public function flush($mem = false) {
		$this->_data = [];
		return true;
	}

	public function addServers($list, $a) {
		return true;
	}
}