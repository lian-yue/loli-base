<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-07 05:50:20
/*	Updated: UTC 2015-01-10 06:01:49
/*
/* ************************************************************************** */
namespace Loli\Captcha;

abstract class Base {

	// 验证码值
	public $code = '1234';

	// 图片宽度
	public $width = 150;

	// 图片高度
	public $height	= 50;

	// 文字颜色
	public $color = [];

	// 文字角度
	public $angle 	= [-20, 20];

	// 文字间隔
	public $spacing = [0.6, 0.8];

	// 文字大小
	public $size = [0.6, 0.8];

	// 文字字体目录
	public $font = '';

	// 线条
	public $line = true;

	// 背景颜色
	public $background	= [];

	// 背景目录
	public $dirBackground = '';

	// 背景透明度
	public $pctBackground = 100;

	// 储存图片
	protected $im;

	public function __construct($code = false) {
		if ($code) {
			$this->code = $code;
		}
	}

	abstract public function display();


	// 随机小数
	protected function rand($min = 0, $max = 1) {
	    return $min + mt_rand() / mt_getrandmax() * ($max - $min);
	}


	// 字体
	protected function font() {
		$fontFile = __DIR__ .'/Fonts/Default.ttf';
		if ($this->font) {
			if (is_file($this->font)) {
				$fontFile = $this->font;
			} elseif (($fonts = dirlist($this->font)) || ($fonts = dirlist(__DIR__ . '/Fonts/'))) {
				$fontFile = $fonts[array_rand($fonts)];
			}
		}
		return $fontFile;
	}


	// 线条
	protected function line() {
		if (!$this->line) {
			return false;
		}
		$lineFile = __DIR__ .'/Lines/Default.ttf';
		if (is_string($this->line)) {
			if (is_file($this->line)) {
				$lineFile = $this->line;
			} elseif (($lines = dirlist($this->line)) || ($lines = dirlist(__DIR__ . '/Lines/'))) {
				$lineFile = $lines[array_rand($lines)];
			}
		}
		return $lineFile;
	}



	/**
	*	rgb 颜色
	*
	*	1 参数 rgb
	*
	*	返回值 array or false
	**/
	protected function rgb($rgb) {
		if ($rgb && is_array($rgb)) {
			foreach (['red', 'green', 'blue'] as $v) {
				$rgb[$v] = empty($rgb[$v]) ? 0 : $rgb[$v];
			}
			return $rgb;
		}
		if (!$rgb || !is_string($rgb) || strlen($rgb) < 6) {
			return false;
		}
		if ($rgb{0} == '#') {
			$rgb =  substr($rgb, 1);
		}
		return [
			'red' => hexdec(substr($rgb, 0, 2)),
			'green' => hexdec(substr($rgb, 2, 2)),
			'blue' => hexdec(substr($rgb, 4, 2)),
		];
	}
}