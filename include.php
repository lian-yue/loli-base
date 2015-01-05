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
/*	Updated: UTC 2015-01-05 09:32:05
/*
/* ************************************************************************** */
namespace Loli;

// 如果是网页 ICO 结束查询 或者 flash 请求
if (!empty($_SERVER['REQUEST_URI']) && in_array(strtolower($_SERVER['REQUEST_URI']), ['/favicon.ico', '/crossdomain.xml', '/robots.txt'])) {
	exit;
}

// PHP 版本检测
version_compare('5.4', phpversion(), '>') && trigger_error('php version less than 5.4', E_USER_ERROR);

// 修改默认时区
date_default_timezone_set('UTC');

// 修改默认编码
mb_internal_encoding('UTF-8');


// 修改
$_SERVER += ['SERVER_SOFTWARE' => '', 'REQUEST_URI' => ''];

// 修改 IIS 的  _SERVER 信息
if (empty($_SERVER['REQUEST_URI']) || (php_sapi_name() != 'cgi-fcgi' && preg_match('/^Microsoft-IIS\//', $_SERVER['SERVER_SOFTWARE']))) {

	if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
		// IIS Mod-Rewrite 静态化
		$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
	} elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
		// IIS Isapi_Rewrite 静态化
		$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
	} else {
		// 如果 没有 PATH_INFO  使用  ORIG_PATH_INFO
		if (!isset($_SERVER['PATH_INFO']) && isset($_SERVER['ORIG_PATH_INFO'])) {
			$_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];
		}
		// IIS 某些 配置 途径信息 无需添加 两次
		if (isset($_SERVER['PATH_INFO'])) {
			if ($_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME']) {
				$_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
			} else {
				$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
			}
		}

		// 追加查询字符串, 如果它存在, 并且不为空
		if (! empty($_SERVER['QUERY_STRING'])) {
			$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
		}
	}
}
// 为PHP解决所有请求CGI主机 SCRIPT_FILENAME , 在php.cgi设置的东西结束
if (isset($_SERVER['SCRIPT_FILENAME']) && (strpos($_SERVER['SCRIPT_FILENAME'], 'php.cgi') == strlen($_SERVER['SCRIPT_FILENAME']) - 7)) {
	$_SERVER['SCRIPT_FILENAME'] = $_SERVER['PATH_TRANSLATED'];
}
// 修改 Dreamhost  和 CGI 的
if (strpos($_SERVER['SCRIPT_NAME'], 'php.cgi') !== false) {
	unset($_SERVER['PATH_INFO']);
}
// 修改 空 PHP_SELF
if (empty($_SERVER['PHP_SELF'])) {
	$_SERVER['PHP_SELF'] = preg_replace('/(\?.*)?$/', '', $_SERVER["REQUEST_URI"]);
}




// Loli 目录
const DIR = __DIR__;


// 载入函数
require __DIR__ . '/functions.php';


// 自动加载
spl_autoload_register(function($name) {
	if (substr($name, 0, 5) == 'Loli\\' && is_file($file = __DIR__ . '/' . ($name = strtr($name, '\\', '/')) . '.php')) {
		require $file;
		do_call($name);
		return true;
	}
	return false;
});


// debug
if (!empty($_SERVER['LOLI']['DEBUG']['is'])) {
	new Debug($_SERVER['LOLI']['DEBUG']);
}


// 缓存
Model::__reg('Cache', ['file' => __DIR__ . '/Model/Cache.php']);

// 静态
Model::__reg('DB', ['file' => __DIR__ . '/Model/DB.php']);

// 查询对象
Model::__reg('Query', ['file' => __DIR__ . '/Model/Query.php']);

// session
Model::__reg('Session', ['file' => __DIR__ . '/Model/Session.php']);





// 自定义协议
//stream_wrapper_register
