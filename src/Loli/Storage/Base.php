<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-21 13:42:16
/*
/* ************************************************************************** */
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-01-15 13:01:52
/*	Updated: UTC 2015-04-03 07:16:17
/*
/* ************************************************************************** */
namespace Loli\Storage;
use finfo;
define('UPLOAD_ERR_MULTIPLE', 100);
define('UPLOAD_ERR_MIME', 101);
define('UPLOAD_ERR_MIME_TYPE', 102);


/*
	没有DIR 函数 扁平化 存放  可以比如放数据库什么的

 */

abstract class Base{

	protected $buffer = 2097152;

	public function __construct(array $args = []) {
		foreach ($args as $key => $value) {
			if ($key && $key{0} != '_' && $value !== NULL && isset($this->$key)) {
				$this->$key = $value;
			}
		}
	}

	/**
	*	上传文件
	*
	*	1 参数 远程文件
	*	2 参数 本地文件
	*
	*	返回值 true  false
	**/
	abstract public function put($remote, $local);

	/**
	*	下载 到本地文件
	*
	*	1 参数 远程文件
	*
	*	返回值 true false
	**/
	abstract public function get($remote);

	/**
	*	上传文件 已打开文件
	*
	*	1 参数 远程文件
	*	2 参数 已打开的文件资源
	*
	*	返回值 true  false
	**/
	abstract public function fput($remote, $resource);

	/**
	*	打开一个文件
	*
	*	1 参数 远程文件
	*
	*	返回值 true  false
	**/
	abstract public function fget($remote);

	/**
	*	上传文件 文件内容
	*
	*	1 参数远程地址
	*	2 参数 文件内容
	*
	*	返回值 bool
	**/
	abstract public function cput($remote, $contents);

	/**
	*	打开一个文件
	*
	*	1 参数 远程文件
	*
	*	返回值 true  false
	**/
	abstract public function cget($remote);

	/**
	*	删除上传文件
	*
	*	1 参数文件地址
	*
	*	返回值 true false
	**/
	abstract public function unlink($remote);

	/**
	*	删除上传文件 多个
	*
	*	1 参数文件地址 数组
	*
	*	返回值 true false
	**/
	abstract public function unlinks($remote);


	/**
	*	取得文件大小
	*
	*	1 参数 文件地址
	*
	*	返回值 true false
	**/
	abstract public function size($remote);

	/**
	*	判断是否 是文件
	*
	*	1 参数 文件地址
	*
	*	返回值 true false
	**/
	abstract public function exists($remote);

	/**
	*	移动文件
	*
	*	1 参数 源
	*	2 参数 目的
	*
	*	返回值 true false
	**/
	abstract public function rename($source, $destination);



	/**
	*	过滤 途径
	*
	*	1 参数 filter
	*
	*	返回值 string
	**/
	public function filter($path) {
		if (!$path = preg_replace('/[\/\\\\]+/', '/', trim($path, " \t\n\r\0\x0B/\\"))) {
			throw new Exception('Path can not be empty', 1);
		}
		$r = [];
		foreach (explode('/', $path) as $name) {
			if (!$name || trim($name, " \t\n\r\0\x0B.") != $name || preg_match('/[\\\"\<\>\|\?\*\:\/	]/', '', $name)) {
				throw new Exception('Path format', 1);
			}
			$r[] = $v;
		}
		return '/' . implode('/', $r);
	}


	public function mime($file) {
		$info = new finfo();
		if (!is_file($file)) {
			throw new Exception('File does not exist', 2);
		}
		return ['type' => $info->file($file, FILEINFO_MIME_TYPE), 'encoding' => $info->file($file, FILEINFO_MIME_ENCODING)];
	}
}