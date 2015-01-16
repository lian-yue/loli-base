<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-02-16 08:55:06
/*	Updated: UTC 2015-01-16 08:03:57
/*
/* ************************************************************************** */
namespace Loli\Image;
class_exists('Loli\Image\Base') || exit;
class GD extends Base {

	// 旧 图片
	private $_old = false;

	// 图片类型
	private $_type = false;

	public function create($a, $type = false) {
		$this->destroy();
		if ($a && is_file($a) && ($info = @getimagesize($a)) && ($a = file_get_contents($a)) && ($this->im = @imagecreatefromstring($a)) && ($this->_old = @imagecreatefromstring($a))) {
			imagealphablending($this->im, false);
			imagesavealpha($this->im, true);
			$this->type($type ? $type : image_type_to_extension($info[2], false));
			return true;
		}
		return false;
	}


	public function destroy() {
		$this->_type = false;
		$this->_old && imagedestroy($this->_old);
		return $this->im && imagedestroy($this->im);
	}


	public function width() {
		return $this->im ? imagesx($this->im) : 0;
	}


	public function height() {
		return $this->im ? imagesy($this->im) : 0;
	}

	public function type($type = false) {
		if ($type) {
			if (empty($this->type[$type])) {
				$type = strtolower($type);
				reset($this->type);
				$this->_type = key($this->type);
				foreach ($this->type as $k => $v) {
					if (in_array($type, $v)) {
						$this->_type = $k;
						break;
					}
				}
			} else {
				$this->_type = intval($type);
			}
		}
		return $this->_type;
	}

	public function frames() {
		return $this->im ? 1 : false;
	}

	public function length(){
		return $this->im ? 0 : false;
	}

	public function rotate($angle) {
		if (!$this->im || !($this->im = imagerotate($this->im, $angle, 16777215 , 0))) {
			return false;
		}
		return true;
	}


	public function flip($mode = self::FLIP_HORIZONTAL) {
		if (!$this->im) {
			return false;
		}
		if (function_exists ('imageflip')){
			$arg =[ self::FLIP_HORIZONTAL => IMG_FLIP_HORIZONTAL, self::FLIP_VERTICAL => IMG_FLIP_VERTICAL, self::FLIP_BOTH => IMG_FLIP_BOTH];
			return imageflip($this->im , isset($arg[$mode]) ? $arg[$mode] : IMG_FLIP_BOTH);
		}
		$width = $this->width();
		$height = $this->height();

		$src_x = 0;
		$src_y = 0;
		$src_width = $width;
		$src_height = $height;

		switch ($mode) {
			case self::FLIP_HORIZONTAL:
				$src_y = $height -1;
				$src_height = -$height;
				break;
			case self::FLIP_VERTICAL:
				$src_x = $width -1;
				$src_width = -$width;
				break;
			default:
				$src_x = $width -1;
				$src_y = $height -1;
				$src_width = -$width;
				$src_height = -$height;
				break;
		}
		$image = $this->_create($width, $height);
		if (!imagecopyresampled($image, $this->im, 0, 0, $src_x, $src_y , $width, $height, $src_width, $src_height)) {
			return false;
		}

		imagedestroy($this->im);
		$this->im = $image;
		return true;
	}


	public function text($text, $font, $size = 12, $color = '#000000', $x = 0, $y = 0, $angle = 0,  $opacity = 1.0) {
		if (!$this->im) {
			return false;
		}
		$angle = $angle % 360;
        $info = imagettfbbox($size, $angle, $font, $text);


        if ($color && is_array($color)) {
			foreach (['red', 'green', 'blue'] as $v) {
				$$v = empty($color[$v]) ? 0 : $color[$v];
			}
		} elseif ($color && is_string($color) && strlen($color) >= 6) {
			if ($color{0} == '#') {
				$color =  substr($color, 1);
			}
			$red = hexdec(substr($color, 0, 2));
			$green = hexdec(substr($color, 2, 2));
			$blue = hexdec(substr($color, 4, 2));
		} else {
			$blue  = $green = $red = 0;
		}


		$xN = $x < 0 || ($x && is_string($x) && $x{0} == '-');
		$w = abs(max($info[0], $info[2], $info[4], $info[6]) - min($info[0], $info[2], $info[4], $info[6]));
		if (is_string($x) && substr($x, -1, 1) == '%') {
			$x = ($this->width() - $w) * (($xN ? 100 + $x : $x) / 100);
		} elseif ($xN) {
			$x += $this->width() - $w;
		}

		if ($angle <= 90) {
			$x -= $info[6];
		} elseif ($angle <= 180) {
			$x -= $info[4];
		} elseif ($angle <= 270) {
			$x -= $info[2];
		} else {
			$x -= $info[0];
		}


		$yN = $y < 0 || ($y && is_string($y) && $y{0} == '-');
		$h = abs(max($info[1], $info[3], $info[5], $info[7]) - min($info[1], $info[3], $info[5], $info[7]));
		if (is_string($y) && substr($y, -1, 1) == '%') {
			$y = ($this->height() - $h) * (($yN ? 100 + $y : $y) / 100);
		} elseif ($yN) {
			$y += $this->height() - $h;
		}

		if ($angle <= 90) {
			$y -= $info[5];
		} elseif ($angle <= 180) {
			$y -= $info[3];
		} elseif ($angle <= 270) {
			$y -= $info[1];
		} else {
			$y -= $info[7];
		}

		imagealphablending($this->im, true);
		$color = imagecolorallocatealpha($this->im, $red, $green, $blue, 127 - $opacity * 127);
		imagettftext($this->im, $size, $angle, $x, $y, $color, $font, $text);
		imagealphablending($this->im, false);
		return true;
	}

