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
/*	Updated: UTC 2015-01-09 15:08:12
/*
/* ************************************************************************** */
namespace Admin\\Resources;
use Loli\Controller\Resources\Script as _Script;

class Script extends _Script {
	public $default = ['type' => 'text/javascript', 'priority' => 10, 'global' => true, 'login' => true];

	public $attr = ['global', 'login'];

	public $global = true;

	public $login = true;

	public function call($value, $args, $key) {
		// 不允许未登录的
		if ($args['login'] && (!$this->admin->current_id || !$this->admin->current_level)) {
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