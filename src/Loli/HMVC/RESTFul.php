<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-09 15:15:25
/*	Updated: UTC 2015-02-18 06:43:31
/*
/* ************************************************************************** */
namespace Loli\HMVC;
class_exists('Loli\HMVC\Controller') || exit;
class  RESTFul extends Controller{

	// 主要字段
	public $primary = [ 'user_id' => '\d', 'qq' => '[0-9a-zA-Z_-]'];

	// 默认
	public function __construct(Request &$request, Response &$response) {
		// 主要字段
		$primary = [];
		foreach ($this->primary as $name => $value) {
			$primary[] = '(?<'.$name.'>'. $value .'+)';
		}
		$primary = '/'. implode('/', $primary);

		$allows = [
			'index' => [
				[
					'GET' => '/',
				]
			],

			// 取得
			'get' => [
				[
					'GET' => $primary,
				]
			],

			// 添加
			'add' => [
				[
					'GET' => $primary . '/add',
					'POST' => '/',
					'POST' => $primary,
				]
			],


			// 写入 更新全部
			'set' => [
				[
					'GET' => $primary .'/set',
					'POST' => $primary .'/set',
					'PUT' => $primary,
				]
			],


			// 编辑
			'edit' => [
				[
					'GET' => $primary .'/edit',
					'POST' => $primary .'/edit',
					'PATCH' => $primary,
				]
			],

			// 删除
			'delete' => [
				[
					'GET' => $primary .'/delete',
					'POST' => $primary .'/delete',
					'DELETE' => $primary,
				]
			],

		];

		$this->allows = array_merge($allows, $this->allows);
		parent::__construct($request, $response);
	}
}