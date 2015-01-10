<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-10 16:52:24
/*	Updated: UTC 2015-01-10 17:01:25
/*
/* ************************************************************************** */
namespace Loli\RBAC\Role;
use Loli\Query;
class Inherit extends Query{
	public $args = [
		'roleID' => '',
		'inherit' => '',
	];

	public $defaults = [
		'roleID' => 0,
		'inherit' => 0,
	];

	public $primary = ['roleID', 'inheritID'];

	public $create = [
		'roleID' => ['type' => 'int', 'unsigned' => true, 'increment' => true, 'primary' => 0]],
		'inherit' => ['type' => 'int', 'unsigned' => true, 'increment' => true, 'primary' => 1]],
	];

	public $add = true;

	public $set = true;

	public $delete = true;

	public $adds = true;

	public $sets = true;

	public $deletes = true;

	// 读取某个角色的所有继承id
	public function role($roleID) {
		$roleID = (int) $roleID;
		if (!is_array($results = $this->cache->get($roleID, get_class($this)))) {
			$results = $this->results(['roleID' => $roleID]);
			$this->slave ? $this->cache->add($results, $roleID, get_class($this), $this->ttl) : $this->cache->set($results, $roleID, get_class($this), $this->ttl);
		}
		return $results;
	}

	// 更新了刷缓存
	public function c($new, $old, $args) {
		$roleID = $old->roleID ? $old->roleID : $new->roleID;
		$this->cache->delete($roleID, get_class($this));
		parent::c($new, $old, $args);
		$this->role($roleID);
	}
}