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
use Loli\DB\Cursor;
class_exists('Loli\DB\Cursor') || exit;
class Table extends Cursor{
	protected $callback = true;

	public function __construct(Route &$route) {
		$this->route = &$route;
		$this->DB = $route->DB;
	}

	public function flush() {
		if ($this->increment !== $this->current) {
			$this->fields = $this->querys = $this->values = $this->documents = $this->options = $this->unions = $this->data = [];
			$this->builder && $this->builder->flush();
			$this->current = $this->increment = 0;
		}
		return $this;
	}
}