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
/*	Updated: UTC 2015-01-07 15:28:47
/*
/* ************************************************************************** */
namespace Loli\Image;
use ImagickException, ImagickPixel, ImagickDraw;
class Imagick extends Base{

	private $_type = false;

	public function create($a, $type = false) {
		$this->_type = false;
		try{
			$this->im = new \Imagick($a);
		} catch(ImagickException $e) {
			return $this->im = false;
		}
		$this->type($type ? $type : $this->im->getImageFormat());
		return true;
	}

	public function destroy() {
		$this->_type = false;
		return $this->im && $this->im->clear() && $this->im->destroy();
	}


	public function width() {
		if (!$this->im) {
			return false;
		}
		$a = $this->im->getImagePage();
		return $a['width'];
	}


	public function height() {
		if (!$this->im) {
			return false;
		}
		$a = $this->im->getImagePage();
		return $a['height'];
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
			$this->im->getImageFormat() == 'GIF' && $this->_type != self::TYPE_GIF && $this->im->flattenImages();
			$this->im->setImageFormat(reset($this->type[$this->_type]));
		}
		return $this->_type;
	}
	/**
	 *	返回帧 数量
	 *
	 * @return [type] [description]
	 */
	public function frames() {
		if (!$this->im) {
			return false;
		}
		if ($this->type() != self::TYPE_GIF) {
			return 1;
		}
		$i = 0;
		foreach ($this->im as $v) {
			++$i;
		}
		return $i;
	}


	public function length(){
		if (!$this->im) {
			return false;
		}
		if ($this->type() != self::TYPE_GIF) {
			return 0;
		}
		$length = 0;
		foreach ($this->im as $v) {
			$length += $v->getImageDelay();
		}
		return $length;
	}


	public function rotate($angle) {
		if (!$this->im) {
			return false;
		}
		$bg = new ImagickPixel('transparent');
		if ($this->type() == self::TYPE_GIF) {
			$im = $this->im->coalesceImages();
			$this->im->destroy();
			do {
				$im->rotateImage($bg, $angle);
			} while ($im->nextImage());
			$this->im = $im->deconstructImages();
			$im->destroy();
		} else {
			$this->im->rotateImage($bg, $angle);
		}
		return true;
	}


	public function flip($mode = self::FLIP_HORIZONTAL) {
		if (!$this->im) {
			return false;
		}
		if ($this->type() == self::TYPE_GIF) {
			$im = $this->im->coalesceImages();
			$this->im->destroy();
			do {
				$mode == self::FLIP_HORIZONTAL || $im->flipImage();
				$mode == self::FLIP_VERTICAL || $im->flopImage();
			} while ($im->nextImage());
			$this->im = $im->deconstructImages();
			$im->destroy();
		} else {
			$mode == self::FLIP_HORIZONTAL || $this->im->flipImage();
			$mode == self::FLIP_VERTICAL || $this->im->flopImage();
		}
		return true;
	}


	public function text($text, $font, $size = 30, $color = '#FFFFFF', $x = 0, $y = 200, $angle = 150,  $opacity = 1.0) {

		if (is_array($color)) {
			$color = '#';
			foreach (['red', 'green', 'blue'] as $key) {
				 $color .= str_pad($rgba[$key], 2, '0', STR_PAD_LEFT);
			}
		}

		$draw = new ImagickDraw();
		$draw->setFont($font);
		$draw->setFontSize($size);
		$draw->setFillColor($color);
		$draw->setFillOpacity($opacity);
		$draw->setTextAntialias(true);
		$draw->setStrokeAntialias(true);
		$metrics = $this->im->queryFontMetrics($draw, $text);



		//$y = $metrics['ascender'];
        $w = $metrics['textWidth'];
        $h = $metrics['textHeight'];
		if (r('info')) {
			print_r($metrics);
			die;
		}
		echo $w;die;
		$diagonal = sqrt($w * $w + $h * $h);
		echo $diagonal;die;

		$xN = $x < 0 || ($x && is_string($x) && $x{0} == '-');
		if (is_string($x) && substr($x, -1, 1) == '%') {
			$x = ($this->width() - $w) * (($xN ? 100 + $x : $x) / 100);
		} elseif ($xN) {
			$x += $this->width() - $w;
		}
//		$xx = ($h + $w)  $angle % 45 ? 1 : 1;
		if ($angle <= 90) {
		} elseif ($angle <= 180) {
			$x += ($w * (($angle - 90) / 90));
		} elseif ($angle <= 270) {
			$x += $h / (($angle - 180) / 90);
		} else {
			$x -= $info[0];
		}

		if ($this->type() == self::TYPE_GIF) {
			$im = $this->im->coalesceImages();
			$this->im->destroy();
			do {
				$im->annotateImage($draw, $x, $y , $angle, $text);
			} while ($im->nextImage());
			$this->im = $im->deconstructImages();
			$im->destroy();
		} else {
			$this->im->annotateImage($draw, $x, $y, $angle, $text);
		}
		$draw->destroy();
		return true;
	}

	public function insert($file, $x = 0, $y = 0, $opacity = 1.0) {

	}




	public function resampled($new_w, $new_h, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) {
		if (!$this->im) {
			return false;
		}
		if ($this->type() == self::TYPE_GIF) {
			$im = $this->im->coalesceImages();
			$this->im->destroy();
			do {
				$im->extentImage($src_w, $src_h, $src_x, $src_y);
				$im->thumbnailImage($dst_w, $dst_h, true, true);
			} while ($im->nextImage());
			$this->im = $im->deconstructImages();
			$im->destroy();
			if ($dst_x || $dst_y || $new_w != $dst_w || $new_h != $dst_h) {
				$im = new \Imagick;
				$bg = new ImagickPixel('transparent');
				$src = $this->im->coalesceImages();
				$this->im->destroy();
				do {
					$a = new \Imagick();
					$a->newImage($new_w, $new_h, $bg, $src->getImageFormat());
					$a->compositeImage($src, \imagick::COMPOSITE_OVER, $dst_x, $dst_y);
					$im->addImage($a);
					$im->setImageDelay($a->getImageDelay());
					$a->destroy();
				} while ($src->nextImage());
				$src->destroy();
				$this->im = $im->deconstructImages();
				$im->destroy();
			}
		} else {
			$this->im->extentImage($src_w, $src_h, $src_x, $src_y);
			$this->im->thumbnailImage($dst_w, $dst_h, true, true);
			if ($dst_x || $dst_y || $new_w != $dst_w || $new_h != $dst_h) {
				$im = new \Imagick;
				$im->newImage($new_w, $new_h, new ImagickPixel('transparent'));
				$im->compositeImage($this->im, \imagick::COMPOSITE_OVER, $dst_x, $dst_y);
				$this->im->destroy();
				$this->im = $im;
				$im->destroy();
			}
		}

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
					break;
				}
			}
		}
		reset($this->type);
		$type = $type ? $type : $this->type();
		$type = empty($this->type[$type]) ? key($this->type) : $type;
		$type == self::TYPE_JPEG && $this->im->borderImage(new ImagickPixel("white") ,0 ,0);
		$this->type($type);
		if ($type == self::TYPE_JPEG) {
			$this->im->setImageCompressionQuality($this->quality);
			$this->im->setImageInterlaceScheme(true);
		}
		$this->im->stripImage();
		if (self::TYPE_GIF == $type) {
			if (!$this->im->writeImages($save, true)) {
				return false;
			}
		} elseif (!$this->im->writeImage($save)) {
			return false;
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
		reset($this->type);
		$type = $type ? $type : $this->type();
		$type = empty($this->type[$type]) ? key($this->type) : $type;

		$type == self::TYPE_JPEG && $this->im->borderImage(new ImagickPixel("white") ,0 ,0);
		$this->type($type);
		if ($type == self::TYPE_JPEG) {
			$this->im->setImageCompressionQuality($this->quality);
			$this->im->setImageInterlaceScheme(true);
		}
		@header('Content-Type: ' . (empty($this->mime[$type]) ? reset($$this->mime) : $this->mime[$type]));
		echo $type == self::TYPE_GIF ? $this->im->getImagesBlob() : $this->im->getImage();
		return true;
	}

}