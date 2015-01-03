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
/*	Updated: UTC 2014-12-31 07:11:41
/*
/* ************************************************************************** */
namespace Loli\Image;
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


	public function flip($mode = IMAGE_FLIP_HORIZONTAL) {
		if (!$this->im) {
			return false;
		}
		if (function_exists ('imageflip')){
			$arg =[ IMAGE_FLIP_HORIZONTAL => IMG_FLIP_HORIZONTAL, IMAGE_FLIP_VERTICAL => IMG_FLIP_VERTICAL, IMAGE_FLIP_BOTH => IMG_FLIP_BOTH];
			return imageflip($this->im , isset($arg[$mode]) ? $arg[$mode] : IMG_FLIP_BOTH);
		}
		$width = $this->width();
		$height = $this->height();

		$src_x = 0;
		$src_y = 0;
		$src_width = $width;
		$src_height = $height;

		switch ($mode) {
			case IMAGE_FLIP_HORIZONTAL:
				$src_y = $height -1;
				$src_height = -$height;
				break;
			case IMAGE_FLIP_VERTICAL:
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

		if (IMAGE_TYPE_WEBP == $type) {
			if (!imagewebp($this->im, $save)) {
				return false;
			}
		} elseif (IMAGE_TYPE_GIF == $type) {
			$this->stotal();
			if (!imagegif($this->im, $save)) {
				return false;
			}
		} elseif (IMAGE_TYPE_PNG == $type) {
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
		if (IMAGE_TYPE_WEBP == $type) {
			if (!imagewebp($this->im)) {
				return false;
			}
		} elseif (IMAGE_TYPE_GIF == $type) {
			$this->stotal();
			imagegif ($this->im);
		} elseif (IMAGE_TYPE_PNG == $type) {
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