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
/*	Updated: UTC 2015-03-22 08:17:46
/*
/* ************************************************************************** */
namespace Loli\Cache;
class_exists('Loli\Cache\Base') || exit;
class RAM extends Base {
	public $_data = [];
	public function __construct(array $args, $key = '') {
	}

	public function get($key, $group = 'default', $mem = false) {
		++$this->count['get'];
		if (!isset($this->_data[$group][$key])) {
			return false;
		}
		if (is_object($this->_data[$group][$key])) {
			return clone $this->_data[$group][$key];
		}
		return $this->_data[$group][$key];
	}

	public function add($data, $key, $group = 'default', $ttl = 0) {
		++$this->count['add'];
		if ($data === NULL || $data === false || $this->get($key, $group) !== false) {
			return false;
		}
		$this->_data[$group][$key] = $data;
		return true;
	}

	public function set($data, $key, $group = 'default', $ttl = 0) {
		++$this->count['set'];
		if ($data === NULL || $data === false) {
			return false;
		}
		$this->_data[$group][$key] = $data;
		return true;
	}


	public function incr($n, $key, $group = 'default') {
		++$this->count['incr'];
		if (($n = intval($n)) < 1) {
			return false;
		}
		if (!isset($this->_data[$group][$key]) || $this->_data[$group][$key] === false) {
			return false;
		}
		$this->_data[$group][$key] += $n;
		return true;
	}

	public function decr($n, $key, $group = 'default') {
		++$this->count['decr'];
		if (($n = intval($n)) < 1) {
			return false;
		}
		if (!isset($this->_data[$group][$key]) || $this->_data[$group][$key] === false) {
			return false;
		}
		$this->_data[$group][$key] -= $n;
		return true;
	}


	public function delete($key, $group = 'default', $ttl = 0) {
		++$this->count['delete'];
		if (isset($this->_data[$group][$key]) && $this->_data[$group][$key] !== false) {
			if ($ttl < 1) {
				unset($this->_data[$group][$key]);
			}
			$unset = true;
		}
		return empty($unset);
	}

	public function ttl($key, $group = 'default') {
		if (isset($this->_data[$group][$key]) && $this->_data[$group][$key] !== false) {
			return 0;
		}
		return false;
	}


	public function flush($mem = false) {
		$this->_data = [];
		return true;
	}

	public function addServers($group, array $servers) {
		return true;
	}
}