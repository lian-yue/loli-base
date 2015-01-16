<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-10 06:55:46
/*	Updated: UTC 2015-01-16 08:08:59
/*
/* ************************************************************************** */
namespace Model\Admin;
use Loli\Query;
class_exists('Loli\Query') || exit;
class Log extends Query{
	public $table = 'admin_log';

	public $limit = 10;

	public $args = [
		'ID' => '',
		'userID' => '',
		'nodeID' => '',
		'IP' => '',
		'dateline' => '>=',
	];

	public $defaults = [
		'userID' => '',
		'nodeID' => '',
		'IP' => '',
		'value' => null,
		'dateline' => 0,
	];

	public $insert_id = 'ID';

	public $primary = ['ID'];

	public $add = true;

	public $create = [
		'ID' => ['type' => 'int', 'unsigned' => true, 'increment' => true, 'primary' => 0],
		'userID' => ['type' => 'int', 'unsigned' => true, 'key' => ['nodeUserDate' => 1, 'userNodeDate' => 0]],
		'nodeID' => ['type' => 'int', 'unsigned' => true, 'key' => ['nodeUserDate' => 0, 'userNodeDate' => 1]],
		'IP' => ['type' => 'text', 'length' => 40],
		'value' => ['type' => 'text', 'length' => 67108864],
		'dateline' =>  ['type' => 'int', 'unsigned' => true, 'key' => ['nodeUserDate' => 2, 'userNodeDate' => 2]],
	];

	public function w($w, $old, $args) {
		if (!$w['userID'] || !$w['nodeID']) {
			return false;
		}
		$w['IP'] = empty($w['IP']) ? current_ip() : $w['IP'];
		$w['value'] = is_array($w['value']) || is_object($w['value']) ? ($w['value'] ? serialize($w['value']) : '') : addslashes($w['value']);
		$w['dateline'] = empty($w['dateline']) ? time() : $w['dateline'];
		return $w;
	}
}