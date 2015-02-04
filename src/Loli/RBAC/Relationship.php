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
/*	Updated: UTC 2015-01-22 15:03:48
/*
/* ************************************************************************** */
namespace Loli\RBAC;
use Loli\Query;
class_exists('Loli\Query') || exit;
class Relationship extends Query{
	public $args = [
		'userID' => '',
		'roleID' => '',
	];

	public $defaults = [
		'userID' => 0,
		'roleID' => 0,
	];

	public $primary = ['userID', 'roleID'];

	public $create = [
		'userID' => ['type' => 'int', 'unsigned' => true, 'increment' => true, 'primary' => 0],
		'roleID' => ['type' => 'int', 'unsigned' => true, 'increment' => true, 'primary' => 1],
		'expires' => ['type' => 'datetime'],			// 过期如果 是 0000-00-00 00:00:00 的话永不过期 gmt 时间
	];

	public $add = true;

	public $set = true;

	public $delete = true;

	public $adds = true;

	public $sets = true;

	public $deletes = true;

	// 读某个用户的所有角色
	public function gets($userID) {
		$userID = (int) $userID;
		if (!is_array($results = $this->cache->get($userID, get_class($this)))) {
			$results = $this->results(['userID' => $userID]);
			$this->slave ? $this->cache->add($results, $userID, get_class($this), $this->ttl) : $this->cache->set($results, $userID, get_class($this), $this->ttl);
		}
		return $results;
	}

	// 更新了刷缓存
	public function c($new, $old, $args) {
		$userID = $old->userID ? $old->userID : $new->userID;
		$this->cache->delete($userID, get_class($this));
		parent::c($new, $old, $args);
		$this->role($userID);
	}
}