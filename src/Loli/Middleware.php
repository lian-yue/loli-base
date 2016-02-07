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

	public function __construct(array $config) {
		foreach ($config as $key => $value) {
			if ($value !== NULL && property_exists($this, $key)) {
				$this->{$key} = $value;
			}
		}
	}

	abstract public function request(array &$params);


	abstract public function response(&$view);
}


