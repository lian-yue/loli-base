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
/*	Updated: UTC 2015-01-11 13:04:00
/*
/* ************************************************************************** */
namespace Loli\RBAC;
use Loli\Query;
class Role extends Query{
	public $args = [
		'ID' => '',
		'status' => '',
	];

	public $defaults = [
		'name' => '',
		'status' => false,
		'description' => '',
	];

	public $primary = ['ID'];

	public $create = [
		'ID' => ['type' => 'int', 'unsigned' => true, 'increment' => true, 'primary' => 0],
		'name' => ['type' => 'text', 'length' => 64],
		'status' => ['type' => 'bool', 'key' => ['status' => 0]],
	//	'transfer' => ['type' => 'bool'],						// 该用户组是否允许转移
		'description' => ['type' => 'text', 'length' => 65535],
	];

	public $add = true;

	public $update = true;

	public $delete = true;
}