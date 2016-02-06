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


class Memcache extends Base{


	private $_data = [];

	private $_link;

	private $_memcached = false;

	// 从新尝试时间
	protected $retry = 15;

	function __construct(array $args, $key = '') {
		$this->_memcached = class_exists('Memcached');
		if ($this->_memcached) {
			$this->_link = new Memcached;
			$this->_link->setOptions([Memcached::OPT_COMPRESSION => true, Memcached::OPT_POLL_TIMEOUT => 1000, Memcached::OPT_RETRY_TIMEOUT => $this->retry]);
		} else {
			$this->_link = new \Memcache;
		}
		parent::__construct($args, $key);
	}


	public function get($key) {
		++$this->statistics['get'];
		if (isset($this->_data[$key])) {
			if (is_object($this->_data[$key])) {
				return clone $this->_data[$key];
			}
			return $this->_data[$key];
		}
		return $this->_link->get($this->_key($key));
	}


	public function add($value, $key, $ttl = 0) {
		++$this->statistics['add'];
		if ($value === NULL || $value === false) {
			return false;
		}

		if ($ttl) {
			$k = $this->_key($key);
			$ttl = $ttl == -1 ? 0 :  time() + $ttl;
			if ($this->_memcached) {
				if (!$this->_link->add($k, $value, $ttl)) {
					return false;
				}
				$this->_link->set('ttl.'. $k, $ttl ? $ttl : -1, $ttl);
			} else {
				if (!$this->_link->add($k, $value, MEMCACHE_COMPRESSED, $ttl)) {
					return false;
				}
				$this->_link->set('ttl.'. $k, $ttl ? $ttl : -1, MEMCACHE_COMPRESSED, $ttl);
			}
			unset($this->_data[$key]);
		} else {
			if (isset($this->_data[$key])) {
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
			$k = $this->_key($key);
			$ttl = $ttl == -1 ? 0 : time() + $ttl;
			if ($this->_memcached) {
				$this->_link->set($k, $value, $ttl);
				$this->_link->set('ttl.'. $k, $ttl ? $ttl : -1, $ttl);
			} else {
				$this->_link->add($k, $value, MEMCACHE_COMPRESSED, $ttl);
				$this->_link->set('ttl.'. $k, $ttl ? $ttl : -1, MEMCACHE_COMPRESSED, $ttl);
			}
		} else {
			if (is_object($value)) {
				$value = clone $value;
			}
			$this->_data[$key] = $value;
		}
		return true;
	}

	public function incr($n, $key) {
		++$this->statistics['incr'];
		if (($n = intval($n)) < 1) {
			return false;
		};
		if (isset($this->_data[$key])) {
			$this->_data[$key] += $n;
			return true;
		}
		return $this->_link->increment($this->_key($key), $n);
	}

	public function decr($n, $key) {
		++$this->statistics['incr'];
		if (($n = intval($n)) < 1) {
			return false;
		}
		if (isset($this->_data[$key])) {
			$this->_data[$key] += $n;
			return true;
		}
		return $this->_link->decrement($this->_key($key), $n);
	}


	public function delete($key, $ttl = 0) {
		++$this->statistics['delete'];
		$k = $this->_key($key);
		if ($ttl > 0) {
			if (isset($this->_data[$key])) {
				$ttl = time() + $ttl;
				$value = $this->_data[$key];
				unset($this->_data[$key]);
			} elseif (($value = $this->_link->get($k)) !== false) {
				if (!$time = $this->_link->get('ttl.'. $k)) {
					$ttl = time() + $ttl;
				} elseif ($time < ($ttl = time() + $ttl)) {
					$ttl = $time;
				}
			} else {
				return false;
			}

			if ($this->_memcached) {
				$this->_link->set($k, $value, $ttl);
				$this->_link->set('ttl.'. $k, $ttl, $ttl);
			} else {
				$this->_link->set($k, $value, MEMCACHE_COMPRESSED, $ttl);
				$this->_link->set('ttl.'. $k, $ttl, MEMCACHE_COMPRESSED, $ttl);
			}
			return true;
		}

		$isset = isset($this->_data[$key]);
		$delete = $this->_link->delete($k, 0);
		$this->_link->delete('ttl.' . $k, 0);
		unset($this->_data[$key]);
		return $isset || $delete;
	}


	public function ttl($key) {
		if (isset($this->_data[$key])) {
			return 0;
		}
		if ($ttl = $this->_link->get('ttl.'. $this->_key($key))) {
			$ttl -= time();
			return $ttl >= 0 ? $ttl : false;
		}
		return false;
	}







	public function flush($mem = false) {
		$this->_data = [];
		if (!$mem) {
			$this->_link->flush();
		}
		return true;
	}

	public function addServers(array $servers) {
		foreach ($servers as $key => $value) {
			$value = is_array($value) ? $value : explode(':', $value, 2);
			$value[1] = empty($value[1]) ? '11211' : $value[1];
			$servers[$key] = $value;
		}

		if ($this->_memcached) {
			$this->_link->addServers($servers);
		} else {
			foreach ($servers as $key => $value) {
				$this->_link->addServer($value[0], empty($value[1]) ? 11211: $value[1], true, 1, 1, $this->retry, true, [$this, 'failure']);
			}
		}
		return true;
	}

	public function failure($host, $port) {
		new Exception('Memcache '. $host .':' . $port);
	}


	private function _key($key) {
		return md5($key . $this->key) . substr(md5($this->key . $key), 12, 8);
	}
}