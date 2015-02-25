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
/*	Updated: UTC 2015-02-25 13:40:05
/*
/* ************************************************************************** */
namespace Loli;


trait Model{

	private static $_classExixts = [];

	protected static $classCounts = 0;

	private $_modelID = '\\';



	public function __call($key, $args) {
		return call_user_func_array($this->$key, $args);
	}


	public function __get($key) {
		++self::$classCounts;
		if (!isset(self::$_classExixts[$class = 'Model' . $_modelID . $key])) {
			if (self::$_classExixts[$class] = class_exists($class)) {
				$this->$key = new $class;
			}
			Filter::run('Model.' . $_modelID . $key, [&$this->$key, &$this]);
			if (isset($this->_modelID)) {
				$this->_modelID = $_modelID . $key . '\\';
			}
		}


		global $_MODEL;
		if (!isset($_MODEL[$key])) {
			class_exists($class = 'Model\\' . $key) || trigger_error('Model does not exist', E_USER_ERROR);
			Filter::run('Model.' . $_modelID . $key, [&$this->$key, &$this]);
			if (isset($this->_modelID)) {
				$this->_modelID = $_modelID . $key . '\\';
			}
		}
		return $_MODEL[$key];
	}
}