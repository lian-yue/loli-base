<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-02-17 08:31:12
/*	Updated: UTC 2015-02-05 07:41:07
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
	*	获取 缓存
	*
	*	1 参数 缓存 唯一 KEY
	*	2 参数 缓存目录  建议只用 0-9 a-z A-Z _ -
	*
	*	返回 你原先存入的数据
	**/
	abstract public function get($key, $list = 'default');

	/**
	*	添加 缓存
	*
	*	1 参数 缓存 KEY
	*	2 参数 缓存内容  支持除了资源类型其他任意类型
	*	3 参数 缓存目录  不同目录 可以添加相同 KEY   建议只用 0-9 a-z A-Z _ -
	*	4 参数 缓存时间  0秒 只在内存中缓存  -1 = 永久缓存
	*
	*	已经存在内容覆盖
	*	返回 true 成功 返回 false 失败
	*/
	abstract public function add($data, $key, $list = 'default', $ttl = 0);


	/**
	*	写入 缓存
	*
	*	1 参数 缓存 KEY
	*	2 参数 缓存内容  支持除了资源类型其他任意类型
	*	3 参数 缓存目录  不同目录 可以添加相同 KEY   建议只用 0-9 a-z A-Z _ -
	*	4 参数 缓存时间  0秒 只在内存中缓存  -1 = 永久缓存
	*
	*	已经存在内容覆盖
	*	返回 true 成功 返回 false 失败
	*/
	abstract public function set($data, $key, $list = 'default', $ttl = 0);


	/**
	*	增加 值
	*
	*	1 参数 缓存 KEY
	*	2 参数 数量
	*	3 参数 缓存目录  不同目录 可以添加相同 KEY   建议只用 0-9 a-z A-Z _ -
	*
	*
	*	返回 true 成功 返回 false 失败
	*/
	abstract public function incr($n, $key, $list = 'default');

	/**
	*	减少 值
	*
	*	1 参数 缓存 KEY
	*	2 参数 数量
	*	3 参数 缓存目录  不同目录 可以添加相同 KEY   建议只用 0-9 a-z A-Z _ -
	*
	*
	*	返回 true 成功 返回 false 失败
	*/
	abstract public function decr($n, $key, $list = 'default');


	/**
	*	删除 缓存
	*
	*	1 参数 缓存KEY
	*	2 参数 缓存目录  建议只用 0-9 a-z A-Z _ -
	*	3 参数 多久后过期 0 = 立刻 否则延迟删除
	*
	*	返回 true 成功 返回 false 失败
	**/
	abstract public function delete($key, $list = 'default', $ttl = 0);


	/**
	 * 获得 或设置 过期时间
	 * @param  [type]  $key  列表
	 * @param  string  $list 时间
	 * @return -1 永不过期  0 = 临时的  1 = 1秒后过期  false = 已过期
	 */
	abstract public function ttl($key, $list = 'default');


	/**
	*	清空缓存
	*
	*	1 是否只清楚php内存中的
	*
	*	返回 true 成功 返回 false 失败
	**/
	abstract public  function flush($mem = false);


	/**
	 * 添加服务器
	 * @param [type] $list 服务器组
	 * @param [type] $a    服务器信息
	 */
	abstract public function addServers($list, array $a);

}


