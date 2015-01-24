<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-21 09:17:20
/*	Updated: UTC 2015-01-24 12:37:03
/*
/* ************************************************************************** */
namespace Loli;
class Message {
	private static $_errors = [];

	// 允许重定向的 url
	public static $to = [];

	/**
	 * 添加消息 添加错误
	 * @param [type] $message 消息 和语言一样的方式
	 * @param [type] $args    附加参数
	 */
	public static function add($error, $args = []) {
		$error = (array) $error;
		$code = reset($error);
		return empty(self::$_errors[$code]) && self::set($error, $args);
	}

	/**
	 * 写入消息 写入错误
	 * @param [type] $error 消息 和语言一样的方式
	 * @param [type] $args    附加参数
	 */
	public static function set($error, $args = []) {
		$error = (array) $error;
		$code = reset($error);
		self::$_errors[$code] = [$error, $args];
		return true;
	}

	/**
	 * 判断消息错误
	 * @param  [type]  $codes 消息信息数组 留空 代表判断是否有消息
	 * @return boolean 是否有那消息
	 */
	public static function has($codes = []) {
		if (!$codes) {
			return !empty(self::$_errors);
		}
		foreach ((array) $codes as $code) {
			if (!empty(self::$_errors[$code])) {
				return true;
			}
		}
		return false;
	}


	/**
	 * 移除一个消息
	 * @param  [type] $code 移除的code
	 * @return [type]     boolean
	 */
	public static function remove($code) {
		if (empty(self::$_errors[$code])) {
			return false;
		}
		unset(self::$_errors[$code]);
		return true;
	}


	/**
	 * 清空消息
	 * @return boolean
	 */
	public static function clear() {
		self::$_errors = [];
		return true;
	}



	/**
	 * [run 执行消息]
	 * @param  [type]  $message 消息
	 * @param  [type]  $args    附加参数
	 * @param  boolean $to      重定向地址
	 * @return [type]           无返回值
	 */
	public static function run($message = [], $args = [], $to = []) {
		http_no_cache();
		@ob_clean();

		// 消息
		$message = $message ? (array) $message : (self::$_errors? ['Error Messages'] : [1]);
		if ($message) {
			$arrays['message'] = Lang::get($message, ['message', 'default']);
		}


		// 错误
		foreach (self::$_errors as $code => $value) {
			$vars = $value[0];
			reset($vars);
			unset($vars[key($vars)]);
			$arrays['errors'][$code] = ['code' => $code, 'message' => Lang::get($value[0], ['message', 'default']), 'vars' => $vars];
			$arrays += $value[1];
		}
		$arrays += $args;



		// to 地址
		if (!isset($arrays['to']) && $to !== false) {
			if (!$to && !empty($arrays['errors']) && !Ajax::$is) {
				$to = 'javascript:history.back()';
			} else {
				$to = $to ? (array) $to : ['referer', 'http' . (is_ssl() ? 's' : '') .'://'. $_SERVER['HTTP_HOST']];
				$to = to($to, self::$to);
				if (substr($to, 0, 2) == '//') {
					$to = (is_ssl() ? 'https:' : 'http:') . $to;
				}
				$parse = parse_url($to);
				$parse['query'] = empty($parse['query']) ? [] : parse_string($parse['query']);
				$parse['query']['message'] = reset($message);
				$parse['query']['errors'] = empty($arrays['errors']) ? null : array_keys($arrays['errors']);
				$parse['query']['ajax'] = null;
				$parse['query']['r'] = mt_rand();
				$parse['query'] = merge_string($parse['query']);
				$to = merge_url($parse);
			}
			$arrays['to'] = $to;
		}



		// header 头
		if (!empty($arrays['errors'])) {
			@header('X-Message-Errors: ' . json_encode(array_keys($arrays['errors'])));
		}
		@header('X-Message: ' . reset($message));



		// ajax
		Ajax::$is && exit(Ajax::get($arrays));



		// 无错误
		if (!self::$_errors) {
			@header('location: '. $arrays['to']);
			exit;
		}


		// 错误消息
		$e = '';
		$e .= '<!DOCTYPE html>';
		$e .= '<html xmlns="http://www.w3.org/1999/xhtml">';
		$e .= '<head>';
		$e .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>';
		$e .= '<meta name="robots" content="noindex,nofollow"/>';
		if (is_mobile()) {
			$e .= '<meta name="viewport" content="width=device-width" />';
		}
		$e .= "<title>" . $arrays['message'] . "</title>";
		$e .= '<style type="text/css">';
		$e .= 'html{background: #f9f9f9;}';
		$e .= 'body,input,button,select,textarea{font: medium/1.8em Tahoma,Helvetica,arial,sans-serif;color:#444;}';
		$e .= 'body,div,ul,ol,li,dl,dd,p,h1,h2,h3,h4,h5,h6,form,fieldset,button,input,a,hr{margin:0;padding:0;}';
		if (is_mobile()) {
			$e .= '#errors{background: #fff;color: #444;font-family: sans-serif;width: 70%;height:70%;margin: 10% auto 0 auto;padding: 2.0em;border: 1px solid #dfdfdf;}';
		} else {
			$e .= '#errors{background: #fff;color: #444;font-family: sans-serif;width: 700px;margin: 10% auto 0 auto;padding: 2.5em;border: 1px solid #dfdfdf;}';
		}
		$e .= '#errors .to{margin-top:0.4em;}';
		$e .= '#errors .to a{text-decoration: none;font-weight: bold;color: #369;}';
		$e .= '#errors .to a:hover{text-decoration: underline;}';
		$e .= '#errors .to a:active{color: #D54E21;}';
		$e .= '</style>';
		$e .= '</head>';
		$e .= '<body>';
		$e .= '<div id="errors">';
		foreach ($arrays['errors'] as $code => $value) {
			is_int($code) && $code >= 400 && $code < 600 && http_response_code($code);
			$e .= '<p>' . $value['message'] . '</p>';
		}
		if (!empty($arrays['to'])) {
			$e .= '<p id="to"><a href="'. $arrays['to'] .'">'. Lang::get('Return', ['message', 'default']). '</a></p>';
		}
		$e .= "</div>";
		$e .= "</body>";
		$e .= "</html>";
		exit($e);
	}
}
Message::$to = empty($_SERVER['LOLI']['MESSAGE']['to']) ? [] : (array) $_SERVER['LOLI']['MESSAGE']['to'];
