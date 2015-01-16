<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-09 11:04:31
/*	Updated: UTC 2015-01-16 15:21:58
/*
/* ************************************************************************** */
namespace Admin\Resources;
use Loli\Controller\Resources\Base, Loli\Model;
class_exists('Loli\Controller\Resources\Base') || exit;
class Style extends Base{
	use Model;
	public $default = ['type' => 'text/css', 'media' => 'all', 'priority' => 10, 'global' => true, 'login' => true];

	public $attr = ['global'];

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
		$k = 'style-' . preg_replace('/[^0-9a-z_-]/i','-', $key);
 		if (empty($args['id'])) {
			$args['id'] = $k;
		}

		$args['class'] = empty($args['class']) ? [] : (is_array($args['class']) ? $args['class'] : explode(' ', $args['class']));
		$args['class'][] = $k;
		$args['class'] = implode(' ', $args['class']);

		if (!$if = empty($args['if'])) {
			echo '<!--[if '. $args['if'] .']>';
		}

		if ($args['call']) {
			echo '<style' . $this->attr($args) .'>';
			is_string($value) && strpos($value, '.') !== false ? (require $value) : call_user_func($value, $this, $key);
			echo '</style>';
		} else {
			echo '<link' . $this->attr(['href' => $value, 'rel' => 'stylesheet'] + $args) .'/>';
		}
		if (!$if) {
			echo '<![endif]-->';
		}
		echo "\n";
	}
}