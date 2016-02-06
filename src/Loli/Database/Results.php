<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-05-23 11:48:42
/*
/* ************************************************************************** */
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-03-10 08:00:28
/*	Updated: UTC 2015-05-23 11:48:42
/*
/* ************************************************************************** */
namespace Loli\Database;
use Loli\ArrayObject;

class Results extends ArrayObject{
	public function __construct($args = false) {
		if ($args) {
			if ($args instanceof Results || (is_array($args) && (is_int(key($args)) || reset($args) instanceof Document))) {
				foreach ($args as $value) {
					$this->__set(NULL, $value);
				}
			} elseif (!is_scalar($args)) {
				$this->__set(NULL, $args);
			}
		}
	}

	public function __set($name, $value) {
		if ($name === NULL || is_int($name)) {
			return parent::__set($name, $value instanceof Document ? $value : (new Document($value)));
		}

		if (($key = $this->key()) !== NULL) {
			return parent::__get($key)->__set($name, $value);
		}

		return parent::__set(NULL, new Document([$name => $value]));
	}

	public function __get($name) {
		if (is_int($name)) {
			return parent::__get($name);
		}
		if (($key = $this->key()) !== NULL) {
			return parent::__get($key)->__get($name);
		}
		return NULL;
	}

	public function __call($name, $args) {
		if (($key = $this->key()) !== NULL) {
			return parent::__get($key)->__call($name, $args);
		}
		throw new Exception('results.'. $name .'()', 'The results is empty');
	}
}