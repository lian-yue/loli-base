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
/*	Updated: UTC 2015-02-12 05:42:57
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
function parse_string($s) {
	if (is_array($s)) {
		return $s;
	}
	parse_str($s, $r);
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
*	1 参数 str
*
*	返回值 str
**/
function nl2p($str) {
 return str_replace('<p></p>', '', '<p>' . preg_replace('#\n|\r#', '</p>$0<p>', $str) . '</p>');
}


function r($name, $defaltValue = '') {
	return isset($_REQUEST[$name]) ? (is_array($_REQUEST[$name]) ? ($_REQUEST[$name] ? '1' : $defaltValue) : (string) $_REQUEST[$name]) : $defaltValue;
}


function g($name, $defaltValue = '') {
	return isset($_GET[$name]) ? (is_array($_GET[$name]) ? ($_GET[$name] ? '1' : $defaltValue) : (string) $_GET[$name]) : $defaltValue;
}


function p($name, $defaltValue = '') {
	return isset($_POST[$name]) ? (is_array($_POST[$name]) ? ($_POST[$name] ? '1' : $defaltValue) : (string) $_POST[$name]) : $defaltValue;
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
function prioritysort(array &$arr, $key = 'priority', $asc = true) {
	$a = [];
	$i = 0;
	foreach ($arr as $k => $v) {
		$a[] = ['k' => $k, 'v' => $v, 'i' => $i, 's' => empty($v[$key]) ? 0 : $v[$key]];
		$i++;
	}
	$function = $asc ? 'uasort' : 'usort';
	$function($a, 'prioritysortcall');
	$arr = [];
	foreach ($a as $v) {
		$arr[$v['k']] = $v['v'];
	}
	return true;
}

function prioritysortcall($a, $b) {
	if ($a['s'] > $b['s']) {
		return 1;
	}
	if ($a['s'] < $b['s']) {
		return -1;
	}
	if ($a['i'] > $b['i']) {
		return 1;
	}
	if ($a['i'] < $b['i']) {
		return -1;
	}
	return 0;
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



function add_call($key, $call, $priority = 10) {
	global $_call_data, $_call_ksort;
	$id = call_id($call);
	$_call_data[$key][$priority][$id] = $call;
	$_call_ksort[$key] = false;
	return true;
}


function has_call($key, $call, $priority = 10) {
	global $_call;
	if (empty($_call_data[$key][$priority])) {
		return false;
	}
	return !empty($_call_data[$key][$priority][call_id($call)]);
}


function did_call($key) {
	global $_call_data, $_call_did;
	return empty($_call_did[$key]) ? 0 : $_call_did[$key];
}

function remove_call($key, $call, $priority = 10) {
	global $_call_data, $_call_ksort;
	$id = call_id($call);
	if (empty($_call_data[$key][$priority][$id])) {
		return false;
	}
	unset($_call_data[$key][$priority][$id]);
	$_call_ksort[$key] = false;
	return true;
}

function remove_all_call($key, $priority = false) {
	global $_call;
	if (empty($_call_data[$key])) {
		return true;
	}
	if (false !== $priority) {
		if (isset($_call_data[$key][$priority])) {
			unset($_call_data[$key][$priority]);
		}
	} else {
		unset($_call_data[$key]);
	}
	$_call_ksort[$key] = false;
	return true;
}


function get_call($key, $value) {
	global $_call_data, $_call_ksort, $_call_did;
	if (empty($_call_did[$key])) {
		$_call_did[$key] = 0;
	}
	++$_call_did[$key];

	if (empty($_call_data[$key])) {
		return $value;
	}

	if ( empty($_call_ksort[$key])) {
		ksort($_call_data[$key]);
		$_call_ksort[$key] = true;
	}

	$args = func_get_args();
	array_shift($args);
	$args[0] = $value;
	foreach ($_call_data[$key] as $v) {
		foreach($v as $call) {
			$args[0] = call_user_func_array($call, $args);
		}
	};

	return $args[0];
}

function get_array_call($key, $args) {
	global $_call_data, $_call_ksort, $_call_did;
	if (empty($_call_did[$key])) {
		$_call_did[$key] = 0;
	}
	++$_call_did[$key];

	if (empty($_call_data[$key])) {
		return $args[0];
	}

	if ( empty($_call_ksort[$key])) {
		ksort($_call_data[$key]);
		$_call_ksort[$key] = true;
	}
	foreach ($_call_data[$key] as $v) {
		foreach($v as $call) {
			$args[0] = call_user_func_array($call, $args);
		}
	}

	return $args[0];
}


function do_call($key) {
	global $_call_data, $_call_ksort, $_call_did;
	if (empty($_call_did[$key])) {
		$_call_did[$key] = 0;
	}
	++$_call_did[$key];

	if (empty($_call_data[$key])) {
		return;
	}

	if ( empty($_call_ksort[$key])) {
		ksort($_call_data[$key]);
		$_call_ksort[$key] = true;
	}

	$args = func_get_args();
	array_shift($args);
	foreach ($_call_data[$key] as $v) {
		foreach($v as $call) {
			call_user_func_array($call, $args);
		}
	};
}


function do_array_call($key, $args) {
	global $_call_data, $_call_ksort, $_call_did;
	if (empty($_call_did[$key])) {
		$_call_did[$key] = 0;
	}
	++$_call_did[$key];

	if (empty($_call_data[$key])) {
		return;
	}

	if ( empty($_call_ksort[$key])) {
		ksort($_call_data[$key]);
		$_call_ksort[$key] = true;
	}
	foreach ($_call_data[$key] as $v) {
		foreach($v as $call) {
			call_user_func_array($call, $args);
		}
	};
}




function call_id($call) {
	if (is_string($call)) {
		return $call;
	}
	if (is_object($call)) {
		return spl_object_hash($call);
	}
	if (is_object($call[0])) {
		return spl_object_hash($call[0]) . $call[1];
	}
	return $call[0].$call[1];
}