<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-10-24 10:41:06
/*	Updated: UTC 2015-02-07 13:02:00
/*
/* ************************************************************************** */
namespace Loli\Cache;
use RedisException;

class_exists('Loli\Cache\Base') || exit;
class Redis extends Base{

	private $_data = [];

	private $ttl = [];

	private $_servers = [];

	public function __construct(array $args, $key = '') {
		$this->_key = $key;
		foreach ($args as $list => $servers) {
			$list = is_int($list) ? 'default' : $list;
			$servers = (array) $servers;
			foreach ($servers as $k => $v) {
				$v = is_array($v) ? $v : explode(':', $v, 3);
				$v[1] = empty($v[1]) ? 6379 : $v[1];
				$servers[$k] = $v;
			}
			$this->addServers($list, $servers);
		}
	}


	public function get($key, $list = 'default') {
		++$this->count['get'];
		if (!isset($this->_data[$list][$key])) {
			$this->_data[$list][$key] = false;
			try {
				if (($data = $this->_obj($key, $list)->get($this->_key($key, $list))) !== false) {
					$this->_data[$list][$key] = is_numeric($data) ? (int) $data : @unserialize($data);
				}
			} catch (RedisException $e) {
			}
		}
		if (is_object($this->_data[$list][$key])) {
			return clone $this->_data[$list][$key];
		}
		return $this->_data[$list][$key];
	}


	public function add($data, $key, $list = 'default', $ttl = 0) {
		++$this->count['add'];
		if ($data === null || $data === false || ($ttl = intval($ttl)) < -1 || (!$ttl && $this->get($key, $list, true) !== false)) {
			return false;
		}
		if (is_object($data)) {
			$data = clone $data;
		}

		if (!$ttl) {
			$this->_ttl[$list][$key] = 0;
			$this->_data[$list][$key] = $data;
			return true;
		}
		$r = false;
		try {
			$obj = $this->_obj($key, $list);
			if ($obj->setnx($k = $this->_key($key, $list), is_int($data) ? $data : serialize($data))) {
				$ttl == -1 || $obj->expire($k, $ttl);
				$this->_data[$list][$key] = $data;
				$this->_ttl[$list][$key] = null;
				$r = true;
			}
		} catch (RedisException $e) {
		}
		return $r;
	}


	public function set($data, $key, $list = 'default', $ttl = 0) {
		++$this->count['set'];
		if ($data === null || $data === false || ($ttl = intval($ttl)) < -1) {
			return false;
		}
		if (is_object($data)) {
			$data = clone $data;
		}
		if (!$ttl) {
			$this->_ttl[$list][$key] = 0;
			$this->_data[$list][$key] = $data;
			return true;
		}
		$r = false;
		try {
			$obj = $this->_obj($key, $list);
			if ($obj->set($k = $this->_key($key, $list), is_int($data) ? $data : serialize($data))) {
				$ttl == -1 || $obj->expire($k, $ttl);
				$this->_data[$list][$key] = $data;
				$this->_ttl[$list][$key] = null;
				$r = true;
			}
		} catch (RedisException $e) {

		}
		return $r;
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


		$this->_ttl[$list][$key] = $this->_data[$list][$key] = null;
		try {
			$obj = $this->_obj($key, $list);
			$r = $obj->exists($k = $this->_key($key, $list)) && $obj->incrby($k, $n);
		} catch (RedisException $e) {

		}
		return $r;
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


		$this->_ttl[$list][$key] = $this->_data[$list][$key] = null;
		try {
			$obj = $this->_obj($key, $list);
			$r = $obj->exists($k = $this->_key($key, $list)) && $obj->decrby($k, $n);
		} catch (RedisException $e) {
		}
		return $r;
	}


	public function delete($key, $list = 'default', $ttl = 0) {
		++$this->count['delete'];
		if ($ttl > 0) {
			$obj = $this->_obj($key, $list);
			if (($ttl = $obj->ttl($k = $this->_key($key, $list))) == -2 || $ttl === false) {
				return isset($this->_data[$list][$key]) && $this->_data[$list][$key] !== false;
			}
			if ($ttl == -1 || $ttl > $ttl) {
				return $obj->expire($k, $ttl);
			}
			return true;
		}
		if (isset($this->_ttl[$list][$key]) && $this->_ttl[$list][$key] === 0) {
			unset($this->_ttl[$list][$key], $this->_data[$list][$key]);
			return true;
		}

		unset($this->_ttl[$list][$key], $this->_data[$list][$key]);
		try {
			$r = $this->_obj($key, $list)->del($this->_key($key, $list));
		} catch (RedisException $e) {
		}
		return $r;
	}

	public function ttl($key, $list = 'default') {
		++$this->count['ttl'];
		if (!isset($this->_ttl[$list][$key])) {
			try {
				if (($ttl = $this->_obj($key, $list)->ttl($this->_key($key, $list))) == -2) {
					$this->_ttl[$list][$key] = false;
				} elseif ($ttl == -1 || $ttl === false) {
					$this->_ttl[$list][$key] = $ttl;
				} else {
					$this->_ttl[$list][$key] = $ttl + time();
				}
			} catch (RedisException $e) {
			}
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
			foreach($this->_servers as $k => $v) {
				foreach ($v as $kk => $vv) {
					try {
						$this->_obj($k, $kk)->flushall();
					} catch (RedisException $e) {

					}
				}
			}
		}
		return true;
	}

	public function addServers($list, array $a) {
		$this->_servers[$list] = array_merge(array_values($a), empty($this->_servers[$list]) ? [] : $this->_servers[$list]);
	}


	private function _obj($key, $list) {
		if (empty($this->_servers[$list])) {
			$list = 'default';
		}
		if (empty($this->_servers[$list][$key])) {
			$key = sprintf('%u', crc32($key)) % count($this->_servers[$list]);
		}
		if (empty($this->_servers[$list][$key]['obj'])) {
			try {
				$this->_servers[$list][$key]['obj'] = new \Redis;
				if ($this->_servers[$list][$key]['obj']->pconnect($this->_servers[$list][$key][0], $this->_servers[$list][$key][1])) {
					empty($this->_servers[$list][$key][2]) || $this->_servers[$list][$key]['obj']->auth($this->_servers[$list][$key][2]);
				}

			} catch (RedisException $e) {
				trigger_error('Redis '. $host .':' . $port . ' message: ' . $e->getMessage(), E_USER_WARNING);
			}
		}
		return $this->_servers[$list][$key]['obj'];
	}

	private function _key($key, $list) {
		return md5($key . $this->_key) . substr(md5($this->_key . $key), 12, 8) . $list;
	}
}