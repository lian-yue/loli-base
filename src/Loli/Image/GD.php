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
/*	Updated: UTC 2015-04-09 13:07:39
/*
/* ************************************************************************** */
namespace Loli\Image;
class_exists('Loli\Image\Base') || exit;
class GD extends Base {

	private $_old, $_im;

	private $_type = false;

	public function create($file, $type = false) {
		$this->_im && $this->destroy();
		if (!$file || !is_file($file)) {
			throw new Exception('Image does not exist');
		}
		if (!($info = @getimagesize($file)) || !($contents = file_get_contents($file)) || !($this->_im = @imagecreatefromstring($contents)) || !($this->_old = @imagecreatefromstring($contents))) {
			throw new Exception('Open the image');
		}
		unset($contents);

		imagealphablending($this->_im, false);
		imagesavealpha($this->_im, true);


		// 设置类型
		$type = $type ? $type : image_type_to_extension($info[2], false);
		if (empty($this->types[$type])) {
			reset($this->types);
			$this->_type = key($this->types);
			foreach ($this->types as $key => $extensions) {
				if (in_array($type, $extensions)) {
					$this->_type = $key;
					break;
				}
			}
		} else {
			$this->_type = (int) $type;
		}

		return $this;
	}

	public function destroy() {
		$this->_type = false;
		$this->_old && imagedestroy($this->_old);
		$this->_im && imagedestroy($this->_im);
		return  $this;
	}


	public function width() {
		if (!$this->_im) {
			throw new Exception('Resource');
		}
		return imagesx($this->_im) ;
	}

	public function height() {
		if (!$this->_im) {
			throw new Exception('Resource');
		}
		return imagesy($this->_im);
	}

	public function type() {
		if (!$this->_im) {
			throw new Exception('Resource');
		}
		return $this->_type;
	}


	public function frames() {
		if (!$this->_im) {
			throw new Exception('Resource');
		}
		return 1;
	}

	public function length() {
		if (!$this->_im) {
			throw new Exception('Resource');
		}
		return 0;
	}

	public function rotate($angle) {
		if (!$this->_im) {
			throw new Exception('Resource');
		}
		if (!$this->_im = imagerotate($this->_im, $angle, 16777215 , 0)) {
			throw new Exception('Rotate');
		}
		return $this;
	}


	public function flip($mode = self::FLIP_HORIZONTAL) {
		if (!$this->_im) {
			throw new Exception('Resource');
		}
		if (function_exists('imageflip')) {
			$arg = [self::FLIP_HORIZONTAL => IMG_FLIP_HORIZONTAL, self::FLIP_VERTICAL => IMG_FLIP_VERTICAL, self::FLIP_BOTH => IMG_FLIP_BOTH];
			if (!imageflip($this->_im , isset($arg[$mode]) ? $arg[$mode] : IMG_FLIP_BOTH)){
				throw new Exception('Flip');
			}
			return $this;
		}
		$width = $this->width();
		$height = $this->height();

		$srcX = 0;
		$srcY = 0;
		$srcWidth = $width;
		$srcHeight = $height;

		switch ($mode) {
			case self::FLIP_HORIZONTAL:
				$srcY = $height -1;
				$srcHeight = -$height;
				break;
			case self::FLIP_VERTICAL:
				$srcX = $width -1;
				$srcWidth = -$width;
				break;
			default:
				$srcX = $width -1;
				$srcY = $height -1;
				$srcWidth = -$width;
				$srcHeight = -$height;
		}
		$image = $this->_create($width, $height);
		if (!imagecopyresampled($image, $this->_im, 0, 0, $srcX, $srcY , $width, $height, $srcWidth, $srcHeight)) {
			throw new Exception('Flip');
		}

		imagedestroy($this->_im);
		$this->_im = $image;
		return $this;
	}


	public function text($text, $font, $size = 12, $color = '#000000', $x = 0, $y = 0, $angle = 0,  $opacity = 1.0) {
		if (!$this->_im) {
			throw new Exception('Resource');
		}
		if (!$font || !is_file($font)) {
			throw new Exception('Font file does not exist');
		}
		$angle = $angle % 360;
		if (!$info = imagettfbbox($size, $angle, $font, $text)) {
			throw new Exception('Font');
		}


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

		if (!function_exists('imagettftext')) {
			throw new Exception('Freetype is not supported');
		}

		imagealphablending($this->_im, true);
		$color = imagecolorallocatealpha($this->_im, $red, $green, $blue, 127 - $opacity * 127);
		imagettftext($this->_im, $size, $angle, $x, $y, $color, $font, $text);
		imagealphablending($this->_im, false);
		return $this;
	}

