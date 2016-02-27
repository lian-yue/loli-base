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
function to_string($string) {
	if (is_string($string)) {

	} elseif (is_array($string)) {
		$string = $string ? 'Array' : '';
	} elseif (is_object($string)) {
		$string = method_exists($string, '__toString') ? $string->__toString() : 'Object';
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


function format_size($value, $precision = 2) {
	if ($size > 1024 * 1024 * 1024 * 1024) {
		return round($size / 1024 / 1024, $precision).' TB';
	} elseif ($size > 1024 * 1024 * 1024) {
		return round($size / 1024 / 1024, $precision).' GB';
	} elseif ($size > 1024 * 1024) {
		return round($size / 1024 / 1024, $precision).' MB';
	} elseif ($size > 1024) {
		return round($size / 1024, $precision).' KB';
	}
	return intval($size) . ' B';
}


function parse_size($value) {
	$value = trim($value);
	$value = strtoupper($value);
	switch (substr($value, -2, 2)) {
		case 'TB':
			return trim(substr($value, 0, -2)) * 1024 * 1024 * 1024 * 1024;
			break;
		case 'GB':
			return trim(substr($value, 0, -2)) * 1024 * 1024 * 1024;
			break;
		case 'MB':
			return trim(substr($value, 0, -2)) * 1024 * 1024;
			break;
		case 'KB':
			return trim(substr($value, 0, -2)) * 1024;
			break;
		default:
			if (substr($value, -1, 1) === 'B') {
				$value = substr($value, 0, -1);
			} elseif (substr($value, -5, 5) === 'BYTES') {
				$value = substr($value, 0, -5);
			}
			return trim($value) * 1;
	}
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
	return $cache[$key] = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
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
