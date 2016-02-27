<?php
namespace Loli\Cache;

use Redis;
use RedisException;

class RedisCacheItemPool extends AbstractCacheItemPool{

	protected $servers = [];

	private $links = [];

	private $count;

	public function __construct(array $args = []) {

		if (!isset($args['servers'])) {
			$args = ['servers' => $args];
		}

		foreach ($args['servers'] as &$server) {
			if (!$server) {
				$server = ['hostname' => 'localhost'];
			} elseif (!is_array($server)) {
				$server = ['hostname' => $server];
			} elseif (is_int(key($server))) {
				$server = ['hostname' => implode(':', $server)];
			} elseif (empty($server['hostname'])) {
				$server = ['hostname' => 'localhost'];
			}
		}
		unset($server);
		parent::__construct($args);
		$this->count = count($this->servers);
	}

	public function validateKey($key) {
		return true;
	}

	public function getItems(array $keys = []) {
		$items = [];
		foreach ($keys as $key) {
			if (isset($this->data[$key])) {
				$items[$key] = new CacheItem($this, $key, true, ...$this->data[$key]);
			} elseif ($data = $this->getData($key)) {
				$this->data[$key] = $data;
				$items[$key] = new CacheItem($this, $key, true, ...$data);
			} else {
				$items[$key] = new CacheItem($this, $key, false);
			}
		}
		return $items;
	}

	public function deleteItems(array $keys) {
		$count = 0;
		foreach($keys as $key) {
			try {
				if ($this->link($key)->delete($this->getKeyHash($key))) {
					++$count;
				}
			} catch (RedisException $e) {
				$this->logger && $this->logger->critical($e->getMessage() .' ('. $e->getCode() .')');
			}
			unset($this->data[$key]);
		}
		return $count !== 0;
	}

	public function hasItem($key) {
		if (isset($this->data[$key])) {
			return true;
		}
		if ($data = $this->getData($key)) {
			$this->data[$key] = $data;
			return true;
		}
		return false;
	}


	public function clear() {
		$this->data = [];
		foreach($this->servers as $key => $server) {
			try {
				$this->link($key)->flushall();
			} catch (RedisException $e) {
				$this->logger && $this->logger->critical($e->getMessage() .' ('. $e->getCode() .')');
			}
		}
		return true;
	}


	protected function write(array $items) {
		$count = 0;
		foreach ($items as $item) {
			$key = $item->getKey();
			$keyHash = $this->getKeyHash($key);
			$expires = $item->getExpiresAt();
			$link = $this->link($key);
			try {
				switch ($item->getMethod()) {
					case 'add':
						$value = $item->getNewValue();
						if (!$link->setNx($keyHash, is_int($value) ? $value : serialize($value))) {
							continue 2;
						}
						$expires && $link->expireAt($keyHash, $expires);
						break;
					case 'incr':
						if (($value = $link->incrBy($keyHash, $item->getNewValue())) === false) {
							continue 2;
						}
						$expires && $link->expireAt($keyHash, $expires);
						break;
					case 'decr':
						if (($value = $link->decrBy($keyHash, $item->getNewValue())) === false) {
							continue 2;
						}
						$expires && $link->expireAt($keyHash, $expires);
						break;
					case '':
						$value = $item->getRawValue();
						if (!$link->set($keyHash, is_int($value) ? $value : serialize($value))) {
							continue 2;
						}
						$expires && $link->expireAt($keyHash, $expires);
				}
				$item->set($value);
				$this->data[$key] = [$value, $expires];
				$item->setHit(true);
				++$count;
			} catch (RedisException $e) {
				$this->logger && $this->logger->critical($e->getMessage() .' ('. $e->getCode() .')');
			}
		}
		return $count;
	}


	private function getData($key) {
		$keyHash = $this->getKeyHash($key);
		$link = $this->link($key);
		try {
			if (($value = $link->get($keyHash)) === false) {
				return false;
			}
			if (($ttl = $link->ttl($keyHash)) == -2)  {
				return false;
			}
			$expires = $ttl == -1 ? null : $link->time()[0] + $ttl;
			return [is_numeric($value) ? (int) $value : @unserialize($value), $expires];
		} catch (RedisException $e) {
			$this->logger && $this->logger->critical($e->getMessage() .' ('. $e->getCode() .')');
		}
		return false;
	}

	private function link($key) {
		if (empty($this->servers[$key])) {
			$key = sprintf('%u', crc32($key)) % $this->count;
		}
		if (empty($this->links[$key])) {
			$server = $this->servers[$key];
			$server['hostname'] = explode(':', $server['hostname'], 2) + [1 => 6379];
			try {
				$this->links[$key] = new Redis;
				if ($this->links[$key]->pconnect($server['hostname'][0], $server['hostname'][1], 1)) {
					empty($server['auth']) || $this->links[$key]->auth($server['auth']);
				}
			} catch (RedisException $e) {
				$this->logger && $this->logger->critical('Redis cache server is unavailable ('. $e->getMessage() .') . ('. $server['hostname'][0] .':' . $server['hostname'][1] . ')');
			}
		}
		return $this->links[$key];
	}
}
