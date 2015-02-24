<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-01-15 13:01:52
/*	Updated: UTC 2015-02-24 03:34:51
/*
/* ************************************************************************** */

/**
 * 数据库查询次数
 * @return integer
 */
function load_db() {
	return Loli\DB\Base::$querySum;
}

/**
 * PHP 载入执行时间
 * @param  integer $decimal 小数点精确
 * @return float
 */
function load_time($decimal = 4) {
	return number_format(microtime(true)- $_SERVER['REQUEST_TIME_FLOAT'], $decimal);
}



/**
 * PHP 载入最大内存使用率
 * @param  integer $decimal 小数点精确
 * @return float
 */
function load_ram($decimal = 4) {
	return number_format((memory_get_peak_usage() / 1024 / 1024), $decimal);
}


/**
 * 载入文件数量
 * @return integer
 */
function load_file() {
	return count(get_included_files());
}



/**
*	字符串转换成数组
*
*	1 参数 输入GET类型字符串
*
*	返回值 GET数组
**/
function parse_string($string) {
	if (is_array($string) || is_object($string)) {
		$call = function($arrays) use(&$call) {
			$arrays = (array) $arrays;
			foreach ($arrays as $key => &$value) {
				if (is_array($value) || is_object($value)) {
					if (!$value = $call($value)) {
						unset($arrays[$key]);
					}
				} elseif (is_bool($value)) {
					$value = (int) $value;
				} elseif (!is_string($value) && !is_int($value) && !is_float($value)) {
					unset($arrays[$key]);
				}
			}
			return $arrays;
		};
		$r = $call($string);
	} else {
		parse_str($string, $r);
	}
	return $r;
}



/**
*	数组转换成字符串
*
*	1 参数 数组
*
*	返回值 GET字符串
**/
function merge_string($a) {
	if (!is_array($a) && !is_object($a)) {
		return (string) $a;
	}
	return http_build_query(to_array($a), null, '&');
}


/**
*	转成数组
*
*	1 参数 数组 或者 对象
*
*	返回值 数组
**/
function to_array($a) {
	$a = (array) $a;
	foreach ($a as &$v) {
		if (is_array($v) || is_object($v)) {
			$v = to_array($v);
		}
	}
	return $a;
}


/**
*	转成对象
*
*	1 参数 数组 或者 对象
*
*	返回值 对象
**/
function to_object($a) {
	$a = (object) $a;
	foreach ($a as &$v) {
		if (is_array($v) || is_object($v)) {
			$v = to_object($v);
		}
	}
	return $a;
}



/**
*	删除 数组中 的 null 值
*
*	1 参数 数组
*	2 参数 是否回调删除多维数组
*
*	返回值 数组
**/
function array_unnull(array $a, $call = false) {
	foreach ($a as $k => $v) {
		if ($call && is_array($a) && $a) {
			 $a[$k] = array_unnull($a, $call);
		}
		if ($v === null) {
			unset($a[$k]);
		}
	}
	return $a;
}



/**
*	判断 某个url 是否是同一域名
*
*	1 参数 某个 url
*	2 参数 某个 url
*
*	返回值 true = 是相同 false = 不是
**/
function domain_match($match, $domain) {
	if (!$match || !$domain) {
		return false;
	}
	if (!preg_match('/^(https?|ftp)\:\/\//i', $domain)) {
		$domain = substr($domain, 0, 2) == '//' ? 'http:' . $domain : 'http://' . $domain;
	}
	if (substr($match, 0, 2) == '//') {
		$match = 'http:' . $match;
	}
	if (!($match = parse_url($match)) || empty($match['host']) || !($domain = parse_url($domain)) || empty($domain['host'])) {
		return false;
	}
	return $match['host'] == $domain['host'] || preg_match('/(^|\.)'. preg_quote($domain['host'], '/') .'$/i', $match['host']);
}



/**
*	移除 xml 中的 CDATA
*
*	1 参数 xml 数据
*
*	返回值 移除后的xml
**/
function simplexml_uncdata($xml) {
	if (preg_match_all("/\<(?<tag>[^<>]+)\>\s*\<\!\[CDATA\s*\[(.*)\]\]\>\s*\<\/\k<tag>\>/isU", $xml, $matches)) {
		$find = $replace = [];
		foreach ($matches[0] as $k => $v) {
			$find[] = $v;
			$replace[] = '<'. $matches['tag'][$k] .'>' .htmlspecialchars($matches[2][$k], ENT_QUOTES). '</' . $matches['tag'][$k].'>';
		}

		$xml = str_replace($find, $replace, $xml);
	}

	return $xml;
}


/**
*	url 合并 parse_url 的反响函数
*
*	1 参数 parse 解析后的数组
*
*	返回 合并后的字符串
**/

function merge_url(array $parse) {
	$url = '';
	if (isset($parse['scheme'])) {
		$url .= $parse['scheme'] . '://';
	} elseif (isset($parse['host'])) {
		$url .= '//';
	}
	if (isset($parse['user'])) {
		$url .= $parse['user'];
	}
	if (isset($parse['pass'])) {
		$url .= ':' . $parse['pass'];
	}
	if (isset($parse['user']) || isset($parse['pass'])) {
		$url .= '@';
	}
	if (isset($parse['host'])) {
		$url .= $parse['host'];
	}
	if (isset($parse['port'])) {
		$url .= ':'. $parse['port'];
	}
	if (isset($parse['path'])) {
		$url .= $parse['path'];
	} else {
		$url .= '/';
	}
	if (isset($parse['query']) && $parse['query'] !== '') {
		$url .= '?'. $parse['query'];
	}

	if (isset($parse['fragment'])) {
		$url .= '#'. $parse['fragment'];
	}
	return $url;
}





