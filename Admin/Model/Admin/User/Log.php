<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-09 07:33:20
/*	Updated: UTC 2015-01-16 08:09:42
/*
/* ************************************************************************** */
namespace Model\Admin\User;
use Loli\Query;
class_exists('Loli\Query') || exit;
class Log extends Query{
	public $table = 'admin_user_log';

	public $limit = 10;

	public $args = [
		'ID' => '',
		'userID' => '',
		'type' => '',
		'IP' => '',
		'dateline' => '>=',
	];

	public $defaults = [
		'userID' => 0,
		'type' => 0,
		'value' => '',
		'IP' => '',
		'dateline' => 0,
	];

	public $insert_id = 'ID';

	public $primary = ['ID'];

	public $add = true;

	public $create = [
		'ID' => ['type' => 'int', 'unsigned' => true, 'increment' => true, 'primary' => 0],
		'userID' => ['type' => 'int', 'unsigned' => true, 'key' => ['userDateType' => 0]],
		'IP' => ['type' => 'text', 'length' => 40, 'key' => ['IPDateType' => 0]],
		'type' =>  ['type' => 'int', 'length' => 1, 'key' => ['IPDateType' => 1, 'userDateType' => 1]],
		'dateline' =>  ['type' => 'int', 'unsigned' => true, 'key' => ['IPDateType' => 2, 'userDateType' => 2]],
		'value' => ['type' => 'text', 'length' => 255],
	];

	public function w($w, $old, $args) {
		if (!$w['userID']) {
			return false;
		}
		$w['IP'] = empty($w['IP']) ? current_ip() : $w['IP'];
		$w['dateline'] = empty($w['dateline']) ? time() : $w['dateline'];
		return $w;
	}
}