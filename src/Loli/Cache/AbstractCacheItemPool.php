<?php
namespace Loli\Cache;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

use Loli\Traits\ConstructConfigTrait;

abstract class AbstractCacheItemPool implements CacheItemPoolInterface, LoggerAwareInterface{
	use LoggerAwareTrait, ConstructConfigTrait;

	protected $key;

	protected $data = [];

	protected $deferred = [];

	protected $names = ['deferred', 'data'];

	public function getItem($key) {
		return $this->getItems([$key])[$key];
	}

	public function deleteItem($key) {
		return $this->deleteItems([$key]);
	}

	public function save(CacheItemInterface $item) {
		return $this->write([$item]) === 1;
	}

	public function saveDeferred(CacheItemInterface $item) {
		$this->deferred[] = $item;
		return true;
	}

	public function commit() {
		$success = $this->write($this->deferred);
		if ($success) {
			$this->deferred = [];
		}
		return $success;
	}
	protected function getKeyHash($key) {
		return md5($key . $this->key) . substr(md5($this->key . $key), 12, 8);
	}
	abstract protected function write(array $items);
}
