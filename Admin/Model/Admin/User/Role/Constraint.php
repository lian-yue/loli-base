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
/*	Updated: UTC 2015-01-11 14:58:19
/*
/* ************************************************************************** */
namespace Model\Admin\User\Role;
use Loli\RBAC\Role\Constraint as Constraint_;
class Constraint extends Constraint_{
	public $table = 'admin_user_role_constraint';
}