<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-04-07 15:12:23
/*
/* ************************************************************************** */
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-01-15 13:01:52
/*	Updated: UTC 2015-04-07 15:12:23
/*
/* ************************************************************************** */
namespace Loli\Captcha;
use Loli\Exception;


class GD extends Base{

	public function mime() {
		if (function_exists('imagepng')) {
			return 'image/png';
		} else {
			return 'image/jpeg';
		}
	}
	public function display() {
		$this->_background();
		$this->_ttftext();
		if ($this->mime() === 'image/png') {
			imagepng($this->im);
		} else {
			imagejpeg($this->im, false, 100);
		}
	}



	/**
	 * _background
	 */
	private function _background() {

		// 创建图片
		$this->im = imagecreatetruecolor($this->width, $this->height);



		// 创建背景颜色
		if (!$color = $this->rgb($this->background)) {
			$color = ['red' => 255, 'green' => 255,  'blue' => 255];
		}
		imagefilledrectangle($this->im, 0, 0, $this->width, $this->height, imagecolorallocate($this->im, $color['red'], $color['green'], $color['blue']));


		// 创建背景图片
		if ($background = $this->background()) {
			$bg = imagecreatefromjpeg($background);
			$x = imageSX($bg);
			$y = imageSY($bg);
			imagecopymerge($this->im, $bg, 0, 0, $x <= $this->width ? 0 : mt_rand(0, $x - $this->width), $y <= $this->height ? 0 : mt_rand(0, $y - $this->height), $x, $y, $this->pctBackground);
			imagedestroy($bg);
		}
	}


	/**
	 * _ttftext
	 */
	private function _ttftext() {

		if (!function_exists('imagettftext')) {
			throw new Exception('Freetype is not supported');
		}

		// 创建文字颜色
		if (!$color = $this->rgb($this->color)) {
			$color = ['red' => 0, 'green' => 0,  'blue' => 0];
		}
		$color = imagecolorallocate($this->im, $color['red'], $color['green'], $color['blue']);

		$line = $this->line();

		$font = $this->font();

		// for 循环写入字体
		$len = mb_strlen($this->code);
		for ($i = 0; $i < $len; ++$i) {
			$size = $this->width / $len * $this->random($this->size[0], $this->size[1]);
			$angle = $this->angle ? $this->random($this->angle[0], $this->angle[1]) : 0;
			$x = isset($x) ? $x + $size * $this->random($this->spacing[0], $this->spacing[1]) : $this->random(0, $this->width /(($this->spacing[0]+ $this->spacing[1] + $this->size[0] + $this->size[1])/1.4));
			$y = isset($y) ? ($y <= $size ? $y * $this->random(1,1.2) : ($y >= $this->height ? $y * $this->random(0.8, 1) : $y * $this->random(0.9,1.1))) : $this->random($size, $this->height);
			$text = mb_substr($this->code, $i, 1);
			imagettftext($this->im, $size, $angle, $x, $y, $color, $font, $text);
			$line && ($i%2) === 0 && imagettftext($this->im, $size + $this->random(-3, 3), $this->random($this->angle[0], $this->angle[1]), $x, $y, $color, $line, mt_rand(0, 9));
		}
	}
}