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

	private $_ttl = [];

	private $_mem = [];

	private $_d = false;

	// 从新尝试时间
	protected $retry = 15;

	function __construct(array $args, $key = '') {
		$this->_key = $key;
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
		++$this->count['get'];
		if (!isset($this->_data[$group][$key])) {
			$this->_data[$group][$key] = $this->_mem($group)->get($this->_key($key, $group));
		}
		if (is_object($this->_data[$group][$key])) {
			return clone $this->_data[$group][$key];
		}
		return $this->_data[$group][$key];
	}


	public function add($value, $key, $group = 'default', $ttl = 0) {
		++$this->count['add'];
		if ($value === NULL || $value === false || ($ttl = intval($ttl)) < -1 || (!$ttl && $this->get($key, $group) !== false)) {
			return false;
		}
		if (is_object($value)) {
			$value = clone $value;
		}
		if (!$ttl || ($this->_d ? $this->_mem($group)->add($k = $this->_key($key, $group), $value, $_ttl = $ttl == -1 ? 0 : $ttl) : $this->_mem($group)->add($k = $this->_key($key, $group), $value, MEMCACHE_COMPRESSED, $_ttl = $ttl == -1 ? 0 : $ttl))) {
			$this->_data[$group][$key] = $value;
			if ($ttl) {
				$this->_ttl[$group][$key] = $ttl = $ttl == -1 ? -1 : time() + $ttl;
				$this->_d ? $this->_mem($group)->set('ttl.'.$k,  $ttl, $_ttl) : $this->_mem($group)->set('ttl.'.$k, $ttl, MEMCACHE_COMPRESSED, $_ttl);
			} else {
				$this->_ttl[$group][$key] = 0;
			}
			return true;
		}
		return false;
	}


	public function set($value, $key, $group = 'default', $ttl = 0) {
		++$this->count['set'];
		if ($value === NULL || $value === false || ($ttl = intval($ttl)) < -1) {
			return false;
		}
		if (is_object($value)) {
			$value = clone $value;
		}
		if (!$ttl || ($this->_d ? $this->_mem($group)->set($k = $this->_key($key, $group), $value, $_ttl = $ttl == -1 ? 0 : $ttl) : $this->_mem($group)->set($k = $this->_key($key, $group), $value, MEMCACHE_COMPRESSED, $_ttl = $ttl == -1 ? 0 : $ttl))) {
			$this->_data[$group][$key] = $value;
			if ($ttl) {
				$this->_ttl[$group][$key] = $ttl = $ttl == -1 ? -1 : time() + $ttl;
				$this->_d ? $this->_mem($group)->set('ttl.'.$k,  $ttl, $_ttl) : $this->_mem($group)->set('ttl.'.$k, $ttl, MEMCACHE_COMPRESSED, $_ttl);
			} else {
				$this->_ttl[$group][$key] = 0;
			}
			return true;
		}
		return false;
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
		$this->_ttl[$group][$key] = $this->_data[$group][$key] = NULL;
		return $this->_mem($group)->increment($this->_key($key, $group), $n);
	}

	public function decr($n, $key, $group = 'default') {
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
		$this->_ttl[$group][$key] = $this->_data[$group][$key] = NULL;
		return $this->_mem($group)->decrement($this->_key($key, $group), $n);
	}


	public function delete($key, $group = 'default', $ttl = 0) {
		++$this->count['delete'];
		if ($ttl > 0) {
			$mem = $this->_mem($group);
			if (($value = $mem->get($k = $this->_key($key, $group))) === false) {
				return isset($this->_data[$group][$key]) && $this->_data[$group][$key] !== false;
			}
			if (($e = $this->_mem($group)->get('ttl.'. $this->_key($key, $group))) == -1 || $e === false || $e > (time() + $ttl)) {
				if (!($this->_d ? $mem->set($k, $value, $ttl) : $mem->set($k, $value, MEMCACHE_COMPRESSED, $ttl))){
					return false;
				}
				$this->_d ? $mem->set('ttl.'. $k,  $ttl, time() + $ttl) : $mem->set('ttl.'. $k, $ttl, MEMCACHE_COMPRESSED, time() + $ttl);
				return true;
			}
		}
		if (isset($this->_ttl[$group][$key]) && $this->_ttl[$group][$key] === 0) {
			unset($this->_ttl[$group][$key], $this->_data[$group][$key]);
			return true;
		}

		unset($this->_ttl[$group][$key], $this->_data[$group][$key]);
		$k = $this->_key($key, $group);
		$this->_mem($group)->delete('ttl.' . $k, 0);
		return $this->_mem($group)->delete($k, 0);
	}


	public function ttl($key, $group = 'default') {
		if (!isset($this->_ttl[$group][$key])) {
			$this->_ttl[$group][$key] = $this->_mem($group)->get('ttl.'. $this->_key($key, $group));
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
		$this->_ttl = $this->_data = [];
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
				$this->_mem[$group] =  new \Memcache;
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
		return md5($key . $this->_key) . substr(md5($this->_key . $key), 12, 8) . $group;
	}
}