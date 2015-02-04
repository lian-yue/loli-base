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
/*	Updated: UTC 2015-02-02 14:47:55
/*
/* ************************************************************************** */
namespace Loli;
class Message {
	private static $_errors = [];

	// 允许重定向的 url
	public static $redirect = [];

	/**
	 * 添加消息 添加错误
	 * @param [type] $message 消息 和语言一样的方式
	 * @param [type] $data    附加数据
	 */
	public static function add($error, $data = []) {
		$error = (array) $error;
		$code = reset($error);
		return empty(self::$_errors[$code]) && self::set($error, $data);
	}

	/**
	 * 写入消息 写入错误
	 * @param [type] $error 消息 和语言一样的方式
	 * @param [type] $data    附加数据
	 */
	public static function set($error, $data = []) {
		$error = (array) $error;
		$code = reset($error);
		self::$_errors[$code] = [$error, $data];
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
	 * @param  [type]  $data    附加数据
	 * @param  boolean $redirect      重定向地址
	 * @return [type]           无返回值
	 */
	public static function run($message = [], $data = [], $redirect = []) {
		http_no_cache();
		@ob_clean();

		// 消息
		$message = $message && ($message !== true || !self::$_errors) ? (array) $message : (self::$_errors? ['Error Messages'] : [1]);
		if ($message) {
			$arrays['message'] = Lang::get($message, ['message', 'default']);
		}


		// 错误
		foreach (self::$_errors as $code => $value) {
			$args = $value[0];
			reset($args);
			unset($args[key($args)]);
			$arrays['errors'][$code] = ['code' => $code, 'message' => Lang::get($value[0], ['message', 'default']), 'args' => $args];
			$arrays += $value[1];
		}
		$arrays += $data;



		// redirect 地址
		if (!isset($arrays['redirect']) && $redirect !== false) {
			if (!$redirect && !empty($arrays['errors']) && !Ajax::$is) {
				$redirect = 'javascript:history.back()';
			} else {
				$redirect = $redirect ? (array) $redirect : ['referer', 'http' . (is_ssl() ? 's' : '') .'://'. $_SERVER['HTTP_HOST']];
				$redirect = redirect($redirect, self::$redirect);
				if (substr($redirect, 0, 2) == '//') {
					$redirect = (is_ssl() ? 'https:' : 'http:') . $redirect;
				}
				$parse = parse_url($redirect);
				$parse['query'] = empty($parse['query']) ? [] : parse_string($parse['query']);
				$parse['query']['message'] = reset($message);
				$parse['query']['errors'] = empty($arrays['errors']) ? null : array_keys($arrays['errors']);
				$parse['query']['ajax'] = null;
				$parse['query']['r'] = mt_rand();
				$parse['query'] = merge_string($parse['query']);
				$redirect = merge_url($parse);
			}
			$arrays['redirect'] = $redirect;
		}



		// header 头
		if (!empty($arrays['errors'])) {
			header('X-Message-Errors: ' . json_encode(array_keys($arrays['errors'])));
		}
		header('X-Message: ' . reset($message));



		// ajax
		Ajax::$is && exit(Ajax::get($arrays));



		// 无错误
		if (!self::$_errors || (!empty($arrays['redirect']) && $arrays['redirect'] != 'javascript:history.back()')) {
			header('location: '. $arrays['redirect']);
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
		$e .= '#redirect{margin-top:0.4em;}';
		$e .= '#redirect a{text-decoration: none;font-weight: bold;color: #369;}';
		$e .= '#redirect a:hover{text-decoration: underline;}';
		$e .= '#redirect a:active{color: #D54E21;}';
		$e .= '</style>';
		$e .= '</head>';
		$e .= '<body>';
		$e .= '<div id="errors">';
		foreach ($arrays['errors'] as $code => $value) {
			is_int($code) && $code >= 400 && $code < 600 && http_response_code($code);
			$e .= '<p>' . $value['message'] . '</p>';
		}
		if (!empty($arrays['redirect'])) {
			$e .= '<p id="redirect"><a href="'. $arrays['redirect'] .'">'. Lang::get('Return', ['message', 'default']). '</a></p>';
		}
		$e .= "</div>";
		$e .= "</body>";
		$e .= "</html>";
		exit($e);
	}
}
Message::$redirect = empty($_SERVER['LOLI']['MESSAGE']['redirect']) ? [] : (array) $_SERVER['LOLI']['MESSAGE']['redirect'];