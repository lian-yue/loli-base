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
/*	Updated: UTC 2015-01-26 15:17:11
/*
/* ************************************************************************** */
namespace Loli\RBAC;
use Loli\Query;
class_exists('Loli\Query') || exit;
class Role extends Query{
	public $args = [
		'ID' => '',
		'status' => '',
	];

	public $defaults = [
		'name' => '',
		'status' => false,		// 角色是否有效
		'description' => '',
	];

	public $primary = ['ID'];

	public $create = [
		'ID' => ['type' => 'int', 'unsigned' => true, 'increment' => true, 'primary' => 0],
		'name' => ['type' => 'text', 'length' => 64],
		'status' => ['type' => 'bool', 'key' => ['status' => 0]],
		'description' => ['type' => 'text', 'length' => 65535],
	];

	public $add = true;

	public $update = true;

	public $delete = true;
}