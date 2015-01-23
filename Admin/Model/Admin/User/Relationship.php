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
/*	Updated: UTC 2015-01-22 15:09:04
/*
/* ************************************************************************** */
namespace Model\Admin\User;
use Loli\RBAC\Relationship as Relationship_;
class_exists('Loli\RBAC\Relationship') || exit;
class Relationship extends Relationship_{
	public $table = 'admin_user_relationship';
}


