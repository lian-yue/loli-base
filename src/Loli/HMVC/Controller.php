<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-11-20 03:56:25
/*	Updated: UTC 2015-02-21 15:39:40
/*
/* ************************************************************************** */
namespace Loli\HMVC;
use Loli\Model;
trait_exists('Loli\Model', true) || exit;
class Controller{
	use Model;
	protected $request, $response;

	public $allows = [
		'index' => [
			[
				'GET' => '/(list)?',
			]
		],

		// 取得
		'get' => [
			[
				'GET' => '/$id',
			]
		],

		// 添加
		'add' => [
			[
				'POST' => '/',
				'POST' => '/$id',
			]
		],


		// 写入 更新全部
		'set' => [
			[
				'GET' => '/$id/set',
				'POST' => '/$id/set',
				'PUT' => '/$id',
			]
		],


		// 编辑
		'edit' => [
			[
				'GET' => '/$id/edit',
				'POST' => '/$id/edit',
				'PATCH' => '/$id',
			]
		],

		// 删除
		'delete' => [
			[
				'GET' => '/$id/delete',
				'POST' => '/$id/delete',
				'DELETE' => '/$id',
			]
		],

	];

	// 默认
	public function __construct(Request &$request, Response &$response) {
		$this->request &= $request;
		$this->response &= $response;
		$this->messages &= $messages;
	}

	// 不支持的方法
	public function __call($name, $args) {
		$this->messages->add(404);
	}
}