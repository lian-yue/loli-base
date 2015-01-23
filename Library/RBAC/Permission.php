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
/*	Updated: UTC 2015-01-22 15:03:59
/*
/* ************************************************************************** */
namespace Loli\RBAC;
use Loli\Query;
class_exists('Loli\Query') || exit;
class Permission extends Query{
	public $ttl = 0;

	public $args = [
		'roleID' => '',
		'nodeID' => '',
	];

	public $defaults = [
		'roleID' => 0,
		'nodeID' => 0,
		'status' => false,
		'private' => false,			// false = 公众 true = 私人 不能被继承
		'args' => [],				// 其他 limit 等参数 比如每天最多发帖多少次 某个板块什么的能发帖
	];

	public $primary = ['roleID', 'nodeID'];


	public $create = [
		'roleID' => ['type' => 'int', 'unsigned' => true, 'increment' => true, 'primary' => 0],
		'nodeID' => ['type' => 'int', 'unsigned' => true, 'increment' => true, 'primary' => 1],
		'status' => ['type' => 'bool'],
		'private' => ['type' => 'bool'],
		'args' => ['type' => 'array'],
	];

	public $add = true;
	public $set = true;
	public $update = true;
	public $delete = true;

	public $adds = true;
	public $sets = true;
	public $updates = true;
	public $deletes = true;

	public function r($r, $c = true) {
		if ($c && $this->ttl) {
			$key = json_encode(['roleID' => $r->roleID, 'nodeID' => $r->nodeID]);
			$this->slave ? $this->cache->add($r, $key, get_class($this), $this->ttl) : $this->cache->set($r, $key, get_class($this), $this->ttl);
		}
		if (!is_array($r->args)) {
			$r->args = $r->args && ($args = @unserialize($r->args)) ? $args : [];
		}
		$this->data[$r->roleID][$r->nodeID] = $r;
		return $r;
	}

	public function w($new, $old, $args) {
		if (isset($new['args'])) {
			$new['args'] = empty($new['args']) ? '' : $new['args'];
		}
	}
}