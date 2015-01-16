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
/*	Updated: UTC 2015-01-16 08:09:16
/*
/* ************************************************************************** */
namespace Model\Admin\User;
use Loli\RBAC\Role as Role_;
class_exists('Loli\RBAC\Role') || exit;
class Role extends Role_{
	public $table = 'admin_user_role';
	public function __construct() {
		$this->_reg('Constraint', ['file' => __CLASS__ .'/Role/Constraint.php']);
		$this->_reg('Inherit', ['file' => __CLASS__ .'/Role/Inherit.php']);
	}
}