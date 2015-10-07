<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-22 06:52:42
/*
/* ************************************************************************** */
namespace Loli;
use Loli\DB\Cursor, ArrayAccess;
class_exists('Loli\DB\Cursor') || exit;
class Table extends Cursor implements ArrayAccess{
	protected $callback = true;

	protected $route;

	public function __construct(Route &$route) {
		$this->route = &$route;
		$this->DB = $route->DB;
	}

	public function flush() {
		if ($this->increment !== $this->current) {
			$this->fields = $this->querys = $this->values = $this->documents = $this->options = $this->unions = $this->data = [];
			$this->builder && $this->builder->flush();
			$this->current = $this->increment = 0;
		}
		return $this;
	}



	public function offsetExists($name) {
		return $this->route->table->offsetExists($name);
	}

	public function offsetGet($name) {
		return $this->route->table->offsetGet($name);
	}

	public function offsetSet($name, $value) {
		return $this->route->table->offsetSet($name, $value);
	}

	public function offsetUnset($name) {
		return $this->route->table->offsetUnset($name);
	}
}