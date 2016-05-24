<?php
namespace Loli;

use JsonSerializable;
use Psr\Http\Message\UriInterface;


class Uri implements UriInterface, JsonSerializable{

	private static $schemes = [
		80 => ['' => true, 'https' => true],
		443 => ['' => true, 'http' => true],
	];

    private $scheme = '';

    private $userInfo = '';

    private $host = '';

    private $port;

    private $path = '';

    private $query = [];

    private $fragment = '';

	private $data = [];

	private $controller = [];

	private $rule = [];

	private $params = [];

	public function __construct(array $controller = [], array $query = [], $method = 'GET') {
		$this->controller = $controller + ['Index', 'index'];
		$this->method = $method;
		$this->query = $query;
		$this->getParsed();
	}


	public function __sleep() {
		return ['scheme', 'userInfo', 'host', 'port', 'path', 'query', 'fragment', 'data', 'controller'];
	}

	public function __wakeup() {
		$this->getParsed();
	}

	public function __clone() {
		$this->data = [];
	}

	public function getMethod() {
		return $this->method;
	}

	public function getScheme() {
		if ($this->scheme) {
			return $this->scheme;
		}
		if (!isset($this->data['scheme'])) {
			if ($this->rule['scheme'] && empty($this->rule['scheme'][1])) {
				$scheme = $this->rule['scheme'][0];
			} else {
				$scheme = '';
			}
			$this->data['scheme'] = $scheme;
		}
		return $this->data['scheme'];
	}

	public function getAuthority() {
		if (!$host = $this->getHost()) {
			return '';
		}
		$authority = '';
		if ($this->userInfo) {
            $authority = $this->userInfo . '@';
        }
		$authority .= $host;

		if ($this->port && !isset(self::$schemes[$this->port][$this->getScheme()])) {
			$authority .= ':' . $this->port;
		}

        return $authority;
	}

    public function getUserInfo() {
		return $this->userInfo;
	}

    public function getHost() {
		if ($this->host) {
			return $this->host;
		}

		if (!isset($this->data['host'])) {
			$searchs = $replaces = [];
			foreach ($this->rule['hostRule'][0][2] as $name => $value) {
				$searchs[] = '"'.$name.'"';
				if (isset($this->params[$name])) {
					$replaces[] = $this->params[$name] === '' || $this->params[$name] === 'index' ? '' : $value . $this->params[$name] . $this->rule['hostRule'][0][3][$name];
				} elseif (isset($this->query[$name])) {
					$replaces[] = $this->query[$name] === '' ? '' : $value . urlencode($this->query[$name]) . $this->rule['hostRule'][0][3][$name];
				} elseif (isset($this->rule['defaults'][$name])) {
					$replaces[] = $this->rule['defaults'][$name] === '' ? '' : $value . urlencode($this->rule['defaults'][$name]). $this->rule['pathRule'][0][3][$name];
				} else {
					$replaces[] = '';
				}
			}
			$this->data['host'] = str_replace($searchs, $replaces, $this->rule['hostRule'][0][0]);
		}
		return $this->data['host'];
	}

    public function getPort() {
		return $this->port;
	}

    public function getPath() {
		if ($this->path) {
			return $this->path;
		}
		if (!isset($this->data['path'])) {
			$searchs = $replaces = [];
			foreach ($this->rule['pathRule'][2] as $name => $value) {
				$searchs[] = '"'.$name.'"';
				if (isset($this->params[$name])) {
					$replaces[] = $this->params[$name] === '' || $this->params[$name] === 'index' ? '' : $value . $this->params[$name] . $this->rule['pathRule'][3][$name];
				} elseif (isset($this->query[$name])) {
					$replaces[] = $this->query[$name] === '' ? '' : $value . urlencode($this->query[$name]). $this->rule['pathRule'][3][$name];
				} elseif (isset($this->rule['defaults'][$name])) {
					$replaces[] = $this->rule['defaults'][$name] === '' ? '' : $value . urlencode($this->rule['defaults'][$name]). $this->rule['pathRule'][3][$name];
				} else {
					$replaces[] = '';
				}
			}
			$this->data['path'] = str_replace($searchs, $replaces, $this->rule['pathRule'][0]);
			if (!$this->data['path'] || $this->data['path']{0} !== '/') {
				$this->data['path'] = '/' . $this->data['path'];
			}
		}
		return $this->data['path'];
	}

    public function getQuery() {
		if (!isset($this->data['query'])) {
			$query = $this->query + $this->rule['defaults'];
			if ($query) {
				$query = array_diff_key($query, $this->rule['match']);
			}
			if ($query) {
				$query = $this->getQueryArray($query);
			}
			$this->data['query'] = $query ? http_build_query($query, null, '&') : '';
		}

		return $this->data['query'];
	}



