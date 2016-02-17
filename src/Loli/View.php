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
class View extends ArrayObject{

	protected $dir;

	public function __construct($views = [], array $data = [], $expire = false) {
		$this->dir = empty($_SERVER['LOLI']['view']['dir']) ? './' : $_SERVER['LOLI']['view']['dir'];
		$data && $this->merge($data);
		$expire && $this->expire($expire);
		$this->views = (array) $views;
	}

	public function expire($expire) {
		if ($expire) {
			$response = Route::response();
			$response->getHeader('Etag') || $response->addHeader('Etag', '"' . md5(json_encode($this)) .'"');
			$response->setStatus($response->getCacheStatus());
		}
		return $this;
	}


	protected function load($files) {
		foreach ($files as $_file) {
			if ($is = is_file($_file = $this->dir .'/' . strtolower(strtr($_file, '\\.', '/')) . '.php')) {
				break;
			}
		}
		if (empty($is)) {
			return false;
		}

		$_data = [];
		foreach ($this as $key => $value) {
			if (!$key || $value === NULL || !is_string($key) || $key{0} === '_' || $key === 'this' || $key === 'GLOBALS') {
				continue;
			}
			$_data[$key] = $value;
		}

		unset($is, $files, $key, $value);
		extract($_data);
		require $_file;
	}

	protected function processing() {
		return '<!--Processing:' . Route::request()->processing() .' Memory:' .number_format((memory_get_peak_usage() / 1024 / 1024), 4) .' Files:' .count(get_included_files()) .' Database: '. count(Database::statistics()) .'-->';
	}

	public function __toString() {
		$request = Route::request();
		$response = Route::response();

		if ($ajax = $request->getAjax()) {
			if (!Route::csrf() && !in_array($ajax, ['true', 'json'], true) && !intval(substr($ajax, 0, 1)) && ($function = preg_replace('/[^0-9a-z_.-]/i', '', $ajax))) {
				$response->setHeader('Content-Type', 'application/x-javascript');
				$json = $function . '(' . json_encode($this). ');';
			} else {
				if ($request->getMethod() === 'GET' || strtolower($request->getHeader('X-Requested-with')) === 'xmlhttprequest') {
					$response->setHeader('Content-Type', 'application/json');
				}
				$json =  json_encode($this);
			}
			echo $json;
		} else {
			$this->load($this->views);
		}
		echo '';
	}
}
