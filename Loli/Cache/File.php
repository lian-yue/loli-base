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
/*	Updated: UTC 2014-12-31 07:10:55
/*
/* ************************************************************************** */
namespace Loli\Cache;
class File extends Base{

	private $_data = [];

	private $_ttl = [];

	private $_list = [];

	private $_dir = './';

	public function __construct($dir, $key = '') {
		$this->_dir = is_array($dir) ? $_dir['dir'] : $_dir;
		$this->key = $key;
	}

	public function get($key, $list = 'default') {
		++$this->count['get'];

		if (!isset($this->_data[$list][$key])) {
			$this->_data[$list][$key] = false;
			$this->_get($key, $list);
		}
		if (is_object($this->_data[$list][$key])) {
			return clone $this->_data[$list][$key];
		}
		return $this->_data[$list][$key];
	}

	public function add($data, $key, $list = 'default', $ttl = 0) {
		++$this->count['add'];
		if ($data === null || $data === false || ($ttl = intval($ttl)) < -1 || $this->get($key, $list) !== false) {
			return false;
		}
		if (is_object($data)) {
			$data = clone $data;
		}
		$this->_data[$list][$key] = $data;
		$this->_ttl[$list][$key] = $ttl == -1 ? -1 : time() + $ttl;
		return $this->_set($key, $list);
	}

	public function set($data, $key, $list = 'default', $ttl = 0) {
		++$this->count['set'];
		if ($data === null || $data === false || ($ttl = intval($ttl)) < -1) {
			return false;
		}
		if (is_object($data)) {
			$data = clone $data;
		}
		$this->_data[$list][$key] = $data;
		$this->_ttl[$list][$key] = $ttl == -1 ? -1 : time() + $ttl;
		return $this->_set($key, $list);
	}


	public function incr($n, $key, $list = 'default') {
		++$this->count['incr'];
		if (($n = intval($n)) < 1) {
			return false;
		}
		$r = false;
		if (isset($this->_ttl[$list][$key]) && $this->_ttl[$list][$key] === 0) {
			if (isset($this->_data[$list][$key]) && $this->_data[$list][$key] !== false) {
				$this->_data[$list][$key] += $n;
				return true;
			}
			return false;
		}

		if ($this->_get($key, $list)) {
			$this->_data[$list][$key] += $n;
			$this->_set($key, $list);
			return true;
		}
		return false;
	}


	public function decr($n, $key, $list = 'default') {
		++$this->count['decr'];
		if (($n = intval($n)) < 1) {
			return false;
		}
		$r = false;
		if (isset($this->_ttl[$list][$key]) && $this->_ttl[$list][$key] === 0) {
			if (isset($this->_data[$list][$key]) && $this->_data[$list][$key] !== false) {
				$this->_data[$list][$key] -= $n;
				return true;
			}
			return false;
		}

		if ($this->_get($key, $list)) {
			$this->_data[$list][$key] -= $n;
			$this->_set($key, $list);
			return true;
		}
		return false;
	}


	public function delete($key, $list = 'default', $ttl = 0) {
		++$this->count['delete'];
		if ($ttl > 0) {
			$this->_get($key, $list);
			if (!isset($this->_ttl[$list][$key])) {
				return false;
			}
			if ($this->_ttl[$list][$key] === 0) {
				return true;
			}
			if ( $this->_ttl[$list][$key] == -1 || ($this->_ttl[$list][$key] <= ($ttl += time()))) {
				$this->_ttl[$list][$key] = $ttl;
				return $this->_set($key, $list);
			}
			return true;
		}
		if (isset($this->_ttl[$list][$key]) && $this->_ttl[$list][$key] === 0) {
			unset($this->_ttl[$list][$key], $this->_data[$list][$key]);
			return true;
		}
		unset($this->_ttl[$list][$key], $this->_data[$list][$key]);
		return is_file($file = $this->_file($key, $list)) && @unlink($file);
	}


	public function ttl($key, $list = 'default') {
		++$this->count['ttl'];
		if (!isset($this->_ttl[$list][$key])) {
			$this->_ttl[$list][$key] = false;
			$this->_get($key, $list);
		}
		if (!$this->_ttl[$list][$key] || $this->_ttl[$list][$key] == -1) {
			return $this->_ttl[$list][$key];
		}
		if (($ttl = $this->_ttl[$list][$key] - time()) < 0) {
			return $this->_ttl[$list][$key] = false;
		}
		return $ttl;
	}

	public function flush($mem = false) {
		$this->_list = $this->_ttl = $this->_data = [];
		return $mem || $this->_undir($this->_dir);
	}

	public function addServers($list, $a) {
		return true;
	}



	private function _get($key, $list) {
		if (is_file($file = $this->_file($key, $list))) {
			if (($a = @unserialize(fread(fopen($file, 'rb'), filesize($file)))) && ($a['ttl'] >= time() || $a['ttl'] == -1)) {
				$this->_data[$list][$key] = $a['data'];
				$this->_ttl[$list][$key] = $a['ttl'];
				return true;
			} else {
				@unlink($file);
			}
		}
		return false;
	}

	private function _set($key, $list) {
		if (!empty($this->_ttl[$list][$key]) && isset($this->_data[$list][$key]) && $this->_data[$list][$key] !== false) {
			return (bool) fwrite(fopen($this->_file($key, $list), 'wb'), serialize(['data' => $this->_data[$list][$key], 'ttl' => $this->_ttl[$list][$key]]));
		}
		return isset($this->_ttl[$list][$key]) && !$this->_ttl[$list][$key] && $this->_ttl[$list][$key] !== false;
	}


	private function _file($key, $list) {
		$list = $list && ($list = preg_replace('/[^0-9a-zA-Z_-]/', '-.-', $list)) ? $list : 'default';
		$list = $this->_dir . '/'. $list;
		if (!in_array($list, $this->_list)) {
			is_dir($list) || mkdir($list, 0755, true);
			$this->_list[] = $list;
		}
		return $list . '/' . md5($key . $this->key) . substr(md5($this->key . $key), 12, 8);
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
		closedir ($opendir);
		return true;
	}
}