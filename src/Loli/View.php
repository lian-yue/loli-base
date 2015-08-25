<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-22 03:09:29
/*
/* ************************************************************************** */
namespace Loli;
interface_exists('Loli\RouteInterface') || exit;
class View implements RouteInterface{

	protected $files;

	protected $data;

	protected $route;

	protected $cache = false;

	public function __construct($files, array $data = [], $cache = false) {
		$this->files = (array) $files;

		foreach ($data as $key => $value) {
			if (!$key || $value === NULL || !is_string($key) || $key{0} === '_' || $key === 'this' || $key === 'GLOBALS') {
				unset($data[$key]);
			}
		}
		$this->cache = $cache;
		$this->data = $data;
		$this->dir = empty($_SERVER['LOLI']['VIEW']['dir']) ? './' : $_SERVER['LOLI']['VIEW']['dir'];
	}

	public function route(Route &$route) {
		$this->route = &$route;
		$this->cache && $route->response->addCache('public', true)->addCache('max-age', $this->cache === true ? 1800 : $this->cache);
		if ($ajax = $route->request->getAjax()) {
			$route->response->setHeader('X-Ajax', 'true');
			switch ($type = strtolower($ajax)) {
				case 'query':
					$this->data = merge_string($this->data);
					break;
				case 'xml':
					$function = function ($array) use(&$function) {
						$ret = $attr = '';
						foreach ($array as $tag => $value) {
						 	if (!preg_match('/^[a-z][0-9a-z_]*$/i', $tag)) {
						 		$attr = ' k="' . htmlspecialchars($tag, ENT_QUOTES) . '"';
								$tag  = 'item';
						 	}
					        $ret .=  '<' . $tag . $attr.'>' .((is_array($value) || is_object($value)) ? $function($value) :  htmlspecialchars($value, ENT_QUOTES)) . '</' . $tag . '>' ."\n";
					    }
					    return $ret;
					};
					$route->response->setHeader('Content-Type', 'application/xml');
					$this->data = '<?xml version="1.0" encoding="UTF-8"?><root>'. $function($this->data) .'</root>';
					break;
				default:
					if (($route->ajaxJS || ($route->request->getToken(false, false) === $route->request->getParam('_token', ''))) && !in_array($type, ['true', 'json'], true) && !intval(substr($type, 0, 1)) && ($function = preg_replace('/[^0-9a-z_.-]/i', '', $ajax))) {
						$route->response->setHeader('Content-Type', 'application/x-javascript');
						$this->data = $function . '(' . json_encode($this->data) . ')';
					} else {
						if ($route->request->getMethod() !== 'POST'|| strtolower($route->request->getHeader('X-Requested-with')) === 'xmlhttprequest') {
							$route->response->setHeader('Content-Type', 'application/json');
						}
						$this->data = json_encode($this->data);
					}
			}
			$route->response->addHeader('X-Processing', $route->request->processing());
			$route->response->addHeader('X-Memory', number_format((memory_get_peak_usage() / 1024 / 1024), 4));
			$route->response->addHeader('X-Files', count(get_included_files()));
			if ($this->cache) {
				$route->response->getHeader('Etag') || $route->response->addHeader('Etag', '"' . substr(hash('md4', $this->data), 0, 16) .'"');
				$route->response->setStatus($route->response->getCacheStatus());
			}
		} else {
			if ($this->cache) {
				$data = json_encode($this->data);
				$route->response->getHeader('Etag') || $route->response->addHeader('Etag', '"' . substr(hash('md4', $data), 0, 16) .'"');
				$route->response->setStatus($route->response->getCacheStatus());
			}
			$this->data['route'] = $this->route;
			foreach(['request', 'response', 'localize'] as $value) {
				$this->data[$value] = &$this->route->$value;
			}
		}
	}

	protected function load($files) {
		foreach ($files as $_file) {
			if ($is = is_file($_file = $this->dir .'/' . strtolower($_file) . '.php')) {
				break;
			}
		}
		if (empty($is)) {
			return false;
		}

		unset($is, $files);
		extract($this->data);
		require $_file;
	}

	protected function processing() {
		return '<!--Processing:' . $this->route->request->processing() .' Memory:' .number_format((memory_get_peak_usage() / 1024 / 1024), 4) .' Files:' .count(get_included_files()) .'-->';
	}

	public function __invoke() {
		if (is_string($this->data)) {
			return $this->data;
		}
		$this->load($this->files);
	}

}