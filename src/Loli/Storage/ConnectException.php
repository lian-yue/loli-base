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
/*	Updated: UTC 2015-03-22 08:14:40
/*
/* ************************************************************************** */
namespace Loli\Storage;
use Loli\LogException;
class_exists('Loli\LogException') || exit;
class ConnectException extends LogException{
	protected $level = 4;
}