<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-11 14:56:19
/*	Updated: UTC 2015-01-16 08:10:28
/*
/* ************************************************************************** */
namespace Model\Admin\User\Role;
use Loli\RBAC\Role\Inherit as Inherit_;
class_exists('Loli\RBAC\Role\Inherit') || exit;
class Inherit extends Inherit_{
	public $table = 'admin_user_role_inherit';
}