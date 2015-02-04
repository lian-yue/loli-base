<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-03 16:45:32
/*	Updated: UTC 2015-02-03 16:52:23
/*
/* ************************************************************************** */
namespace Loli\Search;
use Loli\Query;
class_exists('Loli\Query') || exit;
class Log extends Query{
	public $args = [
		'ID' => '',
		'keywordID' => '',
		'token' => '',
		'IP' => '',
		'dateline' => '>=',
	];

	public $defaults = [
		'keywordID' => 0,
		'token' => '',
		'IP' => '',
		'dateline' => 0,
	];

	public $primary = ['ID'];

	public $create = [
		'ID' => ['type' => 'int', 'length' => 8, 'unsigned' => true, 'increment' => true, 'primary' => 0],
		'keywordID' => ['type' => 'int', 'unsigned' => true, 'key' => ['keywordID' => 0]],
		'token' => ['type' => 'text', 'length' => 16, 'key' => ['token' => 0]],
		'IP' => ['type' => 'text', 'length' => 40, 'key' => ['IP' => 0]],
		'dateline' => ['type' => 'int', 'unsigned' => true, 'key' => ['dateline' => 0]],
	];

	public $add = true;

}