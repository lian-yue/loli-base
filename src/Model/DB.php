<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-04-09 08:08:57
/*	Updated: UTC 2015-01-16 08:07:03
/*
/* ************************************************************************** */
namespace Model;
class_exists('Loli\DB\Base') || exit;
if (empty($_SERVER['LOLI']['DB'])) {
	trigger_error( 'Variables $_SERVER[\'LOLI\'][\'DB\'] does not exist', E_USER_ERROR);
}
$class = 'Loli\DB\\' . (empty($_SERVER['LOLI']['DB']['type']) || in_array($_SERVER['LOLI']['DB']['type'], ['MySQL', 'MySQLi']) ? (class_exists('MySQLi') ? 'MySQLi' : 'MySQL') : $_SERVER['LOLI']['DB']['type']);
return new $class($_SERVER['LOLI']['DB']);