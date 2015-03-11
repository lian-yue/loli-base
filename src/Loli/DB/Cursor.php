<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-03-05 16:10:51
/*	Updated: UTC 2015-03-10 08:14:07
/*
/* ************************************************************************** */
namespace Loli\DB;
use Loli\Log;
abstract class Cursor  implements Iterator{
	protected $results;

	// 是否执行过
	protected $execute = false;

	abstract function __construct(Base $base, $table, array $fields = [], array $querys = [], array $options = []);
	abstract function table($table, $asName = false, $join = '', $on = []);
	abstract function field($field, $asName);
	abstract function query(array $query);
	abstract function group($field, $);
	abstract function offset($offset);
	abstract function limit($limit);
	abstract function option($name, $value);
	abstract function count($sum = true);
	abstract function execute();

	public function debug() {
		print_r($this->results);
	}

	public function results() {
		$this->execute || $this->execute();
		return $this->results;
	}

	public function getPrev() {
		return $this->hasPrev() ? $this->results[key($this->results) - 1] : false;
	}

	public function hasPrev() {
		$this->execute || $this->execute();
		return isset($this->results[key($this->results) - 1]);
	}

	public function getNext() {
		return $this->hasNext() ? $this->results[key($this->results) + 1] : false;
	}

	public function hasNext() {
		$this->execute || $this->execute();
		return isset($this->results[key($this->results) + 1]);
	}


	public function rewind() {
		$this->execute || $this->execute();
		return reset($this->results);
	}

	public function current() {
		$this->execute || $this->execute();
		return current($this->results);
	}

	public function key() {
		$this->execute || $this->execute();
		return key($this->results);
	}

	public function next() {
		$this->execute || $this->execute();
		return next($this->results);
	}

	public function valid() {
		$this->execute || $this->execute();
		$key = key($this->results);
		return ($key !== NULL && $key !== false);
	}
}