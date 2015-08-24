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
class Controller{

	protected $route;


	public function __construct(Route &$route) {
		$this->route = &$route;
	}


	public function __call($name, $args) {
		return new Message(404, Message::ERROR);
	}
}