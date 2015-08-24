<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-21 13:42:16
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
/*	Created: UTC 2014-12-31 15:46:54
/*	Updated: UTC 2015-05-23 11:23:04
/*
/* ************************************************************************** */
namespace Loli;

// PHP 版本检测
version_compare('5.4', phpversion(), '>') && trigger_error('php version less than 5.4', E_USER_ERROR);

// 修改默认时区
date_default_timezone_set('UTC');

// 修改默认编码
mb_internal_encoding('UTF-8');

// 禁用 XML 外部实体
libxml_disable_entity_loader(true);

// 内存限制
@ini_set('memory_limit', empty($_SERVER['LOLI']['limit']) ? '256M' : $_SERVER['LOLI']['limit']);

// 激活引用计数器
@gc_enable();

// 关闭缓冲区
while(ob_get_level()) {
	ob_end_flush();
}

// 系统版本号
const VERSION = '0.0.2';

// 系统技术支持
const POWERED_BY = 'Loli.Net';



if (!headers_sent()) {
	// 默认 编码
	header('Content-Type: text/html; charset=UTF-8');

	// 版本信息
	header('X-Version: ' . VERSION);

	// 版权信息
	header('X-Powered-By: ' . POWERED_BY);
}
