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
class_exists('Loli\Route') || exit;
class Controller{

	protected $route;

	public function __construct(Route &$route) {
		$this->route = &$route;
	}

	public function __call($name, $args) {
		throw new Message(404, Message::ERROR);
	}

	protected function token() {
		if ($this->route->request->getToken(false, false) !== $this->route->request->getParam('_token', '')) {
			$this->route->response->setStatus(403);
			throw new Message([90, 'Token'], Message::ERROR);
		}
	}
}