	public function insert($file, $x = 0, $y = 0, $opacity = 1.0) {
		if (!$file || !is_file($file) || !($info = @getimagesize($file)) || !($data = file_get_contents($file)) || !($im = @imagecreatefromstring($data))) {
			return false;
		}
		unset($data);

		$xN = $x < 0 || ($x && is_string($x) && $x{0} == '-');
		if (is_string($x) && substr($x, -1, 1) == '%') {
			$x = ($this->width() - $info[0]) * (($xN ? 100 + $x : $x) / 100);
		} elseif ($xN) {
			$x += $this->width() - $info[0];
		}

		$yN = $y < 0 || ($y && is_string($y) && $y{0} == '-');
		if (is_string($y) && substr($y, -1, 1) == '%') {
			$y = ($this->height() - $info[1]) * (($yN ? 100 + $y : $y) / 100);
		} elseif ($yN) {
			$y += $this->height() - $info[1];
		}

        imagealphablending($im, true);
		$src =  $this->_create($info[0], $info[1]);
		imagealphablending($src, true);
		imagecopy($src, $this->im, 0, 0, $x, $y, $info[0], $info[1]);
		imagecopy($src, $im, 0, 0, 0, 0, $info[0], $info[1]);
		imagecopymerge($this->im, $src, $x, $y, 0, 0, $info[0], $info[1], $opacity * 100);
		imagedestroy($src);
		imagedestroy($im);
		return true;
	}



	public function resampled($new_w, $new_h, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) {
		if (!$this->im || !($image = $this->_create($new_w, $new_h)) || !imagecopyresampled($image, $this->im, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h)) {
			return false;
		}
		imagedestroy($this->im);
		$this->im = $image;
		return true;
	}




	public function save($save, $type = false) {
		if (!$save || !$this->im) {
			return false;
		}

		$info = pathinfo($save);
		if (!empty($info['extension'])) {
			foreach ($this->type as $k => $v) {
				if (in_array($info['extension'], $v)) {
					$type = $k;
				}
			}
		}

		if (self::TYPE_WEBP == $type) {
			if (!imagewebp($this->im, $save)) {
				return false;
			}
		} elseif (self::TYPE_GIF == $type) {
			$this->stotal();
			if (!imagegif($this->im, $save)) {
				return false;
			}
		} elseif (self::TYPE_PNG == $type) {
			$this->stotal();
			if (!imagepng($this->im, $save)) {
				return false;
			}
		} else {
			imageinterlace($this->im, true);
			if (!imagejpeg($this->im, $save, $this->quality)) {
				return false;
			}
		}

		// 权限
		$stat = stat(dirname($save));
		$perms = $stat['mode'] & 0000666;
		@chmod($save, $perms);
		return $save;
	}





	public function show($type = false) {
		if (!$this->im) {
			return false;
		}
		$type = $type ? $type : $this->type();
		@header('Content-Type: ' . (empty($this->mime[$type]) ? reset($$this->mime) : $this->mime[$type]));
		if (self::TYPE_WEBP == $type) {
			if (!imagewebp($this->im)) {
				return false;
			}
		} elseif (self::TYPE_GIF == $type) {
			$this->stotal();
			imagegif ($this->im);
		} elseif (self::TYPE_PNG == $type) {
			$this->stotal();
			imagepng ($this->im);
		} else {
			imageinterlace($this->im, true);
			imagejpeg($this->im, null, $this->quality);
		}
		return true;
	}



	/**
	*	索引颜色转换
	*
	*	无参数
	*
	*	返回值 bool
	**/
	public function stotal() {
		return function_exists('imageistruecolor') && $this->_old && $this->im && !imageistruecolor($this->_old) && imagetruecolortopalette($this->im, false, imagecolorstotal($this->_old));
	}




	/**
	*	创建新图像
	*
	*	1 参数 宽度
	*	2 参数 高度
	*
	*	返回值 false or 资源
	**/
	private function _create($w, $h) {
		if (!$image = imagecreatetruecolor($w, $h)) {
			return false;
		}
		imagefilledrectangle($image, 0, 0, $w, $h, imagecolorallocate($image, 255, 255, 255));
		imagealphablending($image, false);
		imagesavealpha($image, true);
		return $image;
	}

}