<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-11 14:56:14
/*	Updated: UTC 2015-01-16 08:10:16
/*
/* ************************************************************************** */
namespace Model\Admin\User\Role;
use Loli\RBAC\Role\Constraint as Constraint_;
class_exists('Loli\RBAC\Role\Constraint') || exit;
class Constraint extends Constraint_{
	public $table = 'admin_user_role_constraint';
}