	public function getQueryParams() {
		return $this->query;
	}

	public function withQueryParams(array $query) {
		if ($new->query === $query) {
			return $this;
		}
		$new = clone $this;
		$new->query = $query;
		return $new;
	}

	public function withQueryParam($name, $value) {
		if ($value === null) {
			if (!isset($this->query[$name])) {
				return $this;
			}
			$new = clone $this;
			unset($new->query[$name]);
			return $new;
		}
		$value = is_array($value) ? to_array($value) : to_string($value);
		if (isset($new->query[$name]) && $new->query[$name] === $value) {
			return $this;
		}
		$new = clone $this;
		$new->query[$name] = $value;
		return $new;
	}

    public function getFragment() {
		return $this->fragment;
	}


    public function withMethod($method) {
		if ($this->method === $method) {
            return $this;
        }

        $new = clone $this;
        $new->method = $method;
        return $new;
	}

    public function withScheme($scheme) {
        if ($this->scheme === $scheme) {
            return $this;
        }

        $new = clone $this;
        $new->scheme = $scheme;
		if ($this->port && isset(self::$schemes[$this->port][$scheme])) {
			$new->port = null;
		}
        return $new;
	}

    public function withUserInfo($user, $password = null) {
		$info = $user;
        if ($password) {
            $info .= ':' . $password;
        }

        if ($this->userInfo === $info) {
            return $this;
        }

        $new = clone $this;
        $new->userInfo = $info;
        return $new;
	}

    public function withHost($host) {
		if ($this->host === $host) {
            return $this;
        }

        $new = clone $this;
        $new->host = $host;
        return $new;
	}

    public function withPort($port) {
		if ($port && isset(self::$schemes[$port][$this->getScheme()])) {
			$port = null;
		}

		if ($this->port === $port) {
			return $this;
		}

		$new = clone $this;
		$new->port = $port;
		return $new;
	}


    public function withPath($path) {
        if ($this->path === $path) {
            return $this;
        }
        $new = clone $this;
        $new->path = $path;
        return $new;
	}

    public function withQuery($query) {
		if (is_scalar($query)) {
			parse_str($query, $queryParams);
			$query = $queryParams;
		} elseif (!is_array($query)) {
			$query = to_array($query);
		}
		if ($this->query === $query) {
            return $this;
        }
        $new = clone $this;
        $new->query = $query;
        return $new;
	}

    public function withFragment($fragment) {
		if ($this->fragment === $fragment) {
            return $this;
        }
        $new = clone $this;
        $new->fragment = $fragment;
        return $new;
	}

    public function __toString() {
		$uri = '';

        if ($authority = $this->getAuthority()) {
			if ($scheme = $this->getScheme()) {
				$uri .= $scheme . ':';
			}
            $uri .= '//' . $authority;
        }


		if ($path = $this->getPath()) {
			if ($uri && $path{0} !== '/') {
				$uri .= '/';
			}
			$uri .= $path;
		}


		if ($query = $this->getQuery()) {
			$uri .= '?' . $query;
		}

		if ($this->fragment) {
			$uri .= '#' . $this->fragment;
		}
		return  htmlencode($uri);
	}


	public function jsonSerialize() {
		return $this->__toString();
	}


	protected function getQueryArray(array $query) {
		foreach($query as &$value) {
			if (is_array($value)) {
				$value = $this->getQueryArray($value);
			} elseif (is_object($value)) {
				$value = method_exists($value, '__toString') ? $value->__toString() : 'Object';
			}
		}
		return $query;
	}

	private function getParsed() {
		static $static = [];
		if (!isset($static[$this->controller[0]][$this->controller[1]][$this->method])) {
			foreach (Route::$rules as $rule) {
				if (in_array($this->method, $rule['method'], true) && preg_match($rule['controllerRule'][0][4], $this->controller[0], $matches) && preg_match($rule['controllerRule'][1][4], $this->controller[1], $matches2)) {
					$args = [&$rule, []];
					foreach ($matches + $matches2 as $key => $value) {
						if (!is_int($key)) {
							$key = substr($key, 1);
							$args[1][$key] = $value;
						}
					}
					break;
				}
			}
			if (empty($args)) {
				throw new \InvalidArgumentException('The controller rule does not exist');
			}
			$static[$this->controller[0]][$this->controller[1]][$this->method] = $args;
		}
		$this->rule = &$static[$this->controller[0]][$this->controller[1]][$this->method][0];
		$this->params = &$static[$this->controller[0]][$this->controller[1]][$this->method][1];
	}
}
