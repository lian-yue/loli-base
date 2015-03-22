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
/*	Updated: UTC 2015-03-21 12:06:19
/*
/* ************************************************************************** */
namespace Loli\DB;
class_exists('Loli\DB\Exception') || exit;
class ConnectException extends Exception{
	protected $severity = 3;
	protected $state = 'HY000';
}