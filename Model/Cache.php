<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-04-08 18:08:30
/*	Updated: UTC 2015-01-04 09:52:41
/*
/* ************************************************************************** */
namespace Model;
if (empty($_SERVER['LOLI']['CACHE']['args'])) {
	trigger_error('Variables $_SERVER[\'LOLI\'][\'CACHE\'][\'args\'] does not exist', E_USER_ERROR);
}
$class = '\Loli\Cache\\' . (empty($_SERVER['LOLI']['CACHE']['mode']) ? 'File' : $_SERVER['LOLI']['CACHE']['mode']);
return new $class($_SERVER['LOLI']['CACHE']['args'], empty($_SERVER['LOLI']['CACHE']['key']) ? '' : $_SERVER['LOLI']['CACHE']['key']);