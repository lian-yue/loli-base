<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-25 04:12:45
/*
/* ************************************************************************** */
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-02-17 08:31:12
/*	Updated: UTC 2015-04-07 14:29:51
/*
/* ************************************************************************** */
namespace Loli\Cache;
abstract class Base{

	// 记录使用次数
	public $count = ['get' => 0, 'add' => 0, 'set' => 0, 'incr' => 0, 'decr' => 0, 'delete' => 0, 'ttl' => 0];

	// KEY
	protected $key = '';


	abstract public function __construct(array $args, $key = '');


	/**
	 * get
	 * @param  string $key
	 * @param  string $group 分组
	 * @return
	 */
	abstract public function get($key, $group = 'default');

	/**
	 * add
	 * @param  *       $value
	 * @param  string  $key
	 * @param  string  $group
	 * @param  integer $ttl   0秒 只在内存中缓存  -1 = 永久缓存
	 * @return boolean
	 */
	abstract public function add($value, $key, $group = 'default', $ttl = 0);

	/**
	 * set
	 * @param  *       $value
	 * @param  string  $key
	 * @param  string  $group
	 * @param  integer $ttl   0秒 只在内存中缓存  -1 = 永久缓存
	 * @return boolean
	 */
	abstract public function set($value, $key, $group = 'default', $ttl = 0);


	/**
	 * incr
	 * @param  integer $n
	 * @param  string  $key
	 * @param  string  $group
	 * @return boolean
	 */
	abstract public function incr($n, $key, $group = 'default');

	/**
	 * decr
	 * @param  integer $n
	 * @param  string  $key
	 * @param  string  $group
	 * @return boolean
	 */
	abstract public function decr($n, $key, $group = 'default');


	/**
	 * delete
	 * @param  string  $key
	 * @param  string  $group
	 * @param  integer $ttl 如果设定了时间就是延迟删除
	 * @return boolean
	 */
	abstract public function delete($key, $group = 'default', $ttl = 0);


	/**
	 * ttl  获得有效期
	 * @param  string  $key
	 * @param  string  $group
	 * @return boolean
	 */
	abstract public function ttl($key, $group = 'default');


	/**
	 * flush
	 * @param  boolean $mem  true = 只删除内存的数据
	 * @return boolean
	 */
	abstract public  function flush($mem = false);


	/**
	 * addServers
	 * @param string $group
	 * @param array  $servers
	 */
	abstract public function addServers($group, array $servers);

}


