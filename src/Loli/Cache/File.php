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
/*	Created: UTC 2014-02-17 11:37:04
/*	Updated: UTC 2015-04-07 14:31:37
/*
/* ************************************************************************** */
namespace Loli\Cache;
class_exists('Loli\Cache\Base') || exit;
class File extends Base{
	private $_groups = [];

	private $_data = [];

	private $_dir = './';

	public function __construct(array $args, $key = '') {
		if (!empty($args['dir'])) {
			$this->_dir = $args['dir'];
		}
		$this->key = $key;
	}

	public function get($key, $group = 'default') {
		++$this->statistics['get'];
		if (isset($this->_data[$group][$key])) {
			if (is_object($this->_data[$group][$key])) {
				return clone $this->_data[$group][$key];
			}
			return $this->_data[$group][$key];
		}

		if (($data = $this->_get($key, $group)) && ($data['expire'] === -1 || $data['expire'] >= time())) {
			return $data['value'];
		}
		return false;
	}

	public function add($value, $key, $group = 'default', $ttl = 0) {
		++$this->statistics['add'];
		if ($value === NULL || $value === false) {
			return false;
		}
		if ($ttl) {
			if (($data = $this->_get($key, $group)) && ($data['expire'] === -1 || $data['expire'] >= time())) {
				return false;
			}
			unset($this->_data[$group][$key]);
			$this->_set($value, $key, $group, $ttl == -1 ? -1 : time() + $ttl);
		} else {
			if (!isset($this->_data[$group][$key])) {
				return false;
			}
			if (is_object($value)) {
				$value = clone $value;
			}
			$this->_data[$group][$key] = $value;
		}
		return true;
	}

	public function set($value, $key, $group = 'default', $ttl = 0) {
		++$this->statistics['set'];
		if ($value === NULL || $value === false) {
			return false;
		}
		if ($ttl) {
			unset($this->_data[$group][$key]);
			$this->delete($key, $group);
			return $this->_set($value, $key, $group, $ttl == -1 ? -1 : time() + $ttl);
		} else {
			if (is_object($value)) {
				$value = clone $value;
			}
			$this->_data[$group][$key] = $value;
			return true;
		}
		return false;
	}


	public function incr($n, $key, $group = 'default') {
		++$this->statistics['incr'];
		if (($n = intval($n)) < 1) {
			return false;
		}
		if (isset($this->_data[$group][$key])) {
			$this->_data[$group][$key] += $n;
			return true;
		} elseif (($data = $this->_get($key, $group)) && ($data['expire'] === -1 || $data['expire'] >= time())) {
			return $this->_set($data['value'] + $n, $key, $group, $data['expire']);
		}
		return false;
	}


	public function decr($n, $key, $group = 'default') {
		++$this->statistics['decr'];
		if (($n = intval($n)) < 1) {
			return false;
		}
		if (isset($this->_data[$group][$key])) {
			$this->_data[$group][$key] -= $n;
			return true;
		} elseif (($data = $this->_get($key, $group)) && ($data['expire'] === -1 || $data['expire'] >= time())) {
			return $this->_set($data['value'] - $n, $key, $group, $data['expire']);
		}
		return false;
	}


	public function delete($key, $group = 'default', $ttl = 0) {
		++$this->statistics['delete'];
		if ($ttl > 0) {
			if (isset($this->_data[$group][$key])) {
				unset($this->_data[$group][$key]);
				$value = $this->_data[$group][$key];
				$ttl += time();
			} elseif (($data = $this->_get($key, $group)) && ($data['expire'] === -1 || $data['expire'] >= time())) {
				$value = $data['value'];
				$ttl += time();
				if ($ttl > $data['expire']) {
					$ttl = $data['expire'];
				}
			} else {
				return false;
			}
			return $this->_set($value, $key, $group, $ttl);
		}

		$isset = isset($this->_data[$group][$key]);
		unset($this->_data[$group][$key]);
		$file = is_file($file = $this->_file($key, $group)) && @unlink($file);
		return $file || $isset;
	}


	public function ttl($key, $group = 'default') {
		++$this->statistics['ttl'];
		if (isset($this->_data[$group][$key])) {
			return 0;
		}
		if ($data = $this->_get($key, $group)) {
			if ($data['expire'] === -1) {
				return $data['expire'];
			}
			return ($ttl = $data['expire'] - time()) >= 0 ? $ttl : false;
		}
		return false;
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
			if ($a = @unserialize(fread(fopen($file, 'rb'), filesize($file)))) {
				return $a;
			} else {
				@unlink($file);
			}
		}
		return false;
	}

	private function _set($value, $key, $group, $expire) {
		return (bool) fwrite(fopen($this->_file($key, $group), 'wb'), serialize(['value' => $value, 'expire' => $expire]));
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