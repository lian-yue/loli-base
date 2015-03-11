<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-02-17 08:46:33
/*	Updated: UTC 2015-02-25 13:57:15
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
		foreach ($args as $list => $servers) {
			$list = is_int($list) ? 'default' : $list;
			$servers = (array) $servers;
			foreach ($servers as $k => $v) {
				$v = is_array($v) ? $v : explode(':', $v, 2);
				$v[1] = empty($v[1]) ? '11211' : $v[1];
				$servers[$k] = $v;
			}
			$this->addServers($list, $servers);
		}
	}



	public function get($key, $list = 'default') {
		++$this->count['get'];
		if (!isset($this->_data[$list][$key])) {
			$this->_data[$list][$key] = $this->_mem($list)->get($this->_key($key, $list));
		}
		if (is_object($this->_data[$list][$key])) {
			return clone $this->_data[$list][$key];
		}
		return $this->_data[$list][$key];
	}


	public function add($data, $key, $list = 'default', $ttl = 0) {
		++$this->count['add'];
		if ($data === NULL || $data === false || ($ttl = intval($ttl)) < -1 || (!$ttl && $this->get($key, $list) !== false)) {
			return false;
		}
		if (is_object($data)) {
			$data = clone $data;
		}
		if (!$ttl || ($this->_d ? $this->_mem($list)->add($k = $this->_key($key, $list), $data, $_ttl = $ttl == -1 ? 0 : $ttl) : $this->_mem($list)->add($k = $this->_key($key, $list), $data, MEMCACHE_COMPRESSED, $_ttl = $ttl == -1 ? 0 : $ttl))) {
			$this->_data[$list][$key] = $data;
			if ($ttl) {
				$this->_ttl[$list][$key] = $ttl = $ttl == -1 ? -1 : time() + $ttl;
				$this->_d ? $this->_mem($list)->set('ttl.'.$k,  $ttl, $_ttl) : $this->_mem($list)->set('ttl.'.$k, $ttl, MEMCACHE_COMPRESSED, $_ttl);
			} else {
				$this->_ttl[$list][$key] = 0;
			}
			return true;
		}
		return false;
	}


	public function set($data, $key, $list = 'default', $ttl = 0) {
		++$this->count['set'];
		if ($data === NULL || $data === false || ($ttl = intval($ttl)) < -1) {
			return false;
		}
		if (is_object($data)) {
			$data = clone $data;
		}
		if (!$ttl || ($this->_d ? $this->_mem($list)->set($k = $this->_key($key, $list), $data, $_ttl = $ttl == -1 ? 0 : $ttl) : $this->_mem($list)->set($k = $this->_key($key, $list), $data, MEMCACHE_COMPRESSED, $_ttl = $ttl == -1 ? 0 : $ttl))) {
			$this->_data[$list][$key] = $data;
			if ($ttl) {
				$this->_ttl[$list][$key] = $ttl = $ttl == -1 ? -1 : time() + $ttl;
				$this->_d ? $this->_mem($list)->set('ttl.'.$k,  $ttl, $_ttl) : $this->_mem($list)->set('ttl.'.$k, $ttl, MEMCACHE_COMPRESSED, $_ttl);
			} else {
				$this->_ttl[$list][$key] = 0;
			}
			return true;
		}
		return false;
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
		$this->_ttl[$list][$key] = $this->_data[$list][$key] = NULL;
		return $this->_mem($list)->increment($this->_key($key, $list), $n);
	}

	public function decr($n, $key, $list = 'default') {
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
		$this->_ttl[$list][$key] = $this->_data[$list][$key] = NULL;
		return $this->_mem($list)->decrement($this->_key($key, $list), $n);
	}


	public function delete($key, $list = 'default', $ttl = 0) {
		++$this->count['delete'];
		if ($ttl > 0) {
			$mem = $this->_mem($list);
			if (($data = $mem->get($k = $this->_key($key, $list))) === false) {
				return isset($this->_data[$list][$key]) && $this->_data[$list][$key] !== false;
			}
			if (($e = $this->_mem($list)->get('ttl.'. $this->_key($key, $list))) == -1 || $e === false || $e > (time() + $ttl)) {
				if (!($this->_d ? $mem->set($k, $data, $ttl) : $mem->set($k, $data, MEMCACHE_COMPRESSED, $ttl))){
					return false;
				}
				$this->_d ? $mem->set('ttl.'. $k,  $ttl, time() + $ttl) : $mem->set('ttl.'. $k, $ttl, MEMCACHE_COMPRESSED, time() + $ttl);
				return true;
			}
		}
		if (isset($this->_ttl[$list][$key]) && $this->_ttl[$list][$key] === 0) {
			unset($this->_ttl[$list][$key], $this->_data[$list][$key]);
			return true;
		}

		unset($this->_ttl[$list][$key], $this->_data[$list][$key]);
		$k = $this->_key($key, $list);
		$this->_mem($list)->delete('ttl.' . $k, 0);
		return $this->_mem($list)->delete($k, 0);
	}


	public function ttl($key, $list = 'default') {
		if (!isset($this->_ttl[$list][$key])) {
			$this->_ttl[$list][$key] = $this->_mem($list)->get('ttl.'. $this->_key($key, $list));
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
		$this->_ttl = $this->_data = [];
		if (!$mem) {
			foreach($this->_mem as $v) {
				$v->flush();
			}
		}
		return true;
	}

	public function addServers($list, array $a) {
		if ($this->_d) {
			if (empty($this->_mem[$list])) {
				$this->_mem[$list] = new Memcached;
				$this->_mem[$list]->setOptions([Memcached::OPT_COMPRESSION => true, Memcached::OPT_POLL_TIMEOUT => 1000, Memcached::OPT_RETRY_TIMEOUT => $this->retry]);
			}
			$this->_mem[$list]->addServers($v[0], $a);
		} else {
			if (empty($this->_mem[$list])) {
				$this->_mem[$list] =  new \Memcache;
			}
			foreach ($a as $k => $v) {
				$this->_mem[$list]->addServer($v[0], empty($v[1]) ? 11211: $v[1], true, 1, 1, $this->retry, true, [$this, 'failure']);
			}
		}
		return true;
	}

	public function failure($host, $port) {
		$this->addMessage('Memcache '. $host .':' . $port);
	}


	private function _mem($list) {
		return empty($this->_mem[$list]) ? $this->_mem['default'] : $this->_mem[$list];
	}

	private function _key($key, $list) {
		return md5($key . $this->_key) . substr(md5($this->_key . $key), 12, 8) . $list;
	}
}