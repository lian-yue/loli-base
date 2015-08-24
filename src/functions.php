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
		$results = (array) $string;
		foreach ($results as &$value) {
			if (is_array($value) || is_object($value)) {
				$value = parse_string($value);
			}
		}
	} else {
		parse_str($string, $results);
	}
	return $results;
}


function merge_string($array) {
	if (!is_array($array) && !is_object($array)) {
		return (string) $array;
	}
	return http_build_query(to_array($array), NULL, '&');
}


function to_array($array) {
	$results = [];
	foreach ((is_array($array) || is_object($array) ? $array : (array)  $array) as $key => $value) {
		if (is_array($value) || is_object($value)) {
			$results[$key] = to_array($value);
		} else {
			$results[$key] = $value;
		}
	}
	return $results;
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
