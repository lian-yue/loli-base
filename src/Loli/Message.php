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
use ArrayIterator, IteratorAggregate,JsonSerializable;
interface_exists('Loli\RouteInterface') || exit;
/*
消息模块
1000 以前是系统预留的

001	- 099 系统错误代码

001 ＝ 基本错误
002 ＝ HTTP错误
003 ＝ 权限错误(文件权限什么的)
004 ＝ 缓存错误
005 ＝ 数据库错误
006 ＝ 储存错误
007 ＝ 通讯错误
099 ＝ Exception错误


200 － 399 执行成功 并且要设置http状态码的
400 － 599 ＝ 执行失败 并且 要设置 http 状态码的

*/
class Message extends Exception implements IteratorAggregate, RouteInterface, JsonSerializable{
	const NOTICE = 1;
	const WARNING = 2;
	const ERROR = 3;

	protected $type = 1;

	protected $code = 200;

	protected $args = [];

	protected $data = [];

	protected $redirect = false;

	protected $refresh = 3;
	protected $hosts = [];

	public function __construct($message = [], $type = self::NOTICE, $data = [], $redirect = true, $refresh = 3, Message $previous = NULL) {
		$this->hosts = empty($_SERVER['LOLI']['MESSAGE']['hosts']) ? [] : (array) $_SERVER['LOLI']['MESSAGE']['hosts'];

		// previous　变量自动缩进
		foreach(['type' => self::NOTICE, 'data' => [], 'redirect' => false, 'refresh' => 3] as $key => $value) {
			if ($$key instanceof Message) {
				$previous = $$key;
				$$key = $value;
				break;
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
				$this->type = self::NOTICE;
		}



		$message = $message && $message !== true && $message !== 1 ? (array) $message : [$this->type === self::ERROR ? 500 : 200];

		// code
		$code = (int) reset($message);

		// data
		$this->data = (array) $data;

		// args
		$this->args = $message;
		unset($this->args[key($this->args)]);


		$this->redirect = $redirect;

		// refresh 刷新
		$this->redirect = isset($data['redirect']) ? $data['redirect'] : $redirect;
		if (!$this->redirect || !is_string($this->redirect)) {
			$this->redirect = (bool) $this->redirect;
		}
		$this->refresh = (int) (isset($data['refresh']) ? $data['refresh'] : $refresh);


		// 注册父级
		parent::__construct($code . '.' . $this->type, $code, $previous);
	}

	public function route(Route &$route) {
		$this->message = $route->localize->translate([$this->code] + $this->args, ['messages']);
		$whiteList = !isset($this->data['redirect']);
		if ($this->redirect) {
			if ($this->redirect !== true) {

			} elseif ($this->redirect = $route->request->getParam('redirect', '')) {

			} elseif ($this->redirect = $route->request->getCookie('redirect', '')) {

			} elseif ($this->redirect = $route->request->getHeader('Referer')) {

			} else {
				$this->redirect = '//'. $route->request->getHeader('Host');
			}

			if ($whiteList) {
				$parse = parse_url($this->redirect);
				if (!empty($parse['scheme']) && !in_array($parse['scheme'], ['http', 'https'], true)) {
					$parse = [];
				}

				if ((!empty($parse['scheme']) || !empty($parse['host'])) && (empty($parse['host']) || !preg_match('/(^|\.)('. implode('|', array_map(function($host){ return preg_quote($host, '/'); }, $this->hosts)) .')$/i', $parse['host']))) {
					$parse = [];
				}

				if (isset($parse['user']) || isset($parse['pass'])) {
					$parse = [];
				}

				if (empty($parse['host']) && (stripos($this->redirect, ':') !== false || stripos($this->redirect, '&#') !== false || stripos($this->redirect, ';') !== false)) {
					$parse = [];
				}
				if (!$parse) {
					$parse['host'] = reset($this->hosts);
				}
				$parse['query'] = empty($parse['query']) ? [] : parse_string($parse['query']);
				$parse['query']['_r'] = mt_rand();
				$parse['query']['_message'] = $this->code . '.' . $this->type;
				$parse['query'] = merge_string($parse['query']);
				$this->redirect = merge_url($parse);
			}
		}
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

	public function getIterator() {
		return new ArrayIterator($this->jsonSerialize());
	}

	public function jsonSerialize() {
		return ['message' => $this->getMessage(), 'code' => $this->getCode(), 'type' => $this->getType(), 'args' => $this->getArgs()];
    }
}