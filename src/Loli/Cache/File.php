<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-02-17 11:37:04
/*	Updated: UTC 2015-03-22 08:18:29
/*
/* ************************************************************************** */
namespace Loli\Cache;
class_exists('Loli\Cache\Base') || exit;
class File extends Base{

	private $_data = [];

	private $_ttl = [];

	private $_groups = [];

	private $_dir = './';

	public function __construct(array $args, $key = '') {
		if (!empty($args['dir'])) {
			$this->_dir = $args['dir'];
		}
		$this->key = $key;
	}

	public function get($key, $group = 'default') {
		++$this->count['get'];

		if (!isset($this->_data[$group][$key])) {
			$this->_data[$group][$key] = false;
			$this->_get($key, $group);
		}
		if (is_object($this->_data[$group][$key])) {
			return clone $this->_data[$group][$key];
		}
		return $this->_data[$group][$key];
	}

	public function add($data, $key, $group = 'default', $ttl = 0) {
		++$this->count['add'];
		if ($data === NULL || $data === false || ($ttl = intval($ttl)) < -1 || $this->get($key, $group) !== false) {
			return false;
		}
		if (is_object($data)) {
			$data = clone $data;
		}
		$this->_data[$group][$key] = $data;
		$this->_ttl[$group][$key] = $ttl == -1 ? -1 : time() + $ttl;
		return $this->_set($key, $group);
	}

	public function set($data, $key, $group = 'default', $ttl = 0) {
		++$this->count['set'];
		if ($data === NULL || $data === false || ($ttl = intval($ttl)) < -1) {
			return false;
		}
		if (is_object($data)) {
			$data = clone $data;
		}
		$this->_data[$group][$key] = $data;
		$this->_ttl[$group][$key] = $ttl == -1 ? -1 : time() + $ttl;
		return $this->_set($key, $group);
	}


	public function incr($n, $key, $group = 'default') {
		++$this->count['incr'];
		if (($n = intval($n)) < 1) {
			return false;
		}
		$r = false;
		if (isset($this->_ttl[$group][$key]) && $this->_ttl[$group][$key] === 0) {
			if (isset($this->_data[$group][$key]) && $this->_data[$group][$key] !== false) {
				$this->_data[$group][$key] += $n;
				return true;
			}
			return false;
		}

		if ($this->_get($key, $group)) {
			$this->_data[$group][$key] += $n;
			$this->_set($key, $group);
			return true;
		}
		return false;
	}


	public function decr($n, $key, $group = 'default') {
		++$this->count['decr'];
		if (($n = intval($n)) < 1) {
			return false;
		}
		$r = false;
		if (isset($this->_ttl[$group][$key]) && $this->_ttl[$group][$key] === 0) {
			if (isset($this->_data[$group][$key]) && $this->_data[$group][$key] !== false) {
				$this->_data[$group][$key] -= $n;
				return true;
			}
			return false;
		}

		if ($this->_get($key, $group)) {
			$this->_data[$group][$key] -= $n;
			$this->_set($key, $group);
			return true;
		}
		return false;
	}


	public function delete($key, $group = 'default', $ttl = 0) {
		++$this->count['delete'];
		if ($ttl > 0) {
			$this->_get($key, $group);
			if (!isset($this->_ttl[$group][$key])) {
				return false;
			}
			if ($this->_ttl[$group][$key] === 0) {
				return true;
			}
			if ( $this->_ttl[$group][$key] == -1 || ($this->_ttl[$group][$key] <= ($ttl += time()))) {
				$this->_ttl[$group][$key] = $ttl;
				return $this->_set($key, $group);
			}
			return true;
		}
		if (isset($this->_ttl[$group][$key]) && $this->_ttl[$group][$key] === 0) {
			unset($this->_ttl[$group][$key], $this->_data[$group][$key]);
			return true;
		}
		unset($this->_ttl[$group][$key], $this->_data[$group][$key]);
		return is_file($file = $this->_file($key, $group)) && @unlink($file);
	}


	public function ttl($key, $group = 'default') {
		++$this->count['ttl'];
		if (!isset($this->_ttl[$group][$key])) {
			$this->_ttl[$group][$key] = false;
			$this->_get($key, $group);
		}
		if (!$this->_ttl[$group][$key] || $this->_ttl[$group][$key] == -1) {
			return $this->_ttl[$group][$key];
		}
		if (($ttl = $this->_ttl[$group][$key] - time()) < 0) {
			return $this->_ttl[$group][$key] = false;
		}
		return $ttl;
	}

	public function flush($mem = false) {
		$this->_groups = $this->_ttl = $this->_data = [];
		return $mem || $this->_undir($this->_dir);
	}

	public function addServers($group, array $a) {
		return true;
	}



	private function _get($key, $group) {
		if (is_file($file = $this->_file($key, $group))) {
			if (($a = @unserialize(fread(fopen($file, 'rb'), filesize($file)))) && ($a['ttl'] >= time() || $a['ttl'] == -1)) {
				$this->_data[$group][$key] = $a['data'];
				$this->_ttl[$group][$key] = $a['ttl'];
				return true;
			} else {
				@unlink($file);
			}
		}
		return false;
	}

	private function _set($key, $group) {
		if (!empty($this->_ttl[$group][$key]) && isset($this->_data[$group][$key]) && $this->_data[$group][$key] !== false) {
			return (bool) fwrite(fopen($this->_file($key, $group), 'wb'), serialize(['data' => $this->_data[$group][$key], 'ttl' => $this->_ttl[$group][$key]]));
		}
		return isset($this->_ttl[$group][$key]) && !$this->_ttl[$group][$key] && $this->_ttl[$group][$key] !== false;
	}


	private function _file($key, $group) {
		$group = $group && ($group = preg_replace('/[^0-9a-zA-Z_-]/', '-.-', $group)) ? $group : 'default';
		$group = $this->_dir . '/'. $group;
		if (!in_array($group, $this->_groups)) {
			is_dir($group) || mkdir($group, 0755, true);
			$this->_groups[] = $group;
		}
		return $group . '/' . md5($key . $this->key) . substr(md5($this->key . $key), 12, 8);
	}

	private function _undir($dir) {
		if (!is_dir($dir)) {
			return false;
		}
		$opendir = opendir($dir);
		while ($name = readdir($opendir)) {
			if (in_array($name, ['.', '..'])) {
				continue;
			}
			$path = $dir . '/' . $name;
			is_dir($path) ? $this->_undir($path) : @unlink($path);
		}
		closedir($opendir);
		return true;
	}
}