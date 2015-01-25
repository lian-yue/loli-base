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
/*	Updated: UTC 2015-01-25 15:01:39
/*
/* ************************************************************************** */
namespace Loli\RBAC;
use Loli\Query;
class_exists('Loli\Query') || exit;
class Node extends Query{
	public $args = [
		'ID' => '',
		'parent' => '',
		'key' => '',
		'type' => '',
	];

	public $defaults = [
		'parent' => 0,
		'key' => '',
		'type' => 0,		// 0 = 导航节点(可包含)				1 = 读节点(不可包含)			2 = 请求动作节点(不可包含)
		'name' => '',
		'sort' => 0,
		'description' => '',
	];

	public $add = true;

	public $update = ['name', 'sort', 'description'];

	public $delete = true;

	public $primary = ['ID'];

	public $unique = [['parent', 'key']];

	public $create = [
		'ID' => ['type' => 'int', 'unsigned' => true, 'increment' => true, 'primary' => 0],
		'parent' => ['type' => 'int', 'unsigned' => true, 'unique' => ['parent_key' => 0]],
		'key' => ['type' => 'text', 'length' => 64, 'unique' => ['parent_key' => 0]],
		'type' => ['type' => 'int', 'length' => 1],
		'name' => ['type' => 'text', 'length' => 32],
		'sort' => ['type' => 'int', 'length' => 1],
		'description' => ['type' => 'text', 'length' => 65535],
	];

	public function key($key, $parent = 0) {
		if (!($key = (string) $key) || !($parent = (int) $parent)) {
			return false;
		}
		if ($r = $this->cache->get($parent . '.' . $key, get_class($this))) {
			return $this->r($r, false);
		}
		if ($r = $this->row(['key' => $key, 'parent' => $parent])) {
			$this->slave ? $this->cache->add($r, $parent . '.' . $key, get_class($this), $this->ttl) : $this->cache->set($r, $parent . '.' . $key, get_class($this), $this->ttl);
		}
		return $r;
	}

	public function c($new, $old, $args) {
		$a = $new ? $new : $old;
		$this->cache->delete($a->parent . '.' . $a->key, get_class($this));
		parent::c($new, $old, $args);
		$this->key($a->key, $a->parent);
	}
}