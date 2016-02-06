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

class File extends Base{
	private $_data = [];

	private $_dir = './';

	public function get($key) {
		++$this->statistics['get'];
		if (isset($this->_data[$key])) {
			if (is_object($this->_data[$key])) {
				return clone $this->_data[$key];
			}
			return $this->_data[$key];
		}

		if (($data = $this->_get($key)) && ($data['expire'] === -1 || $data['expire'] >= time())) {
			return $data['value'];
		}
		return false;
	}

	public function add($value, $key, $ttl = 0) {
		++$this->statistics['add'];
		if ($value === NULL || $value === false) {
			return false;
		}
		if ($ttl) {
			if (($data = $this->_get($key)) && ($data['expire'] === -1 || $data['expire'] >= time())) {
				return false;
			}
			unset($this->_data[$key]);
			$this->_set($value, $key, $ttl == -1 ? -1 : time() + $ttl);
		} else {
			if (!isset($this->_data[$key])) {
				return false;
			}
			if (is_object($value)) {
				$value = clone $value;
			}
			$this->_data[$key] = $value;
		}
		return true;
	}

	public function set($value, $key, $ttl = 0) {
		++$this->statistics['set'];
		if ($value === NULL || $value === false) {
			return false;
		}
		if ($ttl) {
			unset($this->_data[$key]);
			$this->delete($key);
			return $this->_set($value, $key, $ttl == -1 ? -1 : time() + $ttl);
		} else {
			if (is_object($value)) {
				$value = clone $value;
			}
			$this->_data[$key] = $value;
			return true;
		}
		return false;
	}


	public function incr($n, $key) {
		++$this->statistics['incr'];
		if (($n = intval($n)) < 1) {
			return false;
		}
		if (isset($this->_data[$key])) {
			$this->_data[$key] += $n;
			return $this->_data[$key];
		} elseif (($data = $this->_get($key)) && ($data['expire'] === -1 || $data['expire'] >= time()) && $this->_set($value = $data['value'] + $n, $key, $data['expire'])) {
			return $value;
		}
		return false;
	}


	public function decr($n, $key) {
		++$this->statistics['decr'];
		if (($n = intval($n)) < 1) {
			return false;
		}
		if (isset($this->_data[$key])) {
			$this->_data[$key] -= $n;
			return $this->_data[$key];
		} elseif (($data = $this->_get($key)) && ($data['expire'] === -1 || $data['expire'] >= time()) && $this->_set($value = $data['value'] - $n, $key, $data['expire'])) {
			return $value;
		}
		return false;
	}


	public function delete($key, $ttl = 0) {
		++$this->statistics['delete'];
		if ($ttl > 0) {
			if (isset($this->_data[$key])) {
				unset($this->_data[$key]);
				$value = $this->_data[$key];
				$ttl += time();
			} elseif (($data = $this->_get($key)) && ($data['expire'] === -1 || $data['expire'] >= time())) {
				$value = $data['value'];
				$ttl += time();
				if ($ttl > $data['expire']) {
					$ttl = $data['expire'];
				}
			} else {
				return false;
			}
			return $this->_set($value, $key, $ttl);
		}

		$isset = isset($this->_data[$key]);
		unset($this->_data[$key]);
		$file = is_file($file = $this->_file($key)) && @unlink($file);
		return $file || $isset;
	}


	public function ttl($key) {
		++$this->statistics['ttl'];
		if (isset($this->_data[$key])) {
			return 0;
		}
		if ($data = $this->_get($key)) {
			if ($data['expire'] === -1) {
				return $data['expire'];
			}
			return ($ttl = $data['expire'] - time()) >= 0 ? $ttl : false;
		}
		return false;
	}

	public function flush($mem = false) {
		$this->_ttl = $this->_data = [];
		return $mem || $this->_undir($this->_dir);
	}

	public function addServers(array $servers) {
		if (!empty($servers['dir'])) {
			$this->_dir = $servers['dir'];
		}
		return true;
	}



	private function _get($key) {
		if (is_file($file = $this->_file($key))) {
			if ($a = @unserialize(fread(fopen($file, 'rb'), filesize($file)))) {
				return $a;
			} else {
				@unlink($file);
			}
		}
		return false;
	}

	private function _set($value, $key, $expire) {
		return (bool) fwrite(fopen($this->_file($key), 'wb'), serialize(['value' => $value, 'expire' => $expire]));
	}


	private function _file($key) {
		return $this->_dir . '/' . md5($key . $this->key) . substr(md5($this->key . $key), 12, 8);
	}

	private function _undir($dir) {
		if (!is_dir($dir)) {
			return false;
		}
		$opendir = opendir($dir);
		while ($name = readdir($opendir)) {
			if (in_array($name, ['.', '..'], true)) {
				continue;
			}
			$path = $dir . '/' . $name;
			is_dir($path) ? $this->_undir($path) : @unlink($path);
		}
		closedir($opendir);
		return true;
	}
}