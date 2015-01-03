<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-12-31 10:16:12
/*	Updated: UTC 2014-12-31 10:41:33
/*
/* ************************************************************************** */
namespace Loli\Resources;
class Style extends Base{
	public $default = ['type' => 'text/css', 'media' => 'all', 'priority' => 10];
	public function call($value, $args, $key) {
		$k =  'style_' . preg_replace('/[^0-9a-z_-]/i','-', $key);
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