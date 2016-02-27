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
		throw new InvalidArgumentException(__METHOD__.'('.$name.') The results is empty');
	}

	public function query($column, $value, $compare = '=') {
		$array = [];
		switch (strtoupper($column)) {
			case '=':
			case '':
				foreach ($this->toArray() as $value) {
					if ($value->$column == $value) {
						$array[] = $value;
					}
				}
				break;
			case '==':
			case '===':
				foreach ($this->toArray() as $value) {
					if ($value->$column === $value) {
						$array[] = $value;
					}
				}
				break;
			case '<':
				foreach ($this->toArray() as $value) {
					if ($value->$column < $value) {
						$array[] = $value;
					}
				}
				break;
			case '<=':
			case '=<':
				foreach ($this->toArray() as $value) {
					if ($value->$column <= $value) {
						$array[] = $value;
					}
				}
				break;
			case '>':
				foreach ($this->toArray() as $value) {
					if ($value->$column > $value) {
						$array[] = $value;
					}
				}
				break;
			case '>=':
			case '=>':
				foreach ($this->toArray() as $value) {
					if ($value->$column >= $value) {
						$array[] = $value;
					}
				}
				break;
			case 'IN':
				foreach ($this->toArray() as $value) {
					if (in_array($value->$column, (array) $value)) {
						$array[] = $value;
					}
				}
				break;
			default:
				throw new InvalidArgumentException(__METHOD__.'('.$column.')  Unknown query compare');
		}
		return $this->clear()->write($array);
	}

	public function order($column, $order = NULL) {
		if ($order === NULL && !is_scalar($column)) {
			foreach ($column as $key => $value) {
				$this->order($key, $value);
			}
			return $this;
		} elseif (strtoupper($order) === 'DESC' || $order == -1) {
			$array = $this->toArray();
			usort($array, function($a, $b) use($column) {
				 if ($a->$column == $b->$column) {
			        return 0;
			    }
			    return ($a->$column < $b->$column) ? 1 : -1;
			});
		} else {
			$array = $this->toArray();
			usort($array, function($a, $b) use($column) {
				 if ($a->$column == $b->$column) {
			        return 0;
			    }
			    return ($a->$column < $b->$column) ? -1 : 1;
			});
		}
		return $this->clear()->write($array);
	}
}
