<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-01-15 13:01:52
/*	Updated: UTC 2014-12-31 09:38:43
/*
/* ************************************************************************** */
namespace Loli;

class Captcha{

	// 验证码值
	public $code = '1234';

	// 图片宽度
	public $width = 0;

	// 图片高度
	public $height	= 0;

	// 文字颜色
	public $color = [];

	// 文字角度
	public $angle 	= [-20, 20];

	// 文字间隔
	public $spacing = [0.6, 0.8];

	// 文字大小
	public $size = [0.6, 0.8];

	// 添加线条
	public $line = true;

	// 文字字体目录
	public $font = '';

	// 背景颜色
	public $background	= [];

	// 背景目录
	public $dirBackground = '';

	// 背景透明度
	public $pctBackground = 100;

	//** 储存图片
	public $im;

	/**
	*	默认自动载入
	*
	*	1 参数 code
	*
	*	五返回值
	**/
	public function __construct($code = false) {
		if ($code) {
			$this->code = $code;
		}
	}


	public function display() {
		$this->background();
		$this->ttftext();
		if (function_exists('imagepng')) {
			@header('Content-type: image/png');
			imagepng($this->im);
		} else {
			@header('Content-type: image/jpeg');
			imagejpeg($this->im, false, 100);
		}
	}


	// 随机小数
	public function rand($min = 0, $max = 1) {
	    return $min + mt_rand() / mt_getrandmax() * ($max - $min);
	}


	/**
	*	创建背景
	*
	*	1 参数 code
	*
	*	五返回值
	**/
	public function background() {

		// 创建图片
		$this->im = imagecreatetruecolor($this->width, $this->height);



		// 创建背景颜色
		if (!$color = $this->rgb($this->background)) {
			$color = ['red' => 255, 'green' => 255,  'blue' => 255];
		}
		imagefilledrectangle ($this->im, 0, 0, $this->width, $this->height, imagecolorallocate($this->im, $color['red'], $color['green'], $color['blue']));



		// 创建背景图片
		if ($this->dirBackground && ($img = dirlist($this->dirBackground))) {
			$bg = imagecreatefromjpeg($img[array_rand($img)]);
			$x = imageSX($bg);
			$y = imageSY($bg);
			imagecopymerge($this->im, $bg, 0, 0, $x <= $this->width ? 0 : mt_rand(0, $x - $this->width), $y <= $this->height ? 0 : mt_rand(0, $y - $this->height), $x, $y, $this->pctBackground);
			imagedestroy($bg);
		}
	}



	public function ttftext(){

		// 创建文字颜色
		if (!$color = $this->rgb($this->color)) {
			$color = ['red' => 0, 'green' => 0,  'blue' => 0];
		}
		$color = imagecolorallocate($this->im, $color['red'], $color['green'], $color['blue']);

		// 线条颜色
		$lineFile = __DIR__ .'/Captcha/Line.ttf';


		// 查找字体
		$fontFile = __DIR__ .'/Captcha/Font.ttf';
		if ($this->font &&($font = dirlist($this->font))) {
			$fontFile = $font[array_rand($font)];
		}

		// for 循环写入字体
		$len = mb_strlen($this->code);
		for ($i = 0; $i < $len; $i++) {
			$size = $this->width / $len * $this->rand($this->size[0], $this->size[1]);
			$angle = $this->angle ? $this->rand($this->angle[0], $this->angle[1]) : 0;
			$x = isset($x) ? $x + $size * $this->rand($this->spacing[0], $this->spacing[1]) : $this->rand(0, $this->width /(($this->spacing[0]+ $this->spacing[1] + $this->size[0] + $this->size[1])/1.4));
			$y = isset($y) ? ($y <= $size ? $y * $this->rand(1,1.2) : ($y >= $this->height ? $y * $this->rand(0.8, 1) : $y * $this->rand(0.9,1.1))) : $this->rand($size, $this->height);
			$text = mb_substr($this->code, $i, 1);
			imagettftext($this->im, $size, $angle, $x, $y, $color, $fontFile, $text);
			$this->line && ($i%2) == 0 && imagettftext($this->im, $size + $this->rand(-3, 3), $this->rand($this->angle[0], $this->angle[1]), $x, $y, $color, $lineFile, mt_rand(0, 9));
		}
	}



	/**
	*	rgb 颜色
	*
	*	1 参数 rgb
	*
	*	返回值 array or false
	**/
	public function rgb($rgb) {
		if ($rgb && is_array($rgb)) {
			$rgb['red'] = empty($rgb['red']) ? 0 : $rgb['red'];
			$rgb['green'] = empty($rgb['green']) ? 0 : $rgb['green'];
			$rgb['blue'] = empty($rgb['blue']) ? 0 : $rgb['blue'];
			return $rgb;
		}

		if (!$rgb || !is_string($rgb) || strlen($rgb) != 6) {
			return false;
		}

		return [
			'red' => hexdec(substr($rgb, 0, 2)),
			'green' => hexdec(substr($rgb, 2, 2)),
			'blue' => hexdec(substr($rgb, 4, 2)),
		];
	}
}