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

	public function __construct($views = [], array $data = []) {
		$data && $this->merge($data);
		$this->views = (array) $views;
	}

	protected function load($files) {
		foreach ($files as $_file) {
			if ($is = is_file($_file = configure(['view', 'dir'], './') .'/' . strtolower(strtr($_file, '\\.', '//')) . '.php')) {
				break;
			}
		}
		if (empty($is)) {
			return false;
		}

		$_data = [];
		foreach ($this as $key => $value) {
			if (!$key || $value === null || !is_string($key) || $key{0} === '_' || $key === 'this' || $key === 'GLOBALS') {
				continue;
			}
			$_data[$key] = $value;
		}
		unset($is, $files, $key, $value);
		extract($_data);
		require $_file;
	}

	protected function processing() {
		$serverParams = Route::request()->getServerParams();
		if (empty($serverParams['REQUEST_TIME_FLOAT'])) {
			$datetime = '0.000';
		} else {
			$datetime = number_format(microtime(true) - $serverParams['REQUEST_TIME_FLOAT'], 3);
		}
		return ['timestamp' => $datetime, 'memory' => number_format((memory_get_peak_usage() / 1024 / 1024), 3), 'files' => count(get_included_files())];
	}

	public function __toString() {
		if (Route::json()) {
			if ($jsonp = Route::jsonp()) {
				Route::response(Route::response()->withHeader('Content-Type', 'application/x-javascript'));
				$jsonp = preg_replace('/[^0-9a-z_.-]/i', '', $jsonp);
				if (Route::csrf()) {
					$json = $jsonp . '(' . json_encode([]). ');';
				} else {
					$json = $jsonp. '(' . json_encode($this->toArray() + ['processing' =>$this->processing()]). ');';
				}
			} else {
				if (Route::request()->getMethod() === 'GET' || Route::ajax()) {
					Route::response(Route::response()->withHeader('Content-Type', 'application/json'));
				}
				$json = json_encode($this->toArray() + ['processing' => $this->processing()]);
			}
			return $json;
		}
		ob_start();
		$this->load($this->views);
		Route::response(Route::response()->withHeader('X-Processing', http_build_query($this->processing(), null, ' ')));
		return ob_get_clean();
	}
}
