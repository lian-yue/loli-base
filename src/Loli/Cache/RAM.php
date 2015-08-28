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
class_exists('Loli\Cache\Base') || exit;
class RAM extends Base {
	private $_data = [];
	private $_ttl = [];

	public function __construct(array $args, $key = '') {

	}

	public function get($key, $group = 'default', $mem = false) {
		++$this->statistics['get'];
		if (!isset($this->_data[$group][$key]) || !isset($this->_ttl[$group][$key]) || $this->_ttl[$group][$key] === false || ($this->_ttl[$group][$key] > 0 && $this->_ttl[$group][$key] < time())) {
			$this->delete($key, $group);
			return false;
		}
		if (is_object($this->_data[$group][$key])) {
			return clone $this->_data[$group][$key];
		}
		return $this->_data[$group][$key];
	}

	public function add($value, $key, $group = 'default', $ttl = 0) {
		++$this->statistics['add'];
		if ($value === NULL || $value === false || $this->get($key, $group) !== false) {
			return false;
		}
		$this->_data[$group][$key] = $value;
		$this->_ttl[$group][$key] = $ttl ? ($ttl == -1 ? -1 : time() + $ttl) : 0;
		return true;
	}

	public function set($value, $key, $group = 'default', $ttl = 0) {
		++$this->statistics['set'];
		if ($value === NULL || $value === false) {
			return false;
		}
		$this->_data[$group][$key] = $value;
		$this->_ttl[$group][$key] = $ttl ? ($ttl == -1 ? -1 : time() + $ttl) : 0;
		return true;
	}


	public function incr($n, $key, $group = 'default') {
		++$this->statistics['incr'];
		if (($n = intval($n)) < 1 || $this->get($key, $group) === false) {
			return false;
		}
		$this->_data[$group][$key] += $n;
		return true;
	}

	public function decr($n, $key, $group = 'default') {
		++$this->statistics['decr'];
		if (($n = intval($n)) < 1 || $this->get($key, $group) === false) {
			return false;
		}
		$this->_data[$group][$key] -= $n;
		return true;
	}


	public function delete($key, $group = 'default', $ttl = 0) {
		++$this->statistics['delete'];
		if (isset($this->_data[$group][$key])) {
			if ($ttl > 0) {
				$this->_ttl[$group][$key] = time() + $ttl;
			} else {
				unset($this->_ttl[$group][$key], $this->_data[$group][$key]);
			}
			return true;
		}
		return false;
	}

	public function ttl($key, $group = 'default') {
		++$this->statistics['ttl'];
		if (isset($this->_data[$group][$key])) {
			if ($this->_ttl[$group][$key] > 1) {
				if (($ttl = $this->_ttl[$group][$key] - time()) < 0) {
					$this->delete($key, $group);
					return false;
				}
				return $ttl;
			} else {
				return $this->_ttl[$group][$key];
			}
		}
		return false;
	}


	public function flush($mem = false) {
		$this->_ttl = $this->_data = [];
		return true;
	}

	public function addServers($group, array $servers) {
		return true;
	}
}