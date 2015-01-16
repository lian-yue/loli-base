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
/*	Updated: UTC 2015-01-16 08:09:32
/*
/* ************************************************************************** */
namespace Model\Admin\User;
use Loli\RBAC\Node as Node_;
class_exists('Loli\RBAC\Node') || exit;
class Node extends Node_{
	public $table = 'admin_user_node';
}