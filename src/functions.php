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
		$results =[];
		foreach ($string as $key => $value) {
			if (is_array($value) || is_object($value)) {
				$value = parse_string($value);
			}
			$results[$key]= $value;
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



function to_string($string) {
	if (is_string($string)) {

	} elseif (is_array($string)) {
		$string = $string ? '1' : '';
	} elseif (is_object($string)) {
		$string = method_exists($string, '__toString') ? $string->__toString() : 'object';
	} else {
		$string = (string) $string;
	}
	return $string;
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



function studly($value) {
	static $cache = [];
	$key = $value;
	if (isset($cache[$key])) {
		return $cache[$key];
	}
	$value = ucwords(str_replace(['-', '_'], ' ', $value));
	return $cache[$key] = str_replace(' ', '', $value);
}


function snake($value, $delimiter = '_') {
	static $cache = [];
	$key = $value . $delimiter;

	if (isset($cache[$key])) {
	    return $cache[$key];
	}
	if (!ctype_lower($value)) {
		$value = preg_replace('/\s+/', '', $value);
		$value = strtolower(preg_replace('/(.)(?=[A-Z])/', '$1'.$delimiter, $value));
	}
	return $cache[$key] = $value;
}




function htmlencode($string) {
	return str_replace(['"', '\'', '<', '>'], ['&quot;', '&#039;', '&lt;', '&gt;'], $string);
}