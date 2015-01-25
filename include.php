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
/*	Updated: UTC 2015-01-24 13:36:10
/*
/* ************************************************************************** */
namespace Loli;

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

const VERSION = '1.0.2';

const SUPPORT = 'Loli.Net';

// 版本号
@header( 'X-Version: ' . VERSION);

// 技术支持
@header( 'X-Support: ' . SUPPORT);













// 载入函数
require __DIR__ . '/functions.php';


// 自动加载配置
$_SERVER['LOLI']['LIBRARY']['Loli/'] = __DIR__ . '/Library';
spl_autoload_register(function($name) {
	static $call;
	if (empty($call)) {
		$call = function($a, $b) {
			if (($a = strlen($a)) == ($b = strlen($b))) {
				return 0;
			}
			return ($a < $b) ? 1 : -1;
		};
	}
	uksort($_SERVER['LOLI']['LIBRARY'], $call);
	$name = strtr($name, '\\', '/');
	foreach ($_SERVER['LOLI']['LIBRARY'] as $key => $value) {
		$length = strlen($key);
		if (($key == $name || (substr($key, -1, 1) == '/' && substr($name, 0, $length) == $key)) && is_file($file = $value . ($key == $name ? '' : '/'. substr($name, $length)) .  '.php')) {
			require $file;
			do_call('Library.'. $name);
			return true;
		}
	}
	return false;
});

// debug
if (!empty($_SERVER['LOLI']['DEBUG']['is'])) {
	new Debug($_SERVER['LOLI']['DEBUG']);
}



$func = function($key) {
	return require __DIR__ . '/Model/'.$key.'.php';
};

// 缓存
Model::__reg('Cache', $func);

// 静态
Model::__reg('DB', $func);

// 查询对象
Model::__reg('Query', $func);

// Session
Model::__reg('Session', $func);


unset($func);


// 自定义协议
//stream_wrapper_register
