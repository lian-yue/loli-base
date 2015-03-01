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
/*	Updated: UTC 2015-02-26 05:45:16
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


	// 字体文件 1参数默认地址
	private function _files($dir, array $extensions = []) {
		$extensions = array_map('strtolower', $extensions);
		$r = [];
		if (is_dir($dir)) {
			$open = opendir($dir);
			while ($read = readdir($open)) {
				if (in_array($read, ['.', '..']) || !is_file($path = $dir .'/'. $read) || ($extensions && (!($extension = strtolower(pathinfo($read, PATHINFO_EXTENSION))) || !in_array($extension, $extensions)))) {
					continue;
				}
				$r[] = $path;
			}
			closedir($open);
		}
		return $r;
	}

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
			} elseif (($fonts = $this->_files($this->font, ['ttf', 'otf'])) || ($fonts = $this->_files(__DIR__ . '/Fonts', ['ttf', 'otf']))) {
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
			} elseif (($lines = $this->_files($this->line, ['ttf', 'otf'])) || ($lines = $this->_files(__DIR__ . '/Lines', ['ttf', 'otf']))) {
				$lineFile = $lines[array_rand($lines)];
			}
		}
		return $lineFile;
	}

	// 背景
	protected function background() {
		if (!$this->dirBackground || !($backgrounds = $this->_files($this->dirBackground, ['jpg', 'jpeg', 'png', 'gif', 'webp']))) {
			return false;
		}
		return $backgrounds[array_rand($backgrounds)];
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