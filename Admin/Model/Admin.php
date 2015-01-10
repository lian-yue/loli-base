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
/*	Updated: UTC 2015-01-10 07:23:53
/*
/* ************************************************************************** */
namespace Model;
use Loli\Model;
class Admin{
	use Model;
	public function __construct() {
		$this->_reg('User', ['file' => __CLASS__ .'/Admin/User.php']);
	}
}