/**
*	毫秒时间戳
*
*	无参数
*
*	返回值当前时间戳毫秒
**/
function timems() {
	$r = explode(' ', microtime());
	$r = ($r[1] + $r [0]) * 1000;
	$r = explode('.', $r);
	return $r[0];
}



/**
*	自动添加 p 标签
*
*	1 参数 string
*
*	返回值 string
**/
function nl2p($string) {
 return str_replace('<p></p>', '', '<p>' . preg_replace('/\n|\r/', '</p>$0<p>', $string) . '</p>');
}


/**
 * 读取 get or post 的 value
 * @param  string
 * @param  string
 * @return string
 */
function r($name, $defaltValue = '') {
	return isset($_REQUEST[$name]) ? (is_array($_REQUEST[$name]) ? ($_REQUEST[$name] ? '1' : $defaltValue) : (string) $_REQUEST[$name]) : $defaltValue;
}

/**
 * 读取 get 的 value
 * @param  string 字段key
 * @param  string 默认值
 * @return string
 */
function g($name, $defaltValue = '') {
	return isset($_GET[$name]) ? (is_array($_GET[$name]) ? ($_GET[$name] ? '1' : $defaltValue) : (string) $_GET[$name]) : $defaltValue;
}

/**
 * 读取 post 的 value
 * @param  string
 * @param  string
 * @return string
 */
function p($name, $defaltValue = '') {
	return isset($_POST[$name]) ? (is_array($_POST[$name]) ? ($_POST[$name] ? '1' : $defaltValue) : (string) $_POST[$name]) : $defaltValue;
}

/**
 * 读取 COOKIE 的 value
 * @param  [type] $name        [description]
 * @param  string $defaltValue [description]
 * @return [type]              [description]
 */
function c($name, $defaltValue = '') {
	return isset($_COOKIE[$name]) ? (is_array($_COOKIE[$name]) ? ($_COOKIE[$name] ? '1' : $defaltValue) : (string) $_COOKIE[$name]) : $defaltValue;
}


/**
 * 读取 header 的 value
 * @param  [type] $name        [description]
 * @param  string $defaltValue [description]
 * @return [type]              [description]
 */
function h($name, $defaltValue = '') {
	$name = 'HTTP_'. strtoupper(strtr($name, '-', '_'));
	return isset($_SERVER[$name]) ? (string) $_SERVER[$name] : $defaltValue;
}


/**
*	二维数组 自定义优先级排序
*
*	1 参数 引用数组
*	2 参数 key 字段默认 priority
*	3 参数 排序方式
*
*	无返回值
**/
function prioritysort(array &$arrays, $key = 'priority', $asc = true) {
	$i = 0;
	$sorts = [];
	foreach ($arrays as $key => $value) {
		$sorts[] = [$key, $value, $i, is_object($value) ? (isset($value->$key) ? $value->$key : 0) : (is_array($value) && isset($value[$key]) ? $value[$key] : 0)];
		++$i;
	}
	$function = $asc ? 'uasort' : 'usort';
	$function($sorts, function($param1, $param2) {
		if ($param1[3] > $param2[3]) {
			return 1;
		}
		if ($param1[3] < $param2[3]) {
			return -1;
		}
		if ($param1[2] > $param2[2]) {
			return 1;
		}
		if ($param1[2] < $param2[2]) {
			return -1;
		}
		return 0;
	});
	$arrays = [];
	foreach ($sorts as $sort) {
		$arrays[$sort[0]] = $sort[1];
	}
	return true;
}




function mb_rand($length, $string = false) {
	$string = $string ? $string : '0123456789abcdefghijklmnopqrstuvwxyz';
	$strlen = mb_strlen($string) - 1;
	$r = '';
	for ($i = 0; $i < $length; $i++) {
		$r .= mb_substr($string, mt_rand(0, $strlen), 1);
	}
	return $r;
}



function get_redirect($redirects = [], $defaults = []) {
	$redirects = (array) $redirects;
	$defaults = $defaults ? (array) $defaults : [];

	if ($redirect = r('redirect')) {
		$redirects[] = $redirect;
	}

	if (in_array('cookie',  $redirects) && ($redirect = c('redirect'))) {
		$redirects[] = $redirect;
	}

	if (in_array('referer',  $redirects) && !($redirect = h('Referer'))) {
		$redirects[] = $redirect;
	}

	$redirects = array_diff($redirects, ['cookie', 'referer']);

	foreach ($redirects as $redirect) {
		if (!$redirect || !is_string($redirect)) {
			continue;
		}
		if (!preg_match('/^(https?\:)?\/\/\w+\.\w+/i', $redirect)) {
			if ($redirect{0} != '/') {
				$redirect = substr($path = explode('?', empty($_SERVER['REQUEST_URI']) ? '/' : $_SERVER['REQUEST_URI'])[0], -1, 1) == '/' ? $path . $redirect : dirname($path) .'/'. $redirect;
			}
			$redirect = '//'. h('Host') . '/' . ltrim($redirect, '/');
		}

		foreach ($defaults as $default) {
			if (domain_match($redirect, $default)) {
				return $redirect;
			}
		}
	}
	return reset($defaults);
}