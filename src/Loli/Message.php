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



use Psr\Http\Message\UriInterface;

use GuzzleHttp\Psr7\Uri as PsrUri;

/*


50 = 验证器错误 {name} {title} {type}

200 － 399 执行成功 并且要设置http状态码的

400 － 599 = 执行失败 并且 要设置 http 状态码的
*/
class Message extends \RuntimeException implements IteratorAggregate, JsonSerializable{

	protected $args = [];

	protected $data = [];

	protected $redirect = false;

	protected $refresh = 3;

	public function __construct($message = [], $code = 200, $data = [], $redirect = true, $refresh = 3, Message $previous = null) {

		// previous　变量自动缩进
		foreach(['code' => 200, 'data' => [], 'redirect' => false, 'refresh' => 3] as $key => $value) {
			if ($$key instanceof Message) {
				$previous = $$key;
				$$key = $value;
				break;
			}
			if ($$key === null) {
				$$key = $value;
			}
		}

		// data
		$this->data = $data ? (array) $data : [];

		// args
		$this->args = is_array($message) ? $message : ['message' => $message];

		// 注册父级
		parent::__construct(self::translate($message), $code, $previous);

		// refresh 刷新
		$this->setRefresh(isset($data['refresh']) ? $data['refresh'] : $refresh);

		// redirect
		$this->setRedirect(isset($data['redirect']) ? $data['redirect'] : $redirect, isset($data['redirect']));

	}

	public function getErrors() {
		$errors = [];
		$message = $this;
		do {
			if (($message->getCode() > 0 && $message->getCode() < 100) || ($message->getCode() >= 400 && $message->getCode() < 600)) {
				$errors[] = $message->getCode();
			}
		} while ($message = $message->getPrevious());
		return $errors;
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

	public function setData(array $data) {
		$this->data = $data;
		return $this;
	}




	public function getParsedRedirect($redirect) {
		if (!$redirect) {
			return false;
		}

		if ($redirect instanceof UriInterface) {
			return clone $redirect;
		}

		if (is_object($redirect)) {
			return new PsrUri((string) $redirect);
		}

		$request = Route::request();
		if ($parsedbody = $request->getParsedBody()) {
			if (is_array($parsedbody) && !empty($parsedbody['redirect'])) {
				return new PsrUri($parsedbody['redirect']);
			}
			if (is_object($parsedbody) && !empty($parsedbody->redirect)) {
				return new PsrUri($parsedbody->redirect);
			}
		}

		if (($params = $request->getQueryParams()) && !empty($params['redirect'])) {
			return new PsrUri($params['redirect']);
		}

		if (($params = $request->getCookieParams()) && !empty($params['redirect'])) {
			return new PsrUri($params['redirect']);
		}

		if ($referer = $request->getHeaderLine('Referer')) {
			return new PsrUri($referer);
		}

		return new PsrUri('//'. $request->getUri()->getHost() . '/');
	}

	public function setRedirect($redirect, $whiteList = false) {
		if ($redirect) {
			try {
				$redirect = $this->getParsedRedirect($redirect);
			} catch (\Exception $e) {
				$redirect = new PsrUri('//'. Route::request()->getUri()->getHost() . '/');
			}


			if (!$whiteList) {

				if ($redirect->getScheme() && !in_array($redirect->getScheme(), ['http', 'https'], true)) {
					// 协议不是 http https
					$error = true;
				} elseif ($redirect->getHost() && !preg_match('/(^|\.)('. implode('|', array_map(function($host){ return preg_quote($host, '/'); }, configure('whitelist_hosts', []))) .')$/i', $redirect->getHost())) {
					// host 无效
					$error = true;
				} elseif ($redirect->getUserInfo()) {
					// 带用户名和密码
					$error = true;
				} elseif (stripos($redirect->getPath(), ':') !== false || stripos($redirect->getPath(), ';') !== false) {
					$error = true;
				}
				if (isset($error)) {
					$redirect = new PsrUri('//'. Route::request()->getUri()->getHost() . '/');
				}
				if ($redirect->getQuery()) {
					parse_str($redirect->getQuery(), $queryParams);
				} else {
					$queryParams = [];
				}
				unset($queryParams['_r'], $queryParams['_message'], $queryParams['_message_code']);
				$queryParams['_r'] = mt_rand();
				$queryParams['_message'] = $this->getMessage();
				$queryParams['_message_code'] = $this->getCode();
				$queryParams = http_build_query($queryParams, null, '&');
				$redirect = $redirect->withQuery($queryParams);
			}
			if (!$redirect->getScheme()) {
				$redirect = $redirect->withScheme(Route::request()->getUri()->getScheme());
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
		return ['message' => $this->getMessage(), 'code' => $this->getCode(), 'args' => $this->getArgs()];
    }

    public function __toString() {
		$name = $this->getCode() >= 400 ? 'error' : 'notice';
		return '<p class="message message-'. $name .'message-'. $this->getCode() .'">'. $this->getMessage() .'</p>';
    }

	public static function translate($text, $original = true) {
		return Locale::translate($text, ['message', 'default'], $original);
	}
}
