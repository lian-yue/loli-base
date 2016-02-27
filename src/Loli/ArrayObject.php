<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-22 03:09:29
/*
/* ************************************************************************** */
namespace Loli;
use Closure;
use Countable;
use ArrayAccess;
use Serializable;
use JsonSerializable;
use SeekableIterator;
use OutOfBoundsException;


class ArrayObject implements JsonSerializable, ArrayAccess, Countable, SeekableIterator, Serializable{

	private $data = [];

	public function __construct() {
		foreach (func_get_args() as $data) {
			foreach ($data as $name => $value) {
				$this->__set($name, $value);
			}
		}
	}

	public function __get($name) {
		return isset($this->data[$name]) ? $this->data[$name] : NULL;
	}

	public function __set($key, $value) {
		if ($key === NULL) {
			return $this->data[] = $value;
		}
		return $this->data[$key] = $value;
	}

	public function __unset($key) {
		unset($this->data[$key]);
		return true;
	}

	public function __isset($key) {
		return isset($this->data[$key]);
	}

	public function __clone() {
		foreach ($this->data as $key => $value) {
			if (is_object($value)) {
				$value = clone $value;
			}
		}
	}

	public function offsetExists($key) {
		return $this->__isset($key);
	}

	public function offsetGet($key) {
		return $this->__get($key);
	}
	public function offsetSet($key, $value) {
		return $this->__set($key, $value);
	}
	public function offsetUnset($key) {
		return $this->__unset($key);
	}

	public function keyExists($key) {
		return array_key_exists($key, $this->data);
	}

	public function current() {
		return current($this->data);
	}
	public function key() {
		return key($this->data);
	}
	public function next() {
		return next($this->data);
	}

	public function rewind() {
		return reset($this->data);
	}

	public function valid() {
		return key($this->data) !== NULL;
	}

	public function seek($position) {
		if (!isset($this->data[$position])) {
			throw new OutOfBoundsException('invalid seek position ('.$position.')');
		}
		return $this;
	}

	public function count() {
		return count($this->data);
	}

	public function toArray() {
		return $this->data;
	}

	public function value($name, $value) {
		$this->__set($name, $value);
		return $this;
	}

	public function merge($array) {
		foreach ($array as $key => $value) {
			$this->__set(is_int($key) ? NULL : $key, $value);
		}
		return $this;
	}

	public function write($array) {
		foreach ($array as $key => $value) {
			$this->__set($key, $value);
		}
		return $this;
	}

	public function clear() {
		$this->data = [];
		return $this;
	}

	public function jsonSerialize() {
		return $this->data;
	}


	public function serialize() {
		return serialize($this->data);
	}

	public function unserialize($data) {
		$this->data = unserialize($data);
	}

	public function asort() {
		asort($this->data);
		return $this;
	}


	public function ksort() {
		ksort($this->data);
		return $this;
	}

	public function natsort() {
		natsort($this->data);
		return $this;
	}
	public function natcasesort() {
		natcasesort($this->data);
		return $this;
	}


	public function uasort(callable $callable) {
		uasort($this->data, $callable);
		return $this;
	}

	public function uksort(callable $callable) {
		uksort($this->data, $callable);
		return $this;
	}

	public function join($glue) {
		return implode($glue, $this->data);
	}

	public function __call($name, $args) {
		switch (substr($name, 0, 3)) {
			case 'get':
				if ($this->__isset($name = snake(substr($name, 3)))) {
					return $this->__get($name);
				}
				break;
			case 'add':
				if (!$this->__isset($name = snake(substr($name, 3)))) {
					$this->__set($name, $args ? $args[0] : NULL);
				}
				return $this;
				break;
			case 'set':
				$this->__set(snake(substr($name, 3)), $args ? $args[0] : NULL);
				return $this;
				break;
			default:
				if (($value = $this->__get($name)) && ($value instanceof Closure || (is_object($value) && method_exists($value, '__invoke')))) {
					return $value(...$args);
				}
		}
		if (method_exists($this, '_call')) {
			return $this->_call($name, $args);
		}
		throw new OutOfBoundsException(static::class .'::'. __FUNCTION__ .'('. $name .') Method or function does not exist');
	}
	public function __debugInfo() {
		return $this->data;
	}
}
