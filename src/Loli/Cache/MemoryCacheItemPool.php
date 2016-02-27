<?php
namespace Loli\Cache;
class MemoryCacheItemPool extends AbstractCacheItemPool{

	public function validateKey($key) {
		return true;
	}

	public function getItems(array $keys = []) {

		$items = [];
		foreach ($keys as $key) {
			if (isset($this->data[$key])) {
				$items[$key] = new CacheItem($this, $key, true, ...$this->data[$key]);
			} else {
				$items[$key] = new CacheItem($this, $key, false);
			}
		}
		return $items;
	}

	public function deleteItems(array $keys) {
		$count = 0;
		foreach($keys as $key) {
			if (isset($this->data[$key])) {
				unset($this->data[$key]);
				++$count;
			}
		}
		return $count !== 0;
	}

	public function hasItem($key) {
		return isset($this->data[$key]);
	}

	public function clear() {
		$this->data = [];
		return true;
	}

	protected function write(array $items) {
		$count = 0;
		foreach ($items as $item) {
			$key = $item->getKey();
			switch ($item->getMethod()) {
				case 'add':
					if ($this->hasItem($key)) {
						continue 2;
					}
					$value = $item->getNewValue();
					break;
				case 'incr':
					if (!$this->hasItem($key)) {
						continue 2;
					}
					$value = $this->data[$key][0] + $item->getNewValue();
					break;
				case 'decr':
					if (!$this->hasItem($key)) {
						continue 2;
					}
					$value = $this->data[$key][0] - $item->getNewValue();
				case '':
					$value = $item->getRawValue();
			}
			++$count;
			$this->data[$key] = [$value, $item->getExpiresAt()];
			$item->set($value);
			$item->setHit(true);
		}
		return $count;
	}
}
