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

	protected $layouts = [];

	public function __construct($views = [], array $data = []) {
		$data && $this->merge($data);
		$this->views = (array) $views;
	}

    protected function layout($name, $call) {
        $this->layouts[$name][] = $call;
    }

	protected function block($name, $call = false) {
        if (!isset($this->layouts[$name])) {
            $this->layouts[$name] = [];
        }
        if ($call) {
            $this->layouts[$name][] = $call;
        }


        $key = 0;
        $parent = function() use (&$key, &$parent, $name) {
            if (empty($this->layouts[$name][$key])) {
                return;
            }
            $key2 = $key;
            ++$key;
            call_user_func($this->layouts[$name][$key2], $parent);
        };
        $parent();
    }


	protected function load($files) {
		foreach ((array)$files as $__file) {
			if ($is = is_file($__file = configure(['view', 'dir'], './') .'/' . strtolower(strtr($__file, '\\.', '//')) . '.php')) {
				break;
			}
		}
		if (empty($is)) {
			return false;
		}
		unset($is, $files);
		return require $__file;
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
