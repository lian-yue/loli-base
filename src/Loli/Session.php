<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-12-10 05:18:47
/*	Updated: UTC 2015-02-17 13:49:31
/*
/* ************************************************************************** */
namespace Loli;
class Session{
	public static function get($key) {
		return Cache::get(Router::request()->getToken(). $key, __CLASS__);
	}
	public static function add($key, $value, $ttl = 3600) {
		return Cache::add($value, Router::request()->getToken(). $key, __CLASS__, $ttl);
	}
	public static function set($key, $value, $ttl = 3600) {
		return Cache::set($value, Router::request()->getToken(). $key, __CLASS__, $ttl);
	}
	public static function delete($key) {
		return Cache::delete(Router::request()->getToken(). $key, __CLASS__);
	}
}
