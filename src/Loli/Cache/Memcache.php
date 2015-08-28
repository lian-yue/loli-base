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
/*	Created: UTC 2014-02-17 08:46:33
/*	Updated: UTC 2015-04-07 14:31:59
/*
/* ************************************************************************** */
namespace Loli\Cache;
use Memcached;
class_exists('Loli\Cache\Base') || exit;
class Memcache extends Base{


	private $_data = [];

	private $_mem = [];

	private $_d = false;

	// 从新尝试时间
	protected $retry = 15;

	function __construct(array $args, $key = '') {
		$this->key = $key;
		$this->_d = class_exists('Memcached');
		foreach ($args as $group => $servers) {
			$group = is_int($group) ? 'default' : $group;
			$servers = (array) $servers;
			foreach ($servers as $k => $v) {
				$v = is_array($v) ? $v : explode(':', $v, 2);
				$v[1] = empty($v[1]) ? '11211' : $v[1];
				$servers[$k] = $v;
			}
			$this->addServers($group, $servers);
		}
	}



	public function get($key, $group = 'default') {
		++$this->statistics['get'];
		if (isset($this->_data[$group][$key])) {
			if (is_object($this->_data[$group][$key])) {
				return clone $this->_data[$group][$key];
			}
			return $this->_data[$group][$key];
		}
		return $this->_mem($group)->get($this->_key($key, $group));
	}


	public function add($value, $key, $group = 'default', $ttl = 0) {
		++$this->statistics['add'];
		if ($value === NULL || $value === false) {
			return false;
		}

		if ($ttl) {
			$k = $this->_key($key, $group);
			$ttl = $ttl == -1 ? 0 :  time() + $ttl;
			if ($this->_d) {
				if (!$this->_mem($group)->add($k, $value, $ttl)) {
					return false;
				}
				$this->_mem($group)->set('ttl.'. $k, $ttl ? $ttl : -1, $ttl);
			} else {
				if (!$this->_mem($group)->add($k, $value, MEMCACHE_COMPRESSED, $ttl)) {
					return false;
				}
				$this->_mem($group)->set('ttl.'. $k, $ttl ? $ttl : -1, MEMCACHE_COMPRESSED, $ttl);
			}
			unset($this->_data[$group][$key]);
		} else {
			if (isset($this->_data[$group][$key])) {
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
		++$this->statistics['add'];
		if ($value === NULL || $value === false) {
			return false;
		}

		if ($ttl) {
			unset($this->_data[$group][$key]);
			$k = $this->_key($key, $group);
			$ttl = $ttl == -1 ? 0 : time() + $ttl;
			if ($this->_d) {
				$this->_mem($group)->set($k, $value, $ttl);
				$this->_mem($group)->set('ttl.'. $k, $ttl ? $ttl : -1, $ttl);
			} else {
				$this->_mem($group)->add($k, $value, MEMCACHE_COMPRESSED, $ttl);
				$this->_mem($group)->set('ttl.'. $k, $ttl ? $ttl : -1, MEMCACHE_COMPRESSED, $ttl);
			}
		} else {
			if (is_object($value)) {
				$value = clone $value;
			}
			$this->_data[$group][$key] = $value;
		}
		return false;
	}

	public function incr($n, $key, $group = 'default') {
		++$this->statistics['incr'];
		if (($n = intval($n)) < 1) {
			return false;
		};
		if (isset($this->_data[$group][$key])) {
			$this->_data[$group][$key] += $n;
			return true;
		}
		return $this->_mem($group)->increment($this->_key($key, $group), $n);
	}

	public function decr($n, $key, $group = 'default') {
		++$this->statistics['incr'];
		if (($n = intval($n)) < 1) {
			return false;
		}
		if (isset($this->_data[$group][$key])) {
			$this->_data[$group][$key] += $n;
			return true;
		}
		return $this->_mem($group)->decrement($this->_key($key, $group), $n);
	}


	public function delete($key, $group = 'default', $ttl = 0) {
		++$this->statistics['delete'];
		$k = $this->_key($key, $group);
		if ($ttl > 0) {
			$mem = $this->_mem($group);
			if (isset($this->_data[$group][$key])) {
				$ttl = time() + $ttl;
				$value = $this->_data[$group][$key];
				unset($this->_data[$group][$key]);
			} elseif (($value = $mem->get($k)) !== false) {
				if (!$time = $mem->get('ttl.'. $k)) {
					$ttl = time() + $ttl;
				} elseif ($time < ($ttl = time() + $ttl)) {
					$ttl = $time;
				}
			} else {
				return false;
			}

			if ($this->_d) {
				$mem->set($k, $value, $ttl);
				$mem->set('ttl.'. $k, $ttl, $ttl);
			} else {
				$mem->set($k, $value, MEMCACHE_COMPRESSED, $ttl);
				$mem->set('ttl.'. $k, $ttl, MEMCACHE_COMPRESSED, $ttl);
			}
			return true;
		}

		$isset = isset($this->_data[$group][$key]);
		$delete = $this->_mem($group)->delete($k, 0);
		$this->_mem($group)->delete('ttl.' . $k, 0);
		unset($this->_data[$group][$key]);
		return $isset || $delete;
	}


	public function ttl($key, $group = 'default') {
		if (isset($this->_data[$group][$key])) {
			return 0;
		}
		if ($ttl = $this->_mem($group)->get('ttl.'. $this->_key($key, $group))) {
			$ttl -= time();
			return $ttl >= 0 ? $ttl : false;
		}
		return false;
	}







	public function flush($mem = false) {
		$this->_data = [];
		if (!$mem) {
			foreach($this->_mem as $v) {
				$v->flush();
			}
		}
		return true;
	}

	public function addServers($group, array $servers) {
		if ($this->_d) {
			if (empty($this->_mem[$group])) {
				$this->_mem[$group] = new Memcached;
				$this->_mem[$group]->setOptions([Memcached::OPT_COMPRESSION => true, Memcached::OPT_POLL_TIMEOUT => 1000, Memcached::OPT_RETRY_TIMEOUT => $this->retry]);
			}
			$this->_mem[$group]->addServers($servers);
		} else {
			if (empty($this->_mem[$group])) {
				$this->_mem[$group] = new \Memcache;
			}
			foreach ($servers as $k => $v) {
				$this->_mem[$group]->addServer($v[0], empty($v[1]) ? 11211: $v[1], true, 1, 1, $this->retry, true, [$this, 'failure']);
			}
		}
		return true;
	}

	public function failure($host, $port) {
		new Exception('Memcache '. $host .':' . $port);
	}


	private function _mem($group) {
		return empty($this->_mem[$group]) ? $this->_mem['default'] : $this->_mem[$group];
	}

	private function _key($key, $group) {
		return md5($key . $this->key) . substr(md5($this->key . $key), 12, 8) . $group;
	}
}