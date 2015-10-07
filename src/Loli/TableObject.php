<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-09-06 01:02:59
/*
/* ************************************************************************** */
namespace Loli;
use ArrayAccess;
class_exists('Loli\Route') || exit;
class TableObject implements ArrayAccess{
	private $_route;

	private $_data = [];
	private $_namespace;

	public function __construct(Route &$route, $namespace) {
		$this->_route = &$route;
		$this->_namespace = $namespace;
	}

	public function offsetExists($name) {
		return $this->__isset($name);
	}

	public function offsetGet($name) {
		return $this->__get($name);
	}

	public function offsetSet($name, $value) {
		return $this->__set($name, $value);
	}

	public function offsetUnset($name) {
		return $this->__unset($name);
	}

	public function __invoke($name, $new = false) {
		return $this->__get($name);
	}

	public function __get($name) {
		$name = strtr($name, '/.', '\\\\');
		if (isset($this->_data[$name])) {
			return $this->_data[$name]->flush();
		}
		if (!class_exists($class = $this->_namespace . '\\' . $name)) {
			throw new Exception('Class does not exist');
		}
		$this->_data[$name] = new $class($this->_route);
		return $this->_data[$name];
	}

	public function __set($name, $value) {
		throw new Exception('Can not be modified');
	}

	public function __isset($name) {
		$name = strtr($name, '/.', '\\\\');
		return isset($this->_data[$name]) || class_exists($class = $this->_namespace . '\\' . $name);
	}

	public function __unset($name) {
		unset($this->_data[strtr($name, '/.', '\\\\')]);
		return true;
	}
}