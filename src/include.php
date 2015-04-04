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
/*	Updated: UTC 2015-04-03 14:45:20
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

// 关闭缓冲区
while(ob_get_level()) {
	ob_end_flush();
}

// 打开缓冲区
//ob_start(null, 4096);

// 系统版本号
const VERSION = '0.0.2';

// 系统技术支持
const SUPPORT = 'www.Loli.Net';


if (!headers_sent()) {
	// 默认 编码
	header('Content-Type: text/html; charset=UTF-8');

	// 版本信息
	header('X-Version: ' . VERSION);

	// 版权信息
	header('X-Support: ' . SUPPORT);
}

// Debug
if (!empty($_SERVER['LOLI']['DEBUG']['is'])) {
	//new Debug($_SERVER['LOLI']['DEBUG']);
}


