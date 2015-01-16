<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-10 06:34:32
/*	Updated: UTC 2015-01-16 16:22:33
/*
/* ************************************************************************** */
namespace Model;
use Loli\Model;
class_exists('Loli\Model') || exit;
class Admin{
	use Model;
	public function __construct() {
		$this->_reg('User', ['file' => __CLASS__ .'/Admin/User.php']);
	}
}