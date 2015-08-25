<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-25 03:45:17
/*
/* ************************************************************************** */
namespace Loli;
use Loli\Cache\Base as Cache, Loli\HTTP\Request;
class_exists('Loli\Cache\Base') || exit;
class Session{
	private $_cache;
	private $_request;

	public function __construct(Cache &$cache, Request &$request) {
		$this->_cache = &$cache;
		$this->_request = &$request;
	}

	public function get($key) {
		return $this->_cache->get($this->_request->getToken() . $key, __CLASS__);
	}

	public function add($value, $key, $ttl = 1800) {
		return $this->_cache->add($value, $this->_request->getToken() . $key, __CLASS__, $ttl);
	}

	public function set($value, $key, $ttl = 1800) {
		return $this->_cache->set($value, $this->_request->getToken() . $key, __CLASS__, $ttl);
	}

	public function incr($n, $key) {
		return $this->_cache->incr($value, $this->_request->getToken() . $key, __CLASS__);
	}

	public function decr($n, $key) {
		return $this->_cache->decr($value, $this->_request->getToken() . $key, __CLASS__);
	}

	public function delete($key, $ttl = 0) {
		return $this->_cache->delete($value, $this->_request->getToken() . $key, __CLASS__, $ttl);
	}

	public function ttl($key) {
		return $this->_cache->delete($value, $this->_request->getToken() . $key, __CLASS__);
	}
}