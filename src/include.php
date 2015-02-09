<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-12-31 15:46:54
/*	Updated: UTC 2015-02-09 15:10:24
/*
/* ************************************************************************** */
namespace Loli;

// PHP 版本检测
version_compare('5.4', phpversion(), '>') && trigger_error('php version less than 5.4', E_USER_ERROR);

// 修改默认时区
date_default_timezone_set('UTC');

// 修改默认编码
mb_internal_encoding('UTF-8');

// 禁用XML 外部实体
libxml_disable_entity_loader(true);

// 系统版本号
const VERSION = '1.0.2';

// 系统技术支持
const SUPPORT = 'Loli.Net';


// 版本信息
if (!headers_sent()) {
	header('X-Version: ' . VERSION);
	header('X-Support: ' . SUPPORT);
}

// Debug
if (!empty($_SERVER['LOLI']['DEBUG']['is'])) {
	new Debug($_SERVER['LOLI']['DEBUG']);
}


// 注册 DB 全局
Model::__reg('DB', function() {
	empty($_SERVER['LOLI']['DB']) && trigger_error( 'Variables $_SERVER[\'LOLI\'][\'DB\'] does not exist', E_USER_ERROR);
	$class = __NAMESPACE__ . '\DB\\' . (empty($_SERVER['LOLI']['DB']['type']) || in_array($_SERVER['LOLI']['DB']['type'], ['MySQL', 'MySQLi']) ? (class_exists('MySQLi') ? 'MySQLi' : 'MySQL') : $_SERVER['LOLI']['DB']['type']);
	return $class($_SERVER['LOLI']['DB']);
});

// 注册 Query 全局
Model::__reg('Query', function() {
	$class = __NAMESPACE__. '\Query\\' . (empty($_SERVER['LOLI']['DB']['type']) || in_array($_SERVER['LOLI']['DB']['type'], ['MySQL', 'MySQLi']) ? 'MySQL' : $_SERVER['LOLI']['DB']['type']);
	return new $class;
});
