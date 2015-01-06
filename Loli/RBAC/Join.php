<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-12-31 16:30:57
/*	Updated: UTC 2015-01-06 13:34:01
/*
/* ************************************************************************** */
namespace Loli\RBAC;
use Loli\Query;
class Join extends Query{
	public $args = [
		'user' => '',
		'role' => '',
	];

	public $defaults = [
		'user' => 0,
		'role' => 0,
	];

	public $primary = ['user', 'role'];

	public $create = [
		'user' => ['type' => 'int', 'unsigned' => true, 'increment' => true, 'primary' => 0]],
		'role' => ['type' => 'int', 'unsigned' => true, 'increment' => true, 'primary' => 1]],
	];

	public $add = true;

	public $set = true;

	public $delete = true;

	public $adds = true;

	public $sets = true;

	public $deletes = true;

	// 读某个用户的所有角色
	public function role($user) {
		$user = (int) $user;
		if (!is_array($results = $this->cache->get($user, get_class($this)))) {
			$results = $this->results(['user' => $user]);
			$this->slave ? $this->cache->add($results, $user, get_class($this), $this->ttl) : $this->cache->set($results, $user, get_class($this), $this->ttl);
		}
		return $results;
	}

	// 更新了刷缓存
	public function c($new, $old, $args) {
		$user = $old->user ? $old->user : $new->user;
		$this->cache->delete($user, get_class($this));
		parent::c($new, $old, $args);
		$this->role($user);
	}
}