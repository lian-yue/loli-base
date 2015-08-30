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
use Loli\HTTP\Request;
class_exists('Loli\HTTP\Request') || exit;
class Session{
	private $_token;

	public function __construct($token) {
		$this->_token = $token;
	}

	public function get($key) {
		return Cache::get($this->_token . $key, __CLASS__);
	}

	public function add($value, $key, $ttl = 1800) {
		return Cache::add($value, $this->_token . $key, __CLASS__, $ttl);
	}

	public function set($value, $key, $ttl = 1800) {
		return Cache::set($value, $this->_token . $key, __CLASS__, $ttl);
	}

	public function incr($n, $key) {
		return Cache::incr($value, $this->_token . $key, __CLASS__);
	}

	public function decr($n, $key) {
		return Cache::decr($value, $this->_token . $key, __CLASS__);
	}

	public function delete($key, $ttl = 0) {
		return Cache::delete($value, $this->_token . $key, __CLASS__, $ttl);
	}

	public function ttl($key) {
		return Cache::delete($value, $this->_token . $key, __CLASS__);
	}
}