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
/*	Created: UTC 2015-04-03 07:05:11
/*	Updated: UTC 2015-04-03 07:05:16
/*
/* ************************************************************************** */
namespace Loli\HTTP;
use Loli\Exception as _Exception;
class_exists('Loli\Exception') || exit;
class Exception extends _Exception{
	public function __construct($message, $code = 0, Exception $previous = NULL) {
		parent::__construct($message, $code ? $code : 500, $previous);
	}
}