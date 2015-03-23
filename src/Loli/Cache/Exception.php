<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-03-22 08:10:32
/*	Updated: UTC 2015-03-22 08:11:17
/*
/* ************************************************************************** */
namespace Loli\Cache;
use Loli\LogException;
class_exists('Loli\LogException') || exit;
class Exception extends LogException{
	protected $level = 4;
}