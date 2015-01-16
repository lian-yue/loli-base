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
/*	Updated: UTC 2015-01-16 08:09:23
/*
/* ************************************************************************** */
namespace Admin\User;
use Loli\RBAC\Permission as Permission_;
class_exists('Loli\RBAC\Permission') || exit;
class Permission extends Permission_{
	public $table = 'admin_user_permission';
}