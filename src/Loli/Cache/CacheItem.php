<?php
namespace Loli\Cache;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;;
class CacheItem implements CacheItemInterface{
	protected $pool;
	protected $key;
	protected $hit;
	protected $value;
	protected $expires;
	protected $method;
	protected $newValue;
	public function __construct(CacheItemPoolInterface $pool, $key, $hit = null, $value= null, $expires = null) {
		$this->pool = $pool;
		$this->key = $key;
		$this->hit = $hit;
		$this->value = $value;
		$this->expires = $expires;
	}

	public function getKey() {
		return $this->key;
	}

	public function get() {
		return $this->isHit() ? $this->value : null;
	}

	public function set($value) {
		$this->value = $value;
		$this->method = $this->newValue = null;
		return $this;
	}

	public function isHit() {
		return $this->hit;
	}

	public function expiresAt($expires) {
		if (is_null($expires)) {
			$this->expires = null;
		} elseif (is_int($expires)) {
			if ($expires >= time()) {
				$this->expires = $expires;
			} else {
				$this->expires = 0;
			}
		} elseif ($expires instanceof \DateTimeInterface) {
			$this->expires = $expires->getTimestamp();
		} else {
			throw new InvalidArgumentException('Argument is not DateTimeInterface');
		}
		return $this;
	}

	public function expiresAfter($time) {
		if (is_null($time)) {
			$this->expires = null;
		} elseif (is_int($time)) {
			if ($time > 0) {
				$this->expires = time() + $time;
			} else {
				$this->expires = 0;
			}
		} elseif ($time instanceof DateInterval) {
			$datetime = new \DateTime();
			$datetime->add($time);
			$this->expires = $datetime->getTimestamp();
		} else {
			throw new InvalidArgumentException( 'Argument is not DateInterval');
		}
		return $this;
	}




	public function add($value) {
		if (!$this->isHit()) {
			$this->method = __FUNCTION__;
			$this->value = $value;
			$this->newValue = $value;
		}
		return $this;
	}

	public function incr($value) {
		if ($this->isHit() ) {
			if (!is_int($this->value)) {
				throw new InvalidArgumentException('The value cannot be increased');
			}
			if (!is_int($value)) {
				throw new InvalidArgumentException('Argument is not intger');
			}
			$this->method = __FUNCTION__;
			$this->value += $value;
			$this->newValue = $value;
		}
		return $this;
	}

	public function decr($value) {
		if ($this->isHit()) {
			if (!is_int($this->value)) {
				throw new InvalidArgumentException('The value cannot be decreased');
			}
			if (!is_int($value)) {
				throw new InvalidArgumentException('Argument is not intger');
			}
			$this->method = __FUNCTION__;
			$this->value -= $value;
			$this->newValue = $value;
		}
		return $this;
	}


	public function getMethod() {
		return $this->method ? $this->method : '';
	}

	public function getRawValue() {
		return $this->value;
	}

	public function getNewValue() {
		return $this->newValue;
	}


	public function getExpiresAt() {
		return $this->expires;
	}

	public function getExpiresAfter() {
		if ($expires = $this->getExpiresAt()) {
			return max(0, $expires - time());
		}
		return null;
	}


	public function setHit($hit) {
		$this->hit = $hit;
		return $this;
	}

	public function save() {
		return $this->pool->save($this);
	}

	public function delete() {
		return $this->pool->deleteItem($this->getKey());
	}
}
