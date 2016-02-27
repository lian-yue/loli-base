<?php
namespace Loli\Cache;
class FileCacheItemPool extends AbstractCacheItemPool{

	protected $dir;


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
			if ($this->hasItem($key)) {
				++$count;
			}
			unset($this->data[$key]);
			if (is_file($file = $this->getPath($key))) {
				@unlink($file);
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
		$opendir = opendir($this->dir);
		while ($name = readdir($opendir)) {
			if (in_array($name, ['.', '..'], true)) {
				continue;
			}
			if (is_file($path = $this->dir . '/' . $name)) {
				@unlink($path);
			}
		}
		closedir($opendir);
		return true;
	}

	protected function write(array $items) {
		$count = 0;
		foreach ($items as $item) {
			$key = $item->getKey();
			switch ($item->getMethod()) {
				case 'add':
					if ($this->getData($key)) {
						continue 2;
					}
					$data = [$item->getNewValue(), $item->getExpiresAt()];
					break;
				case 'incr':
					if ($data = $this->getData($key)) {
						continue 2;
					}
					$data[1] += $item->getNewValue();
					break;
				case 'decr':
					if ($data = $this->getData($key)) {
						continue 2;
					}
					$data[1] -= $item->getNewValue();
					break;
				case '':
					$data = [$item->getRawValue(), $item->getExpiresAt()];
			}
			if (!fwrite(fopen($this->getPath($key), 'wb'), serialize($data))) {
				unset($this->data[$key]);
				continue;
			}
			$this->data[$key] = $data;
			$item->setHit(true);
			++$count;
		}
		return $count;
	}

	private function getPath($key) {
		return $this->dir . '/' . $this->getKeyHash($key);
	}

	private function getData($key) {
		if (is_file($file = $this->getPath($key))) {
			if (($a = @unserialize(fread(fopen($file, 'rb'), filesize($file)))) && (!$a[1] || $a[1] >= time())) {
				return $a;
			} else {
				@unlink($file);
			}
		}
		return false;
	}
}
