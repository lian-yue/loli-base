<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-03 16:33:56
/*	Updated: UTC 2015-02-03 16:54:37
/*
/* ************************************************************************** */
namespace Loli\Search;
use Loli\Query;
class_exists('Loli\Query') || exit;
class Keyword extends Query{
	public $args = [
		'ID' => '',
		'keyword' => '',
		'status' => '',
		'length' => '',
		'number' => '>=',
		'language' => '',
		'dateline' => '<=',
	];


	public $defaults = [
		'keyword' => '',
		'status' => 0,
		'length' => 0,
		'number' => 0,
		'language' => 0,
		'dateline' => 0,
	];

	public $primary = ['ID'];


	public $create = [
		'ID' => ['type' => 'int', 'unsigned' => true, 'increment' => true, 'primary' => 0],
		'keyword' => ['type' => 'text', 'length' => 64, 'unique' => ['keyword' => 0]],
		'status' => ['type' => 'int', 'unsigned' => true, 'length' => 1, 'key' => ['status' => 0]],
		'length' => ['type' => 'int', 'unsigned' => true, 'length' => 1, 'key' => ['length' => 0]],
		'number' => ['type' => 'int', 'unsigned' => true, 'key' => ['number' => 0]],
		'language' => ['type' => 'int', 'length' => 2, 'unsigned' => true, 'key' => ['language' => 0]],
		'dateline' => ['type' => 'int', 'unsigned' => true, 'key' => ['dateline' => 0]],
	];

	public $add = true;

	public $update = ['status', 'number', 'language'];

	public $delete = true;

}