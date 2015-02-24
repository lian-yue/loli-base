<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-16 13:21:40
/*	Updated: UTC 2015-02-24 03:45:18
/*
/* ************************************************************************** */

/**
 *  错误控制器
 *  使用返回错误  (throw|return) new Error(错误码, 附加数据可选, 上一个错误可选);
 *  使用多个错误
 *  $error = null;
 *  $error = new Error('User empty', [], $error);
 *  $error = new Error('Password empty', [], $error);
 *  (throw|return) $error;
 *
 * 返回只能用于 返回视图的函数中
 */
namespace Loli\HMVC;
class Error extends Message{
	protected $code = 500;
	protected $title = 'Error Messages';
	protected $query = 'errors';
}