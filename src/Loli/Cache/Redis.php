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


class Redis extends Base{

	private $_data = [];

	private $_servers = [];

	public function get($key) {
		++$this->statistics['get'];
		if (isset($this->_data[$key])) {
			if (is_object($this->_data[$key])) {
				return clone $this->_data[$key];
			}
			return $this->_data[$key];
		}

		try {
			if (($value = $this->_obj($key)->get($this->_key($key))) !== false) {
				return is_numeric($value) ? (int) $value : @unserialize($value);
			}
		} catch (RedisException $e) {
			new Exception($e->getMessage(), $e->getCode());
		}
		return false;
	}


	public function add($value, $key, $ttl = 0) {
		++$this->statistics['add'];
		if ($value === NULL || $value === false) {
			return false;
		}

		if ($ttl) {
			try {
				$obj = $this->_obj($key);
				if ($obj->setnx($k = $this->_key($key), is_int($value) ? $value : serialize($value))) {
					$ttl == -1 || $obj->expire($k, $ttl);
				}
			} catch (RedisException $e) {
				new Exception($e->getMessage(), $e->getCode());
				return false;
			}
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
			try {
				$obj = $this->_obj($key);
				if ($obj->set($k = $this->_key($key), is_int($value) ? $value : serialize($value))) {
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
			$this->_data[$key] = $value;
		}
		return true;
	}

	public function incr($n, $key) {
		++$this->statistics['incr'];
		if (($n = intval($n)) < 1) {
			return false;
		}
		if (isset($this->_data[$key])) {
			$this->_data[$key] += $n;
			return $this->_data[$key];
		}

		try {
			$obj = $this->_obj($key);
			if ($obj->exists($k = $this->_key($key))) {
				return $obj->incrby($k, $n);
			}
		} catch (RedisException $e) {
			new Exception($e->getMessage(), $e->getCode());
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
		}

		try {
			$obj = $this->_obj($key);
			if ($obj->exists($k = $this->_key($key))) {
				return $obj->decrby($k, $n);
			}
		} catch (RedisException $e) {
			new Exception($e->getMessage(), $e->getCode());
		}
		return false;
	}


	public function delete($key, $ttl = 0) {
		++$this->statistics['delete'];
		if ($ttl > 0) {
			$obj = $this->_obj($key);
			try {
				if (isset($this->_data[$key])) {
					$value = $this->_data[$key];
					$obj->set($k = $this->_key($key), is_int($value) ? $value : serialize($value));
					$obj->expire($k, $ttl);
					return true;
				}
				if (($objTtl = $obj->ttl($k = $this->_key($key))) == -2 || $ttl === false) {
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

		$isset = isset($this->_data[$key]);
		unset($this->_data[$key]);
		try {
			$del = $this->_obj($key)->del($this->_key($key));
		} catch (RedisException $e) {
			new Exception($e->getMessage(), $e->getCode());
		}
		return $isset || !empty($del);
	}



	public function ttl($key) {
		++$this->statistics['ttl'];
		if (isset($this->_data[$key])) {
			return 0;
		}

		try {
			if (($ttl = $this->_obj($key)->ttl($this->_key($key))) == -2) {
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

	public function addServers(array $servers) {
		foreach ($servers as $key => $value) {
			$value = is_array($value) ? $value : explode(':', $value, 3);
			$value[1] = empty($value[1]) ? 6379 : $v[1];
			$servers[$key] = $value;
		}
		$this->_servers = array_merge($this->_servers, array_values($servers));
	}


	private function _obj($key) {
		if (empty($this->_servers[$key])) {
			$key = sprintf('%u', crc32($key)) % count($this->_servers);
		}
		if (empty($this->_servers[$key]['obj'])) {
			try {
				$this->_servers[$key]['obj'] = new \Redis;
				if ($this->_servers[$key]['obj']->pconnect($this->_servers[$key][0], $this->_servers[$key][1])) {
					empty($this->_servers[$key][2]) || $this->_servers[$key]['obj']->auth($this->_servers[$key][2]);
				}
			} catch (RedisException $e) {
				new Exception('Host: ' . $this->_servers[$key][0] .':' .  $this->_servers[$key][1]. '   '. $e->getMessage(), $e->getCode());
			}
		}
		return $this->_servers[$key]['obj'];
	}

	private function _key($key) {
		return md5($key . $this->key) . substr(md5($this->key . $key), 12, 8);
	}
}