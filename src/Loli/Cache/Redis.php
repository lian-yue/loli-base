<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-27 09:13:47
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
/*	Created: UTC 2014-10-24 10:41:06
/*	Updated: UTC 2015-04-07 14:32:33
/*
/* ************************************************************************** */
namespace Loli\Cache;
use RedisException;
class_exists('Loli\Cache\Base') || exit;
class Redis extends Base{

	private $_data = [];

	private $_servers = [];

	public function __construct(array $args, $key = '') {
		$this->_key = $key;
		foreach ($args as $group => $servers) {
			$group = is_int($group) ? 'default' : $group;
			$servers = (array) $servers;
			foreach ($servers as $k => $v) {
				$v = is_array($v) ? $v : explode(':', $v, 3);
				$v[1] = empty($v[1]) ? 6379 : $v[1];
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

		try {
			if (($value = $this->_obj($key, $group)->get($this->_key($key, $group))) !== false) {
				return is_numeric($value) ? (int) $value : @unserialize($value);
			}
		} catch (RedisException $e) {
			new Exception($e->getMessage(), $e->getCode());
		}
		return false;
	}


	public function add($value, $key, $group = 'default', $ttl = 0) {
		++$this->statistics['add'];
		if ($value === NULL || $value === false) {
			return false;
		}

		if ($ttl) {
			try {
				$obj = $this->_obj($key, $group);
				if ($obj->setnx($k = $this->_key($key, $group), is_int($value) ? $value : serialize($value))) {
					$ttl == -1 || $obj->expire($k, $ttl);
				}
			} catch (RedisException $e) {
				new Exception($e->getMessage(), $e->getCode());
				return false;
			}
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
		++$this->statistics['set'];
		if ($value === NULL || $value === false) {
			return false;
		}

		if ($ttl) {
			try {
				$obj = $this->_obj($key, $group);
				if ($obj->set($k = $this->_key($key, $group), is_int($value) ? $value : serialize($value))) {
					$ttl == -1 || $obj->expire($k, $ttl);
				}
			} catch (RedisException $e) {
				new Exception($e->getMessage(), $e->getCode());
				return false;
			}
		} else {
			if (is_object($value)) {
				$value = clone $value;
			}
			$this->_data[$group][$key] = $value;
		}
		return true;
	}

	public function incr($n, $key, $group = 'default') {
		++$this->statistics['incr'];
		if (($n = intval($n)) < 1) {
			return false;
		}
		if (isset($this->_data[$group][$key])) {
			$this->_data[$group][$key] += $n;
			return true;
		}

		try {
			$obj = $this->_obj($key, $group);
			return $obj->exists($k = $this->_key($key, $group)) && $obj->incrby($k, $n);
		} catch (RedisException $e) {
			new Exception($e->getMessage(), $e->getCode());
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
		}

		try {
			$obj = $this->_obj($key, $group);
			return $obj->exists($k = $this->_key($key, $group)) && $obj->decrby($k, $n);
		} catch (RedisException $e) {
			new Exception($e->getMessage(), $e->getCode());
		}
		return false;
	}


	public function delete($key, $group = 'default', $ttl = 0) {
		++$this->statistics['delete'];
		if ($ttl > 0) {
			$obj = $this->_obj($key, $group);
			try {
				if (isset($this->_data[$group][$key])) {
					$value = $this->_data[$group][$key];
					$obj->set($k = $this->_key($key, $group), is_int($value) ? $value : serialize($value));
					$obj->expire($k, $ttl);
					return true;
				}
				if (($objTtl = $obj->ttl($k = $this->_key($key, $group))) == -2 || $ttl === false) {
					return false;
				}
				if ($objTtl == -1 || $objTtl > $ttl) {
					return $obj->expire($k, $ttl);
				}
			} catch (RedisException $e) {
				new Exception($e->getMessage(), $e->getCode());
			}
			return true;
		}

		$isset = isset($this->_data[$group][$key]);
		unset($this->_data[$group][$key]);
		try {
			$del = $this->_obj($key, $group)->del($this->_key($key, $group));
		} catch (RedisException $e) {
			new Exception($e->getMessage(), $e->getCode());
		}
		return $isset || !empty($del);
	}

	public function ttl($key, $group = 'default') {
		++$this->statistics['ttl'];
		if (isset($this->_data[$group][$key])) {
			return 0;
		}

		try {
			if (($ttl = $this->_obj($key, $group)->ttl($this->_key($key, $group))) == -2) {
				return false;
			} else {
				return $ttl;
			}
		} catch (RedisException $e) {
			new Exception($e->getMessage(), $e->getCode());
		}
		return false;
	}


	public function flush($mem = false) {
		$this->_ttl = $this->_data = [];
		if (!$mem) {
			foreach($this->_servers as $k => $v) {
				foreach ($v as $kk => $vv) {
					try {
						$this->_obj($k, $kk)->flushall();
					} catch (RedisException $e) {
						new Exception($e->getMessage(), $e->getCode());
					}
				}
			}
		}
		return true;
	}

	public function addServers($group, array $servers) {
		$this->_servers[$group] = array_merge(array_values($servers), empty($this->_servers[$group]) ? [] : $this->_servers[$group]);
	}


	private function _obj($key, $group) {
		if (empty($this->_servers[$group])) {
			$group = 'default';
		}
		if (empty($this->_servers[$group][$key])) {
			$key = sprintf('%u', crc32($key)) % count($this->_servers[$group]);
		}
		if (empty($this->_servers[$group][$key]['obj'])) {
			try {
				$this->_servers[$group][$key]['obj'] = new \Redis;
				if ($this->_servers[$group][$key]['obj']->pconnect($this->_servers[$group][$key][0], $this->_servers[$group][$key][1])) {
					empty($this->_servers[$group][$key][2]) || $this->_servers[$group][$key]['obj']->auth($this->_servers[$group][$key][2]);
				}
			} catch (RedisException $e) {
				new Exception('Host: ' . $this->_servers[$group][$key][0] .':' .  $this->_servers[$group][$key][1]. '   '. $e->getMessage(), $e->getCode());
			}
		}
		return $this->_servers[$group][$key]['obj'];
	}

	private function _key($key, $group) {
		return md5($key . $this->_key) . substr(md5($this->_key . $key), 12, 8) . $group;
	}
}