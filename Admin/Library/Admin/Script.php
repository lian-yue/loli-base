<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-09 11:04:19
/*	Updated: UTC 2015-01-16 15:21:11
/*
/* ************************************************************************** */
namespace Admin\Resources;
use Loli\Controller\Resources\Base, Loli\Model;
class_exists('Loli\Controller\Resources\Base') || exit;
class Script extends Base {
	use Model;
	public $default = ['type' => 'text/javascript', 'priority' => 10, 'global' => true, 'login' => true];

	public $attr = ['global', 'login'];

	public $global = true;

	public $login = false;

	public function call($value, $args, $key) {
		// 不允许未登录的
		if ($args['login'] && !$this->login) {
			return;
		}
		if ($args['call'] && $args['global']) {
			if (!$this->global) {
				is_string($value) && strpos($value, '.') !== false ? (require $value) : call_user_func($value, $this, $key);
				echo "\n";
			}
			return;
		} elseif (!$this->global) {
			return;
		}

		if (!$if = empty($args['if'])) {
			echo '<!--[if '. $args['if'] .']>';
		}
		if ($args['call']) {
			echo '<script' . $this->attr($args) .'>';
			is_string($value) && strpos($value, '.') !== false ? (require $value) : call_user_func($value, $this, $key);
			echo '</script>';
		} else {
			echo '<script' . $this->attr(['src' => $value] + $args) .'></script>';
		}
		if (!$if) {
			echo '<![endif]-->';
		}
		echo "\n";
	}
}