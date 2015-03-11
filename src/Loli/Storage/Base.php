<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-01-15 13:01:52
/*	Updated: UTC 2015-02-26 10:01:47
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
		static $info;
		if (!isset($info)) {
			$info = new finfo();
		}
		if (!is_file($file)) {
			throw new Exception('File does not exist', 2);
		}
		return ['type' => $info->file($file, FILEINFO_MIME_TYPE), 'encoding' => $info->file($file, FILEINFO_MIME_ENCODING)];
	}

	/**
	*	表单的文件
	*
	*	1 参数 key
	*	2 参数 size 最大限制
	*	2 参数 extension 允许的后缀 或 mime类型
	*	3 参数 multiple 最大一次性传多少个
	*
	*	返回值 数组
	**/
	public function post($key, $size = 0, $mimeType = [], $multiple = 1) {
		if (empty($_FILES[$key])) {
			return [];
		}

		// 整理 文件数组
		$files = $this->_files($_FILES[$key]['name'], $_FILES[$key]['type'], $_FILES[$key]['tmp_name'], $_FILES[$key]['error'], $_FILES[$key]['size']);

		$a = [];
		foreach ($files as $v) {
			$v += pathinfo($v['name']) + ['dirname' =>'', 'basename' => '', 'extension' => '', 'filename' => '', 'encoding' => ''];
			if ($v['error']) {
				$a[] = $v;
				continue;
			}

			if ($multiple && count($a) >= $multiple) {
				$v['error'] = UPLOAD_ERR_MULTIPLE;
				$a[] = $v;
				continue;
			}

			if (!$mime = $this->mime($v['tmp_name'])) {
				$v['error'] = UPLOAD_ERR_MIME;
				$a[] = $v;
				continue;
			}
			$v = $mime + $v;
			if ($mimeType && !in_array($v['type'], $mimeType) && !in_array(strtolower($v['extension']), $mimeType)) {
				$v['error'] = UPLOAD_ERR_MIME_TYPE;
				$a[] = $v;
				continue;
			}

			if ($size && $v['size'] > $size) {
				$v['error'] = UPLOAD_ERR_FORM_SIZE;
				$a[] = $v;
				continue;
			}
			$a[] = $v;
		}
		return $a;
	}

	private function _files($name, $type, $tmp_name, $error, $size) {
		$files = [];
		if (is_array($name)) {
			foreach ($name as $key => $value) {
				$files = array_merge($files, $this->_files($name[$key], $type[$key], $tmp_name[$key], $error[$key], $size[$key]));
			}
		} else {
			$files[] = ['name' => $name, 'type' => $type, 'tmp_name' => $tmp_name, 'error' => $error, 'size' => $size];
		}
		return $files;
	}
}