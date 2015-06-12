<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-06-05 02:29:41
/*	Updated: UTC 2015-06-08 14:32:12
/*
/* ************************************************************************** */
namespace Loli\DOM\CSS;
class References{

	private $_references = [];

	public function __construct($references = []) {
		if (is_array($references) || is_object($references)) {
			foreach ($references as $key => $value) {
				$this->offsetSet($name, $value);
			}
		} else {
			foreach (explode(';', preg_replace('/\/\*.*\*\//s', '', $references)) as $value) {
				if ($value && count($value = explode(':', $value, 2)) === 2) {
					$this->offsetSet($value[0], $value[1]);
				}
			}
		}
	}

	public function __get($name) {
		return  $this->offsetGet($name);
	}

	public function __set($name, $value) {
		$this->offsetSet($name, $value);
	}

	public function __isset($name) {
		return  $this->offsetExists($name);
	}

	public function __unset($name) {
		return  $this->offsetUnset($name);
	}

	public function offsetSet($name, $value) {
		$value = trim($value);
		$name = strtolower(trim($name));
		if ($value && $name) {
			$this->_references[$name] = $value;
		} else {
			unset($this->_references[$name]);
		}
	}
	public function offsetExists($name) {
		return isset($this->_references[$name]);
	}
	public function offsetUnset($name) {
		unset($this->_references[$name]);
	}

	public function offsetGet($name) {
		return isset($this->_references[$name]) ? $this->_references[$name] : NULL;
	}

	public function serialize() {
		return serialize($this->_references);
	}

	public function unserialize($attributes) {
		$this->_references = unserialize($attributes);
	}

	public function count() {
		return count($this->_references);
	}

	public function getIterator() {
		return new ArrayIterator($this->_references);
	}



	public function jsonSerialize() {
		$array = [];
		foreach ($this as $key => $value) {
			if ($value)
			$array[$key] = $value;
		}
		return $array;
	}

	public function __toString() {
		$array = $this->jsonSerialize();
		ksort($array);
		$references = '';
		foreach ($array as $key => &$value) {
			$value = $key . ':' .strtr($value, ['"' => '', '\'' => '', ';' => '']);
		}
		return implode(';', $array);
	}
}