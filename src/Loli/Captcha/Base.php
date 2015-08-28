<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-21 13:42:16
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
/*	Created: UTC 2015-01-07 05:50:20
/*	Updated: UTC 2015-04-07 15:05:50
/*
/* ************************************************************************** */
namespace Loli\Captcha;

abstract class Base {

	// 验证码值
	protected $code = '1234';

	// 图片宽度
	protected $width = 150;

	// 图片高度
	protected $height	= 50;

	// 文字颜色
	protected $color = [];

	// 文字角度
	protected $angle 	= [-20, 20];

	// 文字间隔
	protected $spacing = [0.6, 0.8];

	// 文字大小
	protected $size = [0.6, 0.8];

	// 文字字体目录
	protected $font = '';

	// 线条
	protected $line = true;

	// 背景颜色
	protected $background	= [];

	// 背景目录
	protected $dirBackground = '';

	// 背景透明度
	protected $pctBackground = 100;

	// 储存图片
	protected $im;
	/**
	 * __construct
	 * @param boolean $code
	 */
	public function __construct($code = false) {
		if ($code) {
			$this->code = $code;
		}
	}

	/**
	 * display
	 * @return boolean
	 */
	abstract public function mime();
	abstract public function display();


	/**
	 * _files
	 * @param  string $dir
	 * @param  array  $extensions
	 * @return array
	 */
	private function _files($dir, array $extensions = []) {
		$extensions = array_map('strtolower', $extensions);
		$files = [];
		if (is_dir($dir)) {
			$open = opendir($dir);
			while ($read = readdir($open)) {
				if (in_array($read, ['.', '..']) || !is_file($path = $dir .'/'. $read) || ($extensions && (!($extension = strtolower(pathinfo($read, PATHINFO_EXTENSION))) || !in_array($extension, $extensions)))) {
					continue;
				}
				$files[] = $path;
			}
			closedir($open);
		}
		return $files;
	}

	/**
	 * rand float
	 * @param  float|integer $min
	 * @param  float|integer $max
	 * @return float
	 */
	protected function rand($min = 1, $max = 1) {
		return $min + mt_rand() / mt_getrandmax() * ($max - $min);
	}


	/**
	 * font    Font file
	 * @return string
	 */
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


	/**
	 * line   Line file
	 * @return string|boolean
	 */
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

	/**
	 * background
	 * @return string|boolean
	 */
	protected function background() {
		if (!$this->dirBackground || !($backgrounds = $this->_files($this->dirBackground, ['jpg', 'jpeg', 'png', 'gif', 'webp']))) {
			return false;
		}
		return $backgrounds[array_rand($backgrounds)];
	}



	/**
	 * rgb
	 * @param  $rgb
	 * @return array|boolean
	 */
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
		if ($rgb{0} === '#') {
			$rgb =  substr($rgb, 1);
		}
		return [
			'red' => hexdec(substr($rgb, 0, 2)),
			'green' => hexdec(substr($rgb, 2, 2)),
			'blue' => hexdec(substr($rgb, 4, 2)),
		];
	}
}