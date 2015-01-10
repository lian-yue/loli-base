<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-12-31 10:16:16
/*	Updated: UTC 2015-01-09 12:03:58
/*
/* ************************************************************************** */
namespace Loli\Controller\Resources;
class Script extends Base{
	public $default = ['type' => 'text/javascript', 'priority' => 10];
	public function call($value, $args, $key) {
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