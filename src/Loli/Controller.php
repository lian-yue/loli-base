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
	public function __call($name, $args) {
		throw new Message([404, 'Controller does not exist'], Message::ERROR);
	}
}