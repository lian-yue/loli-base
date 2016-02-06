<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2016-01-27 06:08:07
/*
/* ************************************************************************** */
namespace Loli;
class URL extends ArrayObject{
	public function __construct($url = false) {
		if (is_scalar($url)) {
			foreach (parse_url($url) + ['query' => []] as $name => $value) {
				$this->__set($name, $value);
			}
		} else {
			$this->__set($url);
		}
	}

	public function query($query, $value = NULL) {
		if ($value !== NULL || (is_scalar($query) && strpos($query, '&') === false)) {
			$this->__set('query', array_merge($this->__get('query'), [$query=> $value]));
		} else {
			$this->__set('query', $query);
		}
		return $this;
	}

	public function __set($name, $value) {
		if ($name === 'query') {
			if (is_scalar($value)) {
				$value = parse_string($value);
			} else {
				$value = (array) $value;
			}
		}
		return parent::__set($name, $value);
	}

	public function __toString() {
		$url = '';
		if ($this->host) {
			if ($this->scheme) {
				$url .= $this->scheme . ':';
			}
			$url .= '//';
		}

		if ($this->user) {
			$url .= $this->user;
		}
		if ($this->pass) {
			$url .= ':' . $this->pass;
		}
		if ($this->user || $this->pass) {
			$url .= '@';
		}

		if ($this->host) {
			$url .= $this->host;
		}
		if ($this->port) {
			$url .= ':'. $this->port;
		}
		if ($this->path) {
			$url .= '/'. ltrim($this->path, '/');
		} else {
			$url .= '/';
		}

		if ($this->query) {
			$url .= '?'. merge_string($this->query);
		}

		if ($this->fragment) {
			$url .= '#'. $this->fragment;
		}

		return htmlencode($url);
	}

	public function jsonSerialize() {
		return $this->__toString();
	}
}