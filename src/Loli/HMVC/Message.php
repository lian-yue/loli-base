<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-16 13:21:40
/*	Updated: UTC 2015-02-24 05:49:18
/*
/* ************************************************************************** */
namespace Loli\HMVC;
use Iterator, Loli\Exception, Loli\Lang;
/*
消息模块
 1000 以前是系统预留的
 1 ＝ 执行成功

 2 ＝ 系统基本错误
 3 ＝ 系统权限错误(文件权限什么的)
 4 ＝ 系统缓存错误
 5 ＝ 系统数据库错误
 6 ＝ 系统储存错误
 7 ＝ 其他通讯错误
 9 ＝ Exception错误


 200 － 399 执行成功 并且要设置http状态码的
 400 － 599 ＝ 执行失败 并且 要设置 http 状态码的

 */


class Message extends Exception implements Iterator{
	protected $code = 200;
	protected $data;
	protected $redirect;
	protected $refresh;
	protected $args;
	protected $title = 'Messages';

	protected $useQuery = 'messages';
	protected $notQuery = ['messages', 'errors'];

	protected $hosts = ['qq.com'];

	protected $results = [];
	public function __construct($message = [], $data = [], $redirect = true, $refresh = 3, Message $previous = NULL) {
		$this->hosts = empty($_SERVER['LOLI']['MESSAGE']['hosts']) ? [] : (array) $_SERVER['LOLI']['MESSAGE']['hosts'];


		$message = $message  && $message !== true && $message !== 1 ? (array) $message : [$this->code];

		// previous　变量自动缩进
		foreach(['data' => [], 'redirect' => false, 'refresh' => 3] as $key => $value) {
			if ($$key instanceof Message) {
				$previous = $$key;
				$$key = $value;
				break;
			}
		}

		// data 换成数组
		$data = (array) $data;



		// args
		$this->args = $message;
		reset($this->args);
		unset($this->args[key($this->args)]);


		// code
		$code = (int) reset($message);

		// message
		$message = self::lang($message);


		// 注册父级
		parent::__construct($message, $code, $previous);

		// 循环所有消息
		$message = $this;
		do {
			$this->results[$message->getCode()] = $message;
		} while ($message = $message->getPrevious());
		$this->results = array_reverse($this->results, true);


		// 重定向
		if (isset($data['redirect']) && (is_string($data['redirect']) || is_bool($data['redirect']))) {
			$redirect = $data['redirect'];
		} else {
			if (isset($data['redirect'])) {
				$redirect = $data['redirect'];
			}
			if ($redirect === false || $redirect === true || $redirect === NULL) {
				$redirect = (bool) $redirect;
			} else {
				$redirect = get_redirect($redirect, array_map(function($host){ return '//' . $host;}, $this->hosts));
				$isEmptyScheme = substr($redirect, 0, 2) == '//';
				$array = parse_url($isEmptyScheme ? 'http:' . $redirect : $redirect);
				if ($isEmptyScheme) {
					unset($array['scheme']);
				}
				$array['query'] = empty($array['query']) ? [] : parse_string($array['query']);
				foreach ($this->notQuery as $query) {
					unset($array['query'][$query]);
				}
				$array['query'][$this->useQuery] = [];
				foreach ($this->results as $value) {
					$array['query'][$this->useQuery][] = $value->getCode();
				}
				$array['query']['r'] = mt_rand();
				$array['query'] = merge_string($array['query']);

				$redirect = merge_url($array);
			}
		}
		$this->redirect = $redirect;

		// refresh 刷新
		$this->refresh = (int) (isset($data['refresh']) ? $data['refresh'] : $refresh);

		// data
		$this->data = $data;

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

	public function getTitle() {
		$title = $this->title;
		$message = $this;
		do {
			if (($data = $message->getData()) && !empty($data['title'])) {
				$title = $data['title'];
			}
		} while ($message = $message->getPrevious());
		return self::lang($title);
	}


	public static function lang($message) {
		return Lang::get($message, ['message', 'default']);
	}


	public function rewind() {
		reset($this->results);
	}

	public function current() {
		return current($this->results);
	}

	public function key() {
		return key($this->results);
	}

	public function next() {
		return next($this->results);
	}

	public function valid() {
		$key = key($this->results);
		return ($key !== NULL && $key !== false);
	}
}