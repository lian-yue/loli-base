<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2016-01-29 08:33:42
/*
/* ************************************************************************** */
namespace Loli;
class URLRoute extends URL{

	public function __construct($controller = false, array $query = [], $method = 'GET') {
		$controller && $this->__set('controller', (array) $controller);
		$this->__set('query', $query);
		$this->__set('method', $method ? $method : 'GET');
	}


	public function __toString() {
		static $static = [];
		$controller = $this->controller;
		$controller[0] = strtr($controller[0], '.', '/');

		if (!isset($static[$controller[0]][$controller[1]][$this->method])) {
			$args = [];
			$static[$controller[0]][$controller[1]][$this->method] = &$args;
			foreach (Route::$rules as &$route) {
				if (in_array($this->method, $route['method'], true) && preg_match($route['controllerRule'][0][4], $controller[0], $match) && preg_match($route['controllerRule'][1][4], $controller[1], $match2)) {
					$args = [&$route, []];
					foreach ($match + $match2 as $key => $value) {
						if (!is_int($key)) {
							$key = substr($key, 1);
							$args[1][$key] = $value;
						}
					}
					break;
				}
			}
		}

		if (empty($static[$controller[0]][$controller[1]][$this->method])) {
			return '';
		}

		$args = &$static[$controller[0]][$controller[1]][$this->method];

		$hostSearch = $hostReplace = $pathSearch = $pathReplace = [];
		foreach ($args[0]['hostRule'][0][2] as $name => $value) {
			$hostSearch[] = '"'.$name.'"';
			if (isset($args[1][$name])) {
				$hostReplace[] = $args[1][$name] === '' ? '' : $value . $args[1][$name] . $args[0]['hostRule'][0][3][$name];
			} elseif (isset($this->query[$name])) {
				$hostReplace[] = $this->query[$name] === '' ? '' : $value . urlencode($this->query[$name]) . $args[0]['hostRule'][0][3][$name];
			} else {
				$hostReplace[] = '';
			}
		}

		$this->query += $args[0]['defaults'];

		foreach ($args[0]['pathRule'][2] as $name => $value) {
			$pathSearch[] = '"'.$name.'"';
			if (isset($args[1][$name])) {
				$pathReplace[] = $args[1][$name] === '' ? '' : $value . $args[1][$name] . $args[0]['pathRule'][3][$name];
			} elseif (isset($this->query[$name])) {
				$pathReplace[] = $this->query[$name] === '' ? '' : $value . urlencode($this->query[$name]). $args[0]['pathRule'][3][$name];
			} else {
				$pathReplace[] = '';
			}
		}


		$url = '';
		if ($this->host) {
			if ($this->scheme) {
				$url .= $this->scheme . ':';
			}
		} else {
			if ($this->scheme) {
				$this->scheme . ':';
			} elseif (empty($args[0]['scheme'][1])) {
				$url .= $args[0]['scheme'][0] . ':';
			}
		}
		$url .= '//';

		if ($this->user) {
			$url .= $this->user;
		}
		if ($this->pass) {
			$url .= ':' . $this->pass;
		}
		if ($this->user || $this->pass) {
			$url .= '@';
		}
		if ($this->host) {
			$url .= $this->host;
		} else {
			$url .= str_replace($hostSearch, $hostReplace, $args[0]['hostRule'][0][0]);
		}

		if ($this->port) {
			$url .= ':'. $this->port;
		}

		if ($this->path) {
			$url .= '/'. ltrim($this->path, '/');
		} else {
			$url .= str_replace($pathSearch, $pathReplace, $args[0]['pathRule'][0]);
		}

		if ($this->query) {
			if ($this->host || $this->path) {
				$url .= '?'. merge_string($this->query);
			} elseif ($query = array_diff_key($this->query, $args[0]['match'])) {
				$url .= '?'. merge_string($query);
			}
		}

		if ($this->query) {
			$url .= '?'. merge_string($this->query);
		}

		if ($this->fragment) {
			$url .= '#'. $this->fragment;
		}
		return htmlencode($url);
	}
}