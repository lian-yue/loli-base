<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-22 05:38:02
/*
/* ************************************************************************** */
namespace Loli;
use IteratorAggregate;
use JsonSerializable;

/*
消息模块
1000 以前是系统预留的

1	- 99 系统错误代码(%s)

1 ＝ 基本错误(%s)
2 ＝ HTTP错误(%s)
3 ＝ 权限错误(文件权限什么的)(%s)
4 ＝ 缓存错误(%s)
5 ＝ 数据库错误(%s)
6 ＝ 储存错误(%s)
7 ＝ 通讯错误(%s)
90 ＝ 无权限(%s)
99 ＝ Exception错误(%s)


200 － 399 执行成功 并且要设置http状态码的
400 － 599 ＝ 执行失败 并且 要设置 http 状态码的


1000-1099  验证消息
1000-1099 默认状态码


 组  表(模块) 字段 状态
 00   00     00  00

组 不能 未 0

*/
class Message extends Exception implements IteratorAggregate, JsonSerializable{
	const SUCCESS = 1;
	const WARNING = 2;
	const ERROR = 3;

	protected $type = 1;

	protected $code = 200;

	protected $args = [];

	protected $data = [];

	protected $redirect = false;

	protected $refresh = 3;

	protected $hosts = [];

	public function __construct($message = [], $type = self::SUCCESS, $data = [], $redirect = true, $refresh = 3, Message $previous = NULL) {
		$this->hosts = empty($_SERVER['LOLI']['message']['hosts']) ? [] : (array) $_SERVER['LOLI']['message']['hosts'];

		// previous　变量自动缩进
		foreach(['type' => self::SUCCESS, 'data' => [], 'redirect' => false, 'refresh' => 3] as $key => $value) {
			if ($$key instanceof Message) {
				$previous = $$key;
				$$key = $value;
				break;
			}
			if ($$key === NULL) {
				$$key = $value;
			}
		}

		switch ($type) {
			case self::ERROR:
				$this->type = self::ERROR;
				break;
			case self::WARNING:
				$this->type = self::WARNING;
				break;
			default:
				$this->type = self::SUCCESS;
		}



		$message = $message && $message !== true && $message !== 1 ? (array) $message : [$this->type === self::ERROR ? 500 : 200];

		// code
		$code = (int) reset($message);

		// data
		$this->data = $data ? (array) $data : [];

		// args
		$this->args = $message;
		unset($this->args[key($this->args)]);


		$message = self::translate([$code] + $this->args);

		// 注册父级
		parent::__construct($message, $code, $previous);

		// refresh 刷新
		$this->setRefresh(isset($data['refresh']) ? $data['refresh'] : $refresh);

		// redirect
		$this->setRedirect($redirect = isset($data['redirect']) ? $data['redirect'] : $redirect, isset($data['redirect']));

	}

	public function hasError() {
		$message = $this;
		do {
			if ($message->getType() === self::ERROR) {
				return true;
			}
		} while ($message = $message->getPrevious());
		return false;
	}

	public function hasWarning() {
		$message = $this;
		do {
			if ($message->getType() === self::WARNING) {
				return true;
			}
		} while ($message = $message->getPrevious());
		return false;
	}



	public function hasCode($codes = []) {
		if (!$codes) {
			return true;
		}
		$codes = (array) $codes;
		$message = $this;
		do {
			if (in_array($message->getCode(), $codes)) {
				return true;
			}
		} while ($message = $message->getPrevious());
		return false;
	}


	public function getType() {
		return $this->type;
	}


	public function getArgs() {
		return $this->args;
	}

	public function getRedirect() {
		return $this->redirect;
	}

	public function getRefresh() {
		return $this->refresh;
	}

	public function getData() {
		return $this->data;
	}

	public function setData(array $data) {
		$this->data = $data;
		return $this;
	}

	public function setRedirect($redirect, $whiteList = false) {


		if ($redirect) {
			$request = Route::request();
			if (!$redirect instanceof URL) {
				if (is_string($redirect) && $redirect !== '1' && $redirect !== 'true') {

				} elseif (is_object($redirect)) {
					$redirect = (string) $redirect;
				} elseif ($redirect = $request->getParam('redirect', '')) {

				} elseif ($redirect = $request->getCookie('redirect', '')) {

				} elseif ($redirect = $request->getHeader('Referer')) {

				} else {
					$redirect = '//'. $request->getHeader('Host');
				}
				$redirect = new URL($redirect);
			}


			if (!$whiteList) {
				if ($redirect->scheme && !in_array($redirect->scheme, ['http', 'https'], true)) {
					// 协议不是 http https
					$error = true;
				} elseif ($redirect->host && !preg_match('/(^|\.)('. implode('|', array_map(function($host){ return preg_quote($host, '/'); }, $this->hosts)) .')$/i', $redirect->host)) {
					// host 无效
					$error = true;
				} elseif ($redirect->user || $redirect->pass) {
					// 带用户名和密码
					$error = true;
				} elseif (stripos($redirect->path, ':') !== false || stripos($redirect->path, ';') !== false) {
					$error = true;
				}
				if (isset($error)) {
					$redirect = new URL('//' . $request->getHeader('Host'));
				}
				$redirect->query('_r', mt_rand());
				$redirect->query('_message', $this->code . '.' . $this->type);
			}
		} else {
			$redirect = false;
		}
		$this->redirect = $redirect;
		return $this;
	}

	public function setRefresh($refresh) {
		$this->refresh = $refresh;
		return $this;
	}

	public function getIterator() {
		return new ArrayIterator($this->jsonSerialize());
	}

	public function jsonSerialize() {
		return ['message' => $this->getMessage(), 'code' => $this->getCode(), 'type' => $this->getType(), 'args' => $this->getArgs()];
    }

    public function __toString() {
    	switch ($this->type) {
    		case self::ERROR:
    			$type = 'error';
    			break;
			case self::WARNING:
    			$type = 'warning';
    			break;
    		default:
    			$type = 'notice';
    	}
		return '<p class="message message-type-'. $this->type .'message-'. $type .'">'. $this->getMessage() .'</p>';
    }


	public static function translate($text, $original = true) {
		if ($original === true && is_array($text) && is_int($code = reset($text)) && $code >= 1000000) {
			$original = Language::translate([1000 + substr($code, -2, 2)] + $text, ['message'], $code);
		}
		return Language::translate($text, ['message'], $original);
	}
}
