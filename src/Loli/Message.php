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

200 － 399 执行成功 并且要设置http状态码的

400 － 599 = 执行失败 并且 要设置 http 状态码的
*/
class Message extends \RuntimeException implements IteratorAggregate, JsonSerializable{

	protected $args = [];

	protected $data = [];

	protected $redirectUri = false;

	protected $refresh = 3;

	public function __construct($message = [], $code = 200, $data = [], $redirectUri = true, $refresh = 3, Message $previous = null) {
		// previous　变量自动缩进
		foreach(['code' => 200, 'data' => [], 'redirectUri' => false, 'refresh' => 3] as $key => $value) {
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

		// redirect uri
		$this->setRedirectUri(isset($data['redirect_uri']) ? $data['redirect_uri'] : $redirectUri, isset($data['redirect_uri']));

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

	public function getRedirectUri() {
		return $this->redirectUri;
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




	public static function getParsedRedirectUri($redirectUri = false, $defaultRedirectUri = false, $referer = true) {
		if (!$redirectUri) {
			return false;
		}

		if ($redirectUri instanceof UriInterface) {
			return clone $redirectUri;
		}

		if (is_object($redirectUri) || (is_string($redirectUri) && $redirectUri !== '1')) {
			return new PsrUri((string) $redirectUri);
		}

		$request = Route::request();
		if ($parsedbody = $request->getParsedBody()) {
			if (is_array($parsedbody) && !empty($parsedbody['redirect_uri'])) {
				return new PsrUri($parsedbody['redirect_uri']);
			}
			if (is_object($parsedbody) && !empty($parsedbody->redirect_uri)) {
				return new PsrUri($parsedbody->redirect_uri);
			}
		}

		if (($params = $request->getQueryParams()) && !empty($params['redirect_uri'])) {
			return new PsrUri($params['redirect_uri']);
		}

        if ($referer && ($referer = $request->getHeaderLine('referer')) && (!($params = $request->getQueryParams()) || empty($params['_re']) || $params['_re'] < 3)) {
			return new PsrUri($referer);
		}


        if ($defaultRedirectUri) {
            if ($defaultRedirectUri instanceof UriInterface) {
    			return clone $defaultRedirectUri;
    		}
            return new PsrUri((string) $defaultRedirectUri);
        }
		return new PsrUri('//'. $request->getUri()->getHost() . '/');
	}



	public function setRedirectUri($redirectUri, $whiteList = false) {
		if ($redirectUri) {
			try {
				$redirectUri = self::getParsedRedirectUri($redirectUri);
			} catch (\Exception $e) {
				$redirectUri = new PsrUri('//'. Route::request()->getUri()->getHost() . '/');
			}

			if (!$whiteList) {
				if ($redirectUri->getScheme() && !in_array($redirectUri->getScheme(), ['http', 'https'], true)) {
					// 协议不是 http https
					$error = true;
				} elseif ($redirectUri->getHost() && !preg_match('/(^|\.)('. implode('|', array_map(function($host){ return preg_quote($host, '/'); }, configure('whitelist_hosts', []))) .')$/i', $redirectUri->getHost())) {
					// host 无效
					$error = true;
				} elseif ($redirectUri->getUserInfo()) {
					// 带用户名和密码
					$error = true;
				} elseif (stripos($redirectUri->getPath(), ':') !== false || stripos($redirectUri->getPath(), ';') !== false) {
					$error = true;
				}
				if (isset($error)) {
					$redirectUri = new PsrUri('//'. Route::request()->getUri()->getHost() . '/');
				}
				if ($redirectUri->getQuery()) {
					parse_str($redirectUri->getQuery(), $queryParams);
				} else {
					$queryParams = [];
				}
				unset($queryParams['_r'], $queryParams['_message'], $queryParams['_message_code']);
				$queryParams['_r'] = mt_rand();
				$queryParams['_re'] = empty(Route::request()->getQueryParams()['_re']) ? 1 : Route::request()->getQueryParams()['_re'] + 1;
				$queryParams['_message'] = $this->getMessage();
				$queryParams['_message_code'] = $this->getCode();
				$queryParams = http_build_query($queryParams, null, '&');
				$redirectUri = $redirectUri->withQuery($queryParams);
			}
			if (!$redirectUri->getScheme()) {
				$redirectUri = $redirectUri->withScheme(Route::request()->getUri()->getScheme());
			}
		} else {
			$redirectUri = false;
		}
		$this->redirectUri = $redirectUri;
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