	public function insert($file, $x = 0, $y = 0, $opacity = 1.0) {
		if (!$this->_im) {
			throw new Exception('Resource');
		}
		if (!$file || !is_file($file)) {
			throw new Exception('Image does not exist');
		}
		if (!($info = @getimagesize($file)) || !($data = file_get_contents($file)) || !($im = @imagecreatefromstring($data))) {
			throw new Exception('Open the image');
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
		imagecopy($src, $this->_im, 0, 0, $x, $y, $info[0], $info[1]);
		imagecopy($src, $im, 0, 0, 0, 0, $info[0], $info[1]);
		imagecopymerge($this->_im, $src, $x, $y, 0, 0, $info[0], $info[1], $opacity * 100);
		imagedestroy($src);
		imagedestroy($im);
		return $this;
	}



	public function resampled($newWidth, $newHeight, $dstX, $dstY, $srcX, $srcY, $dstWidth, $dstHeight, $srcWidth, $srcHeight) {
		if (!$this->_im) {
			throw new Exception('Resource');
		}
		if (!imagecopyresampled($image = $this->_create($newWidth, $newHeight), $this->_im, $dstX, $dstY, $srcX, $srcY, $dstWidth, $dstHeight, $srcWidth, $srcHeight)) {
			throw new Exception('Resize');
		}
		imagedestroy($this->_im);
		$this->_im = $image;
		return $this;
	}




	public function save($save, $type = false) {
		if (!$this->_im) {
			throw new Exception('Resource');
		}
		if (!$save) {
			throw new Exception('Path');
		}

		$info = pathinfo($save);
		if (!empty($info['extension'])) {
			foreach ($this->types as $k => $v) {
				if (in_array($info['extension'], $v)) {
					$type = $k;
				}
			}
		}

		if (self::TYPE_WEBP == $type) {
			if (!imagewebp($this->_im, $save)) {
				throw new Exception('Save');
			}
		} elseif (self::TYPE_GIF == $type) {
			$this->_stotal();
			if (!imagegif($this->_im, $save)) {
				throw new Exception('Save');
			}
		} elseif (self::TYPE_PNG == $type) {
			$this->_stotal();
			if (!imagepng($this->_im, $save)) {
				throw new Exception('Save');
			}
		} else {
			imageinterlace($this->_im, true);
			if (!imagejpeg($this->_im, $save, $this->quality)) {
				throw new Exception('Save');
			}
		}

		// 权限
		$stat = stat(dirname($save));
		$perms = $stat['mode'] & 0000666;
		@chmod($save, $perms);
		return $this;
	}





	public function show($type = false) {
		if (!$this->_im) {
			throw new Exception('Resource');
		}
		$type = $type ? $type : $this->type();
		headers_sent() || header('Content-Type: ' . (empty($this->mimes[$type]) ? reset($$this->mimes) : $this->mimes[$type]));
		if (self::TYPE_WEBP === $type) {
			if (!imagewebp($this->_im)) {
				throw new Exception('Show');
			}
		} elseif (self::TYPE_GIF === $type) {
			$this->_stotal();
			if (!imagegif($this->_im)) {
				throw new Exception('Show');
			}
		} elseif (self::TYPE_PNG === $type) {
			$this->_stotal();
			imagepng($this->_im);
		} else {
			imageinterlace($this->_im, true);
			if (!imagejpeg($this->_im, NULL, $this->quality)) {
				throw new Exception('Show');
			}
		}
		return $this;
	}



	/**
	 * _stotal
	 */
	private function _stotal() {
		function_exists('imageistruecolor') && $this->_old && $this->_im && !imageistruecolor($this->_old) && imagetruecolortopalette($this->_im, false, imagecolorstotal($this->_old));
	}




	/**
	 * _create 创建文件
	 * @param  integer $w
	 * @param  integer $h
	 * @return resource
	 */
	private function _create($w, $h) {
		if (!$image = imagecreatetruecolor($w, $h)) {
			throw new Exception('Creating images');
		}
		imagefilledrectangle($image, 0, 0, $w, $h, imagecolorallocate($image, 255, 255, 255));
		imagealphablending($image, false);
		imagesavealpha($image, true);
		return $image;
	}
}