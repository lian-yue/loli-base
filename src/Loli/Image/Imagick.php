<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-02-12 15:00:16
/*	Updated: UTC 2015-04-09 08:14:20
/*
/* ************************************************************************** */
namespace Loli\Image;
use ImagickException, ImagickPixel, ImagickDraw;
class_exists('Loli\Image\Base') || exit;
class Imagick extends Base{

	private $_im;

	private $_type = false;

	public function create($file, $type = false) {
		$this->_im && $this->destroy();
		if (!$file || !is_file($file)) {
			throw new Exception('Image does not exist');
		}

		try {
			$this->_im = new \Imagick($file);
			$type = $type ? $type : $this->_im->getImageFormat();
			if (empty($this->types[$type])) {
				$type = strtolower($type);
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
			$this->_im->getImageFormat() == 'GIF' && $this->_type != self::TYPE_GIF && $this->_im->flattenImages();
			$this->_im->setImageFormat(reset($this->types[$this->_type]));
		} catch(ImagickException $e) {
			$this->_type = $this->_im = false;
			throw new Exception($e->getMessage());
		}
		return $this;
	}

	public function destroy() {
		$this->_im && $this->_im->clear() && $this->_im->destroy();
		$this->_type = $this->_im = false;
		return $this;
	}


	public function width() {
		if (!$this->_im) {
			throw new Exception('Resource');
		}
		$a = $this->_im->getImagePage();
		return $a['width'];
	}


	public function height() {
		if (!$this->_im) {
			throw new Exception('Resource');
		}
		$a = $this->_im->getImagePage();
		return $a['height'];
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
		if ($this->type() != self::TYPE_GIF) {
			return 1;
		}
		$i = 0;
		foreach ($this->_im as $v) {
			++$i;
		}
		return $i;
	}


	public function length() {
		if (!$this->_im) {
			throw new Exception('Resource');
		}
		if ($this->type() != self::TYPE_GIF) {
			return 0;
		}
		$length = 0;
		foreach ($this->_im as $v) {
			$length += $v->getImageDelay();
		}
		return $length;
	}


	public function rotate($angle) {
		if (!$this->_im) {
			throw new Exception('Resource');
		}
		try {
			$bg = new ImagickPixel('transparent');
			if ($this->type() == self::TYPE_GIF) {
				$im = $this->_im->coalesceImages();
				$this->_im->destroy();
				do {
					$im->rotateImage($bg, $angle);
				} while ($im->nextImage());
				$this->_im = $im->deconstructImages();
				$im->destroy();
			} else {
				$this->_im->rotateImage($bg, $angle);
			}
		} catch(ImagickException $e) {
			throw new Exception($e->getMessage());
		}
		return $this;
	}


	public function flip($mode = self::FLIP_HORIZONTAL) {
		if (!$this->_im) {
			throw new Exception('Resource');
		}
		try {
			if ($this->type() == self::TYPE_GIF) {
				$im = $this->_im->coalesceImages();
				$this->_im->destroy();
				do {
					$mode == self::FLIP_HORIZONTAL || $im->flipImage();
					$mode == self::FLIP_VERTICAL || $im->flopImage();
				} while ($im->nextImage());
				$this->_im = $im->deconstructImages();
				$im->destroy();
			} else {
				$mode == self::FLIP_HORIZONTAL || $this->_im->flipImage();
				$mode == self::FLIP_VERTICAL || $this->_im->flopImage();
			}
		} catch(ImagickException $e) {
			throw new Exception($e->getMessage());
		}
		return $this;
	}


	public function text($text, $font, $size = 30, $color = '#FFFFFF', $x = '0%', $y = '-0%', $angle = 0,  $opacity = 1.0) {
		if (!$this->_im) {
			throw new Exception('Resource');
		}

		if (!$font || !is_file($font)) {
			throw new Exception('Font file does not exist');
		}
		$angle = $angle % 360;

		if (is_array($color)) {
			$color = '#';
			foreach (['red', 'green', 'blue'] as $key) {
				 $color .= str_pad($rgba[$key], 2, '0', STR_PAD_LEFT);
			}
		}

		// 不会算法 所以这不支持45度什么的
		try {
			$draw = new ImagickDraw();
			$draw->setFont($font);
			$draw->setFontSize($size);
			$draw->setFillColor($color);
			$draw->setFillOpacity($opacity);
			$draw->setTextAntialias(true);
			$draw->setStrokeAntialias(true);
			$metrics = $this->_im->queryFontMetrics($draw, $text);
			$w = $metrics['textWidth'];
	        $h = $metrics['textHeight'];
			$xN = $x < 0 || ($x && is_string($x) && $x{0} == '-');
	        if (is_string($x) && substr($x, -1, 1) == '%') {
				$x = ($this->width() - $w) * (($xN ? 100 + $x : $x) / 100);
			} elseif ($xN) {
				$x += $this->width() - $w;
			}



			$yN = $y < 0 || ($y && is_string($y) && $y{0} == '-');
			if (is_string($y) && substr($y, -1, 1) == '%') {
				$y = ($this->height() - $h) * (($yN ? 100 + $y : $y) / 100);
			} elseif ($yN) {
				$y += $this->height() - $h;
			}
			$y += $metrics['ascender'];


			if ($this->type() == self::TYPE_GIF) {
				$im = $this->_im->coalesceImages();
				$this->_im->destroy();
				do {
					$im->annotateImage($draw, $x, $y , $angle, $text);
				} while ($im->nextImage());
				$this->_im = $im->deconstructImages();
				$im->destroy();
			} else {
				$this->_im->annotateImage($draw, $x, $y, $angle, $text);
			}
			$draw->destroy();
		} catch(ImagickException $e) {
			throw new Exception($e->getMessage());
		}
		return $this;
	}

	public function insert($file, $x = 0, $y = 0, $opacity = 1.0) {
		if (!$this->_im) {
			throw new Exception('Resource');
		}
		if (!$file || !is_file($file)) {
			throw new Exception('Image does not exist');
		}
		try {
			$src = new \Imagick($file);
			$src->setImageOpacity($opacity);
			$info = $src->getImagePage();

			$xN = $x < 0 || ($x && is_string($x) && $x{0} == '-');
			if (is_string($x) && substr($x, -1, 1) == '%') {
				$x = ($this->width() - $info['width']) * (($xN ? 100 + $x : $x) / 100);
			} elseif ($xN) {
				$x += $this->width() - $info['width'];
			}

			$yN = $y < 0 || ($y && is_string($y) && $y{0} == '-');
			if (is_string($y) && substr($y, -1, 1) == '%') {
				$y = ($this->height() - $info['height']) * (($yN ? 100 + $y : $y) / 100);
			} elseif ($yN) {
				$y += $this->height() - $info['height'];
			}


			$draw = new ImagickDraw();
			$draw->composite($src->getImageCompose(), $x, $y, $info['width'], $info['height'], $src);
			if ($this->type() == self::TYPE_GIF) {
				$im = $this->_im->coalesceImages();
				$this->_im->destroy();
				do{
					$im->drawImage($draw);
					} while ($im->nextImage());
				$this->_im = $im->deconstructImages();
				$im->destroy();
			} else {
				$this->_im->drawImage($draw);
			}
		} catch (ImagickException $e) {
			throw new Exception($e->getMessage());
		}
		return $this;
	}





	public function resampled($new_w, $new_h, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) {
		if (!$this->_im) {
			throw new Exception('Resource');
		}
		try {
			if ($this->type() == self::TYPE_GIF) {
				$im = $this->_im->coalesceImages();
				$this->_im->destroy();
				do {
					$im->extentImage($src_w, $src_h, $src_x, $src_y);
					$im->thumbnailImage($dst_w, $dst_h, true, true);
				} while ($im->nextImage());
				$this->_im = $im->deconstructImages();
				$im->destroy();
				if ($dst_x || $dst_y || $new_w != $dst_w || $new_h != $dst_h) {
					$im = new \Imagick;
					$bg = new ImagickPixel('transparent');
					$src = $this->_im->coalesceImages();
					$this->_im->destroy();
					do {
						$a = new \Imagick();
						$a->newImage($new_w, $new_h, $bg, $src->getImageFormat());
						$a->compositeImage($src, \imagick::COMPOSITE_OVER, $dst_x, $dst_y);
						$im->addImage($a);
						$im->setImageDelay($a->getImageDelay());
						$a->destroy();
					} while ($src->nextImage());
					$src->destroy();
					$this->_im = $im->deconstructImages();
					$im->destroy();
				}
			} else {
				$this->_im->extentImage($src_w, $src_h, $src_x, $src_y);
				$this->_im->thumbnailImage($dst_w, $dst_h, true, true);
				if ($dst_x || $dst_y || $new_w != $dst_w || $new_h != $dst_h) {
					$im = new \Imagick;
					$im->newImage($new_w, $new_h, new ImagickPixel('transparent'));
					$im->compositeImage($this->_im, \imagick::COMPOSITE_OVER, $dst_x, $dst_y);
					$this->_im->destroy();
					$this->_im = $im;
					$im->destroy();
				}
			}
		} catch (ImagickException $e) {
			throw new Exception($e->getMessage());
		}
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
					break;
				}
			}
		}

		try {
			reset($this->types);
			$this->_type = $type ? $type : $this->type();
			$this->_type = empty($this->types[$this->_type]) ? key($this->types) : $this->_type;
			$this->_type == self::TYPE_JPEG && $this->_im->borderImage(new ImagickPixel("white") ,0 ,0);
			$this->_im->setImageFormat(reset($this->types[$this->_type]));

			if ($this->_type == self::TYPE_JPEG) {
				$this->_im->setImageCompressionQuality($this->quality);
				$this->_im->setImageInterlaceScheme(true);
			}
			$this->_im->stripImage();
			if (self::TYPE_GIF == $this->_type) {
				$this->_im->writeImages($save, true);
			} else {
				$this->_im->writeImage($save);
			}

			$stat = stat(dirname($save));
			$perms = $stat['mode'] & 0000666;
			@chmod($save, $perms);
		} catch (ImagickException $e) {
			throw new Exception($e->getMessage());
		}

		return $this;
	}



	public function show($type = false) {
		if (!$this->_im) {
			throw new Exception('Resource');
		}
		try {
			reset($this->types);
			$this->_type = $type ? $type : $this->type();
			$this->_type = empty($this->types[$this->_type]) ? key($this->types) : $this->_type;

			$this->_type == self::TYPE_JPEG && $this->_im->borderImage(new ImagickPixel("white") ,0 ,0);
			$this->_im->setImageFormat(reset($this->types[$this->_type]));

			if ($this->_type == self::TYPE_JPEG) {
				$this->_im->setImageCompressionQuality($this->quality);
				$this->_im->setImageInterlaceScheme(true);
			}
			headers_sent() || header('Content-Type: ' . (empty($this->mime[$this->_type]) ? reset($this->mime) : $this->mime[$this->_type]));
			echo $this->_type == self::TYPE_GIF ? $this->_im->getImagesBlob() : $this->_im->getImage();
		} catch (ImagickException $e) {
			throw new Exception($e->getMessage());
		}
		return $this;
	}

}