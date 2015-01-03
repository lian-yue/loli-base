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
/*	Updated: UTC 2014-12-31 07:11:45
/*
/* ************************************************************************** */
namespace Loli\Image;
use ImagickException, ImagickPixel;
class Imagick extends Base{

	public $_type = false;

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
			$this->im->getImageFormat() == 'GIF' && $this->_type != IMAGE_TYPE_GIF && $this->im->flattenImages();
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
		if ($this->type() != IMAGE_TYPE_GIF) {
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
		if ($this->type() != IMAGE_TYPE_GIF) {
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
		if ($this->type() == IMAGE_TYPE_GIF) {
			$this->im = $this->im->coalesceImages();
			do {
				$this->im->rotateImage($bg, $angle);
			} while ($this->im->nextImage());
		} else {
			$this->im->rotateImage($bg, $angle);
		}
		return true;
	}


	public function flip($mode = IMAGE_FLIP_HORIZONTAL) {
		if (!$this->im) {
			return false;
		}
		if ($this->type() == IMAGE_TYPE_GIF) {
			$this->im = $this->im->coalesceImages();
			do {
				$mode == IMAGE_FLIP_HORIZONTAL || $this->im->flipImage();
				$mode == IMAGE_FLIP_VERTICAL || $this->im->flopImage();
			} while ($this->im->nextImage());
		} else {
			$mode == IMAGE_FLIP_HORIZONTAL || $this->im->flipImage();
			$mode == IMAGE_FLIP_VERTICAL || $this->im->flopImage();
		}
		return true;
	}



	public function resampled($new_w, $new_h, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) {
		if (!$this->im) {
			return false;
		}
		if ($this->type() == IMAGE_TYPE_GIF) {
			$this->im = $this->im->coalesceImages();
			do {
				$this->im->extentImage($src_w, $src_h, $src_x, $src_y);
				$this->im->thumbnailImage($dst_w, $dst_h, true, true);
			} while ($this->im->nextImage());

			if ($dst_x || $dst_y || $new_w != $dst_w || $new_h != $dst_h) {
				$im = new \Imagick;
				$bg = new ImagickPixel('transparent');
				$this->im = $this->im->coalesceImages();
				do {
					$a = new \Imagick();
					$a->newImage($new_w, $new_h, $bg, $this->im->getImageFormat());
					$a->compositeImage($this->im, \imagick::COMPOSITE_OVER, $dst_x, $dst_y);
					$im->addImage($a);
					$im->setImageDelay($a->getImageDelay());
				} while ($this->im->nextImage());
			}
		} else {
			$this->im->extentImage($src_w, $src_h, $src_x, $src_y);
			$this->im->thumbnailImage($dst_w, $dst_h, true, true);
			if ($dst_x || $dst_y || $new_w != $dst_w || $new_h != $dst_h) {
				$im = new \Imagick;
				$im->newImage($new_w, $new_h, new ImagickPixel('transparent'));
				$im->compositeImage($this->im, \imagick::COMPOSITE_OVER, $dst_x, $dst_y);
			}
		}

		if (!empty($im)) {
			$this->im->clear();
			$this->im->destroy();
			$this->im = $im;
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
		$type == IMAGE_TYPE_JPEG && $this->im->borderImage(new ImagickPixel("white") ,0 ,0);
		$this->type($type);
		if ($type == IMAGE_TYPE_JPEG) {
			$this->im->setImageCompressionQuality($this->quality);
			$this->im->setImageInterlaceScheme(true);
		}
		$this->im->stripImage();
		if (IMAGE_TYPE_GIF == $type) {
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

		$type == IMAGE_TYPE_JPEG && $this->im->borderImage(new ImagickPixel("white") ,0 ,0);
		$this->type($type);
		if ($type == IMAGE_TYPE_JPEG) {
			$this->im->setImageCompressionQuality($this->quality);
			$this->im->setImageInterlaceScheme(true);
		}
		@header('Content-Type: ' . (empty($this->mime[$type]) ? reset($$this->mime) : $this->mime[$type]));
		echo $type == IMAGE_TYPE_GIF ? $this->im->getImagesBlob() : $this->im->getImage();
		return true;
	}

}