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
/*	Updated: UTC 2015-01-23 05:10:48
/*
/* ************************************************************************** */
namespace Model;
use Loli\Model as Model_;
trait_exists('Loli\Model', true) || exit;
class Admin{
	use Model_;
	public function __construct() {
		$this->_reg('User');
		$this->_reg('Log');
	}
}