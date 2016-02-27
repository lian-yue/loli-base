<?php
namespace Loli\Cache;
use Memcache;
use Memcached;


class MemcacheCacheItemPool extends AbstractCacheItemPool{

	protected $retry = 15;

	protected $servers = [];

	protected $username;

	protected $passowrd;

	private $link;

	private $memcached = false;

	const EXPIRES = 'expires';

	public function __construct(array $args = []) {
		$this->memcached = class_exists('Memcached');

		if (!isset($args['servers'])) {
			$args = ['servers' => $args];
		}

		$i = 0;
		foreach ($args['servers'] as &$server) {
			if ($i === 0 && isset($server['username']) && !isset($args['username'])) {
				$args['username'] = $server['username'];
				if (isset($server['password'])) {
					$args['password'] = $server['password'];
				}
			}

			if (!$server) {
				$server = ['hostname' => 'localhost'];
			} elseif (!is_array($server)) {
				$server = ['hostname' => $server];
			} elseif (is_int(key($server))) {
				$server = ['hostname' => implode(':', $server)];
			} elseif (empty($server['hostname'])) {
				$server = ['hostname' => 'localhost'];
			}
			++$i;
		}
		unset($server);


		parent::__construct($args);

		if ($this->memcached) {
			$this->link = new \Memcached;
			$this->link->setOptions([\Memcached::OPT_COMPRESSION => true, \Memcached::OPT_POLL_TIMEOUT => 1000, \Memcached::OPT_RETRY_TIMEOUT => $this->retry]);

			$servers = [];
			foreach ($this->servers as $server) {
				$servers[] = explode(':', $server['hostname'], 2) + [1 =>'11211'];
			}
			$this->link->addServers($servers);
			if ($this->username) {
				$this->link->setSaslAuthData($this->username, $this->password);
			}
		} else {
			$this->link = new \Memcache;
			foreach ($this->servers as $server) {
				$server = explode(':', $server['hostname'], 2) + [1 =>'11211'];
				$this->link->addServer($server[0], $server[1], true, 1, 1, $this->retry, true, [$this, 'failure']);
			}
		}
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

		$deleteKeys = [];
		foreach($keys as $key) {
			$keyHash = $this->getKeyHash($key);
			$deleteKeys[] = $keyHash;
			$deleteKeys[] = self::EXPIRES . $keyHash;
			if ($this->hasItem($key)) {
				++$count;
			}
			unset($this->data[$key]);
		}
		if ($this->memcached) {
			$this->link->deleteMulti($deleteKeys);
		} else {
			foreach ($deleteKeys as $key) {
				$this->link->delete($key);
			}
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
		$this->link->flush();
		return true;
	}

	protected function write(array $items) {
		$count = 0;
		foreach ($items as $item) {
			$key = $item->getKey();
			$keyHash = $this->getKeyHash($key);
			$expires = $item->getExpiresAt();
			switch ($item->getMethod()) {
				case 'add':
					$value = $item->getNewValue();
					if ($this->memcached) {
						if (!$this->link->add($keyHash, $value, (int) $expires)) {
							continue 2;
						}
						$this->link->set(self::EXPIRES. $keyHash, $expires, $expires ? : 0);
					} else {
						if (!$this->link->add($keyHash, $value, MEMCACHE_COMPRESSED, (int) $expires)) {
							continue 2;
						}
						$this->link->set(self::EXPIRES.$keyHash, $expires, MEMCACHE_COMPRESSED, $expires ? : 0);
					}
					break;
				case 'incr':
					if (($value = $this->link->increment($keyHash, $item->getNewValue())) === false) {
						continue 2;
					}
					if ($this->memached) {
						if ($expires !== $this->link->set(self::EXPIRES. $keyHash)) {
							$this->touch($keyHash, (int) $expires);
							$this->link->set(self::EXPIRES. $keyHash, $expires, $expires ? : 0);
						}
					}
					break;
				case 'decr':
					if (($value = $this->link->decrement($keyHash, $item->getNewValue())) === false) {
						continue 2;
					}
					if ($this->memached) {
						if ($expires !== $this->link->set(self::EXPIRES. $keyHash)) {
							$this->touch($keyHash, (int) $expires);
							$this->link->set(self::EXPIRES. $keyHash, $expires, $expires ? : 0);
						}
					}
					break;
				case '':
					$value = $item->getRawValue();
					if ($this->memcached) {
						if (!$this->link->set($keyHash, $value, (int) $expires)) {
							continue 2;
						}
						$this->link->set(self::EXPIRES. $keyHash, $expires, $expires ? : 0);
					} else {
						if (!$this->link->set($keyHash, $value, MEMCACHE_COMPRESSED, (int) $expires)) {
							continue 2;
						}
						$this->link->set(self::EXPIRES.$keyHash, $expires, MEMCACHE_COMPRESSED, $expires ? : 0);
					}
			}
			$item->set($value);
			$this->data[$key] = [$value, $expires];
			$item->setHit(true);
			++$count;
		}
		return $count;
	}

	private function getData($key) {
		$keyHash = $this->getKeyHash($key);
		$value = $this->link->get($keyHash);
		if ($value !== false)  {
			return [$value, $this->link->get(self::EXPIRES . $keyHash) ? : null];
		}
		if ($this->memcached) {
			if (($resultCode = $this->link->getResultCode()) === Memcached::RES_SUCCESS) {
				return [$value, $this->link->get(self::EXPIRES . $keyHash) ? : null];
			} elseif ($this->logger && in_array($resultCode, $ee = [Memcached::RES_HOST_LOOKUP_FAILURE, 3, 4, 41, Memcached::RES_CONNECTION_SOCKET_CREATE_FAILURE], true)) {
				$resultMessage = $this->link->getResultMessage();
				$server = $this->link->getServerByKey($keyHash);
				$this->logger->critical('Memcache cache server is unavailable ('. $resultMessage . ' '. $server['host'] .':' . $server['port'] .')');
			}
		} elseif (($expires = $this->link->get(self::EXPIRES . $keyHash)) !== false) {
			return [$value, $expires? : null];
		}
		return false;
	}


	public function failure($host, $port) {
		$this->logger && $this->logger->critical('Memcache cache server is unavailable ('. $host .':' . $port . ')');
	}
}
