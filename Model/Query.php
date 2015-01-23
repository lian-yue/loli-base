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
/*	Updated: UTC 2015-01-22 13:18:49
/*
/* ************************************************************************** */
namespace Model;
class_exists('Loli\Query\Base') || exit;
$class = 'Loli\Query\\' . (empty($_SERVER['LOLI']['DB']['type']) || in_array($_SERVER['LOLI']['DB']['type'], ['Mysql', 'Mysqli']) ? 'Mysql' : $_SERVER['LOLI']['DB']['type']);
return new $class;
