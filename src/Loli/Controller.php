<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-22 06:52:42
/*
/* ************************************************************************** */
namespace Loli;
use Model\RBAC;
class_exists('Loli\Route') || exit;
class Controller{

	protected $route;

	public function __construct(Route &$route) {
		$this->route = &$route;
	}

	public function __call($name, array $args) {
		throw new Message(404, Message::ERROR);
	}

	public function __RBAC(array $params, Route &$route) {
		if (empty($route->table['RBAC'])) {
			return true;
		}
		if ($route->table['RBAC']->has($route->controllerName, $route->controllerMethod)) {
			return true;
		}
		return false;
	}

	protected function token() {
		if ($this->route->request->getToken(false, false) !== $this->route->request->getParam('_token', '')) {
			$this->route->response->setStatus(403);
			throw new Message([90, 'Token'], Message::ERROR);
		}
	}
}