<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-09 07:29:23
/*	Updated: UTC 2015-01-16 08:09:58
/*
/* ************************************************************************** */
namespace Model\Admin\User;
use Loli\RBAC\Join as Join_;
class_exists('Loli\RBAC\Join') || exit;
class Join extends Join_{
	public $table = 'admin_user_join';
}


