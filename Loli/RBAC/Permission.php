<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-03 07:09:40
/*	Updated: UTC 2015-01-06 15:47:03
/*
/* ************************************************************************** */
namespace Loli\RBAC;
use Loli\Query;
class Permission extends Query{
	public $ttl = 0;

	public $args = [
		'role' => '',
		'node' => '',
		'status' => '',
	];

	public $defaults = [
		'role' => 0,
		'node' => 0,
		'status' => false,
		'args' => [],	// 其他 limit 等参数 比如每天最多发帖多少次
	];

	public $primary = ['role', 'node'];

	public $create = [
		'role' => ['type' => 'int', 'unsigned' => true, 'increment' => true, 'primary' => 0]],
		'node' => ['type' => 'int', 'unsigned' => true, 'increment' => true, 'primary' => 1]],
		'status' => ['type' => 'bool'],
		'args' => ['type' => 'array'],
	];

	public function r($r, $c = true) {
		if ($c && $this->ttl) {
			$key = json_encode(['role' => $r->role, 'node' => $r->node]);
			$this->slave ? $this->cache->add($r, $key, get_class($this), $this->ttl) : $this->cache->set($r, $key, get_class($this), $this->ttl);
		}
		if (!is_array($r->args)) {
			$r->args = $r->args && ($args = @unserialize($r->args)) ? $args : [];
		}
		$this->data[$r->role][$r->node] = $r;
		return $r;
	}
}