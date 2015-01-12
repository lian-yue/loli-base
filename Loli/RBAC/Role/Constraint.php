<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-11 12:29:46
/*	Updated: UTC 2015-01-12 08:16:19
/*
/* ************************************************************************** */
/**
*	角色互相排斥 RBAC2
*
*/
namespace Loli\RBAC;
use Loli\Query;
class Constraint extends Query{

	// 不能被排斥的
	public $not = [];

	public $args = [
		'roleID' => '',
		'constraint' => '',
	];

	public $defaults = [
		'roleID' => 0,
		'constraint' => 0,
		'priority' => 0,
	];

	public $primary = ['ID'];

	public $create = [
		'roleID' => ['type' => 'int', 'unsigned' => true, 'increment' => true, 'primary' => 0],
		'constraint' => ['type' => 'int', 'unsigned' => true, 'increment' => true, 'primary' => 1],
		'priority' => ['type' => 'int', 'length' => 1],
	];

	public $add = true;

	public $set = true;

	public $delete = true;

	public $adds = true;

	public $sets = true;

	public $deletes = true;


	// 角色约束 // 不约束继承的角色
	public function gets($roles) {
		$ret = [];
		foreach ((array) $roles as $roleID) {
			if (!is_array($results = $this->cache->get($roleID, get_class($this)))) {
				$results = $this->results(['roleID' => $roleID]);
				$this->slave ? $this->cache->add($results, $roleID, get_class($this), $this->ttl) : $this->cache->set($results, $roleID, get_class($this), $this->ttl);
			}
			$ret = array_merge($ret, $results);
		}
		return $ret;
	}


	// 更新了刷缓存
	public function c($new, $old, $args) {
		$roleID = $old->roleID ? $old->roleID : $new->roleID;
		$this->cache->delete($roleID, get_class($this));
		parent::c($new, $old, $args);
		$this->gets($roleID);
	}
}