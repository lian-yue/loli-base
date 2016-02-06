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
class ViewModel{

	protected $route;

	protected $middleware = [

	];
	protected $defaultMiddleware = [];

	public function __construct(Route $route, $viewModel = false) {
		$this->route = $route;
		$this->viewModel = $viewModel;
		if ($this->viewModel) {
			$this->handle();
		}
	}

	protected function handle() {
		$method = strtolower($this->route->model[1]);
		foreach ($this->middleware as $key => $middleware) {
			if ($is = (strtolower($key) === $method)) {
				break;
			}
		}

		if (empty($is)) {
			$middleware = $this->middleware;
		}

		foreach ($middleware as $value) {
			$class = 'App\Middleware\\' . $value;
			$class = new $class;
			if ($class->handle($this->route) === false) {
				break;
			}
		}
		return $this;
	}

	public function __call($name, $args) {
		throw new Message([404, 'Model does not exist'], Message::ERROR);
	}
}