<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2016-02-03 05:13:38
/*
/* ************************************************************************** */
namespace Loli;
abstract class Middleware{

	public function __construct(Route $route, $viewModel = false) {
		$this->route = $route;
		$this->viewModel = $viewModel;
		if ($this->viewModel) {
			$this->pretreatment();
		}
	}

	abstract public function handle();
}