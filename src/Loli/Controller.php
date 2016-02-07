<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2016-02-03 03:02:06
/*
/* ************************************************************************** */
namespace Loli;
class Controller{


	protected $middleware = [];

	protected $defaultMiddleware = [];

	protected $mustMiddleware = [];

	public function __construct($handle = false) {
		$this->handle = $handle;
		$handle && $this->handle();
	}

	protected function handle() {
		$method = strtolower(Route::controller()[1]);
		foreach ($this->middleware as $key => $middleware) {
			if ($is = (strtolower($key) === $method)) {
				break;
			}
		}

		if (empty($is)) {
			$middleware = $this->defaultMiddleware;
		}
		$middleware = $middleware + $this->mustMiddleware;

		foreach ($middleware as $name => $value) {
			$class = 'App\Middleware\\' . $name;
			$class = new $class;
			if ($class->handle($value) === false) {
				break;
			}
		}
		return $this;
	}

	public function __call($name, $args) {
		throw new Message([404, 'Controller does not exist'], Message::ERROR);
	}
}