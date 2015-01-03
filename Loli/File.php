<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-01 15:27:44
/*	Updated: UTC 2015-01-01 15:55:16
/*
/* ************************************************************************** */
namespace Loli;
class File{
    private static $_link;
    public static function __callstatic($method,$args) {
    	if (!isset(self::$_link)) {
    		$class = __NAMESPACE__ '\File\\' . (empty($_SERVER['LOLI']['FILE']['type']) ? 'Local' : $_SERVER['LOLI']['FILE']['type']);
			self::$_link = new $class($_SERVER['LOLI']['FILE']);
    	}
    	return call_user_func_array([self::$_link, $method], $args);
    }
}
