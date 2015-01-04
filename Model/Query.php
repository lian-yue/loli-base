<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-04-09 09:34:24
/*	Updated: UTC 2014-12-31 07:49:52
/*
/* ************************************************************************** */
namespace Model;
if (empty($_SERVER['LOLI']['DB'])) {
	trigger_error('Variables $_SERVER[\'LOLI\'][\'DB\'] does not exist', E_USER_ERROR);
}
$class = 'Loli\Query\\' . (empty($_SERVER['LOLI']['DB']['type']) || in_array($_SERVER['LOLI']['DB']['type'], ['Mysql', 'Mysqli']) ? 'Mysql' : $_SERVER['LOLI']['DB']['type']);
return new $class;
