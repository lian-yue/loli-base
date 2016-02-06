<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2016-01-27 05:40:11
/*
/* ************************************************************************** */
namespace Loli;
class Paginator {
	protected $url;

	protected $key = 'page';

	protected $current = 1;

	protected $perPage = 20;

	protected $total = 0;

	public function __construct(URL $url = null, $current = false, $perPage = false) {
		$this->setUrl($url ? $url : new URL(Route::request()->getURL()));
		$this->setCurrent($current ? $current : Route::request()->getParam($this->key, 1));
		$perPage && $this->setPerPage($perPage);
	}

	public function start() {
		return 1;
	}

	public function end() {
		$end = (int) ($this->total() / $this->perPage);
		if ($end < 1) {
			$end = 1;
		}
		return $end;
	}


	public function prev() {
		if ($this->current() > 1) {
			return $this->current() - 1;
		}
		return false;
	}

	public function next() {
		if ($this->end() > $this->current()) {
			return 1 + $this->current();
		}
		return false;
	}


	public function current() {
		return $this->current;
	}


	public function url($page) {
		$url = clone $this->url;
		$url->query($this->key, $page);
		return $url;
	}

	public function key() {
		return $this->key;
	}

	public function perPage() {
		return $this->perPage;
	}

	public function total() {
		return $this->total;
	}

	public function setUrl(URL $url) {
		$this->url = $url;
		return $this;
	}

	public function setKey($key) {
		$this->key = $key;
		return $this;
	}

	public function setPerPage($perPage) {
		if ($perPage < 1) {
			$perPage = 1;
		}
		$this->perPage = (int) $perPage;
		return $this;
	}

	public function setCurrent($current) {
		if ($current < 1) {
			$current = 1;
		}
		$this->current = (int) $current;
		return $this;
	}

	public function setTotal($total) {
		if ($total < 0) {
			$total = 0;
		}
		$this->total = (int) $total;
		return $this;
	}
}