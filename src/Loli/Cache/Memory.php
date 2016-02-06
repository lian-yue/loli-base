<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-21 13:42:16
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
/*	Created: UTC 2014-12-17 05:59:42
/*	Updated: UTC 2015-04-07 14:30:26
/*
/* ************************************************************************** */
namespace Loli\Cache;


class Memory extends Base {
	private $_data = [];
	private $_ttl = [];

	public function get($key, $mem = false) {
		++$this->statistics['get'];
		if (!isset($this->_data[$key]) || !isset($this->_ttl[$key]) || $this->_ttl[$key] === false || ($this->_ttl[$key] > 0 && $this->_ttl[$key] < time())) {
			$this->delete($key);
			return false;
		}
		if (is_object($this->_data[$key])) {
			return clone $this->_data[$key];
		}
		return $this->_data[$key];
	}

	public function add($value, $key, $ttl = 0) {
		++$this->statistics['add'];
		if ($value === NULL || $value === false || $this->get($key) !== false) {
			return false;
		}
		$this->_data[$key] = $value;
		$this->_ttl[$key] = $ttl ? ($ttl == -1 ? -1 : time() + $ttl) : 0;
		return true;
	}

	public function set($value, $key, $ttl = 0) {
		++$this->statistics['set'];
		if ($value === NULL || $value === false) {
			return false;
		}
		$this->_data[$key] = $value;
		$this->_ttl[$key] = $ttl ? ($ttl == -1 ? -1 : time() + $ttl) : 0;
		return true;
	}


	public function incr($n, $key) {
		++$this->statistics['incr'];
		if (($n = intval($n)) < 1 || $this->get($key) === false) {
			return false;
		}
		$this->_data[$key] += $n;
		return $this->_data[$key];
	}

	public function decr($n, $key) {
		++$this->statistics['decr'];
		if (($n = intval($n)) < 1 || $this->get($key) === false) {
			return false;
		}
		$this->_data[$key] -= $n;
		return $this->_data[$key];
	}


	public function delete($key, $ttl = 0) {
		++$this->statistics['delete'];
		if (isset($this->_data[$key])) {
			if ($ttl > 0) {
				$this->_ttl[$key] = time() + $ttl;
			} else {
				unset($this->_ttl[$key], $this->_data[$key]);
			}
			return true;
		}
		return false;
	}

	public function ttl($key) {
		++$this->statistics['ttl'];
		if (isset($this->_data[$key])) {
			if ($this->_ttl[$key] > 1) {
				if (($ttl = $this->_ttl[$key] - time()) < 0) {
					$this->delete($key);
					return false;
				}
				return $ttl;
			} else {
				return $this->_ttl[$key];
			}
		}
		return false;
	}


	public function flush($mem = false) {
		$this->_ttl = $this->_data = [];
		return true;
	}

	public function addServers(array $servers) {
		return true;
	}
}