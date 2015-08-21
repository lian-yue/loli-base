<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-19 08:59:51
/*
/* ************************************************************************** */



function parse_string($string) {
	if (is_array($string) || is_object($string)) {
		$res = (array) $string;
		foreach ($res as &$value) {
			if (is_array($value) || is_object($value)) {
				$value = parse_string($value);
			}
		}
	} else {
		parse_str($string, $res);
	}
	return $res;
}


function merge_string($a) {
	if (!is_array($a) && !is_object($a)) {
		return (string) $a;
	}
	return http_build_query(to_array($a), NULL, '&');
}


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




