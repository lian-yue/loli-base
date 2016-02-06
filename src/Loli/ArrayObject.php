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

	private $_data = [];

	public function __construct() {
		foreach (func_get_args() as $data) {
			foreach ($data as $name => $value) {
				$this->__set($name, $value);
			}
		}
	}

	public function __get($name) {
		return isset($this->_data[$name]) ? $this->_data[$name] : NULL;
	}

	public function __set($key, $value) {
		if ($key === NULL) {
			return $this->_data[] = $value;
		}
		return $this->_data[$key] = $value;
	}

	public function __unset($key) {
		unset($this->_data[$key]);
		return true;
	}

	public function __isset($key) {
		return isset($this->_data[$key]);
	}

	public function __clone() {
		foreach ($this->_data as $key => $value) {
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
		return array_key_exists($key, $this->_data);
	}

	public function current() {
		return current($this->_data);
	}
	public function key() {
		return key($this->_data);
	}
	public function next() {
		return next($this->_data);
	}

	public function rewind() {
		return reset($this->_data);
	}

	public function valid() {
		return key($this->_data) !== NULL;
	}

	public function seek($position) {
		if (!isset($this->_data[$position])) {
			throw new OutOfBoundsException('invalid seek position ('.$position.')');
		}
		return $this;
	}

	public function count() {
		return count($this->_data);
	}

	public function data() {
		return $this->_data;
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
		$this->_data = [];
		return $this;
	}

	public function jsonSerialize() {
		return $this->_data;
	}


	public function serialize() {
		return serialize($this->_data);
	}

	public function unserialize($data) {
		$this->_data = unserialize($data);
	}

	public function asort() {
		asort($this->_data);
		return $this;
	}


	public function ksort() {
		ksort($this->_data);
		return $this;
	}

	public function natsort() {
		natsort($this->_data);
		return $this;
	}
	public function natcasesort() {
		natcasesort($this->_data);
		return $this;
	}


	public function uasort(callable $callable) {
		uasort($this->_data, $callable);
		return $this;
	}

	public function uksort(callable $callable) {
		uksort($this->_data, $callable);
		return $this;
	}

	public function json($glue) {
		return implode($glue, $this->_data);
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
		throw new Exception('Method or function does not exist');
	}
}