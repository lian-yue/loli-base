<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-04-08 08:27:43
/*	Updated: UTC 2015-02-17 08:56:06
/*
/* ************************************************************************** */
namespace Loli;


trait Model{

	private static $__DATA = [];

	private $_DATA = [];

	public static $__COUNT = 0;

	public $__ID = [];

	final public static function __reg($key, $call = false) {
        if (isset(self::$__DATA[$key])) {
            return false;
        }
        self::$__DATA[$key] = $call;
        return true;
    }

	final public static function __has($key) {
        return isset(self::$__DATA[$key]);
    }

	final public static function __remove($key) {
		if (!isset(self::$__DATA[$key])) {
			return false;
		}
		global $_MODEL;
		unset(self::$__DATA[$key], $_MODEL[$key]);
		return true;
	}





	final public function _reg($key, $call = false) {
        if (isset($this->_DATA[$key])) {
            return false;
        }
        $this->_DATA[$key] = $call;
        return true;
    }

	final public function _has($key) {
        return isset($this->_DATA[$key]);
    }


	final public function _remove($key) {
		if (!isset($this->_DATA[$key])) {
			return false;
		}
		unset($this->_DATA[$key], $this->$key);
		return true;
	}


	public function __get($key) {
		++self::$__COUNT;

		// sub 的
		if (isset($this->_DATA[$key])) {
			$this->__ID || trigger_error('Unknown module ID', E_USER_ERROR);
			$ID = $this->__ID;
			$ID[] = $key;
			if ($this->_DATA[$key]) {
				$this->$key = call_user_func($this->_DATA[$key], $key, $ID);
			} else {
				$class = 'Model\\' . implode('\\', $ID);
				$this->$key = new $class;
			}
			if ($is = isset($this->$key->__ID)) {
				$this->$key->__ID = $ID;
			}
			Filter::run('Model.' . implode('\\', $ID), [&$this->$key, &$this]);
			if ($is && !$this->$key->__ID) {
				$this->$key->__ID = $ID;
			}
			return $this->$key;
		}

		global $_MODEL;
		if (!isset($_MODEL[$key])) {
			if (!isset(self::$__DATA[$key])) {
				trigger_error('Not found Model: '.$key, E_USER_ERROR);
			}
			if (self::$__DATA[$key]) {
				$_MODEL[$key] = call_user_func(self::$__DATA[$key], $key, [$key]);
			} else {
				$class = 'Model\\' . $key;
				$_MODEL[$key] = new $class;
			}
			if ($is = isset($_MODEL[$key]->__ID)) {
				$_MODEL[$key]->__ID = [$key];
			}
			Filter::run('Model.' . $key, [&$_MODEL[$key]]);
			if ($is && !$_MODEL[$key]->__ID) {
				 $_MODEL[$key]->__ID = $key;
			}
		}
		return $_MODEL[$key];
	}

	public function __call($key, $args) {
		return call_user_func_array($this->$key, $args);
	}
}