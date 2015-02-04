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
/*	Updated: UTC 2015-01-16 08:05:24
/*
/* ************************************************************************** */
/**
 * 用户继承 RBAC1
 */
namespace Loli\RBAC\Role;
use Loli\Query;
class_exists('Loli\Query') || exit;
class Inherit extends Query{
	public $args = [
		'roleID' => '',
		'inherit' => '',
	];

	public $defaults = [
		'roleID' => 0,
		'inherit' => 0,
	];

	public $primary = ['roleID', 'inherit'];

	public $create = [
		'roleID' => ['type' => 'int', 'unsigned' => true, 'increment' => true, 'primary' => 0],
		'inherit' => ['type' => 'int', 'unsigned' => true, 'increment' => true, 'primary' => 1],
	];

	public $add = true;

	public $set = true;

	public $delete = true;

	public $adds = true;

	public $sets = true;

	public $deletes = true;

	// 角色继承
	public function gets($roles) {
		$roles = (array) $roles;
		$ret = [];
		foreach ($roles as $roleID) {
			if (!is_array($results = $this->cache->get($roleID, get_class($this)))) {
				$results = [];
				$query = $roleID;
				while($query && ($inherits = $this->results(['roleID' => $query]))) {
					$query = [];
					foreach ($inherits as $inherit) {
						if (empty($results[$inherit->inherit])) {
							$query[] = $inherit->inherit;
							$results[$inherit->inherit] = $inherit;
						}
					}
				}
				$this->slave ? $this->cache->add($results, $roleID, get_class($this), $this->ttl) : $this->cache->set($results, $roleID, get_class($this), $this->ttl);
			}
			$ret += $results;
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