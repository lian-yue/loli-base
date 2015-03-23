<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-27 09:59:36
/*	Updated: UTC 2015-03-22 08:01:03
/*
/* ************************************************************************** */
namespace Loli\DB;
class_exists('Loli\DB\Exception') || exit;
class ConnectException extends Exception{
	protected $level = 4;
	protected $state = 'HY000';
}