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
/*	Updated: UTC 2014-12-30 15:03:18
/*
/* ************************************************************************** */
namespace Loli\Image;


define('IMAGE_FLIP_HORIZONTAL', 1);
define('IMAGE_FLIP_VERTICAL', 2);
define('IMAGE_FLIP_BOTH', 3);

define('IMAGE_TYPE_JPEG', 1);
define('IMAGE_TYPE_GIF', 2);
define('IMAGE_TYPE_PNG', 3);
define('IMAGE_TYPE_WEBP', 4);


abstract class Base {

	// 处理 图片 最大宽度 0 = 不限制
	public $maxWidth = 4096;

	// 处理 图片 最大高度 0 = 不限制
	public $maxHeight = 4096;

	// 处理 图片 最小宽度 0 = 不限制
	public $minWidth = 0;

	// 处理 图片 最小高度 0 = 不限制
	public $minHeight = 0;

	// jpg 处理质量
	public $quality = 90;

	// 图片
	public $im;

	// 处理图片内存限制
	public $Memory = '512M';

	// 文件后缀
	public $type = [IMAGE_TYPE_JPEG => ['jpg', 'jpeg', 'jpe', 'jfif', 'jif'], IMAGE_TYPE_GIF => ['gif'], IMAGE_TYPE_PNG => ['png'], IMAGE_TYPE_WEBP => ['webp']];

	public $mime = [IMAGE_TYPE_JPEG => 'image/jpeg', IMAGE_TYPE_GIF => 'image/gif', IMAGE_TYPE_PNG => 'image/png', IMAGE_TYPE_WEBP => 'image/webp'];
	/**
	*	init
	*
	*	1 图像文件
	*
	*	返回值 bool
	**/
	public function __construct($a = '', $type = false) {
		@ini_set('memory_limit', $this->Memory);
		$a && $this->create($a, $type);
	}

	/**
	*	打开图片
	*
	*	1 图像文件
	*
	*	返回值 bool
	**/
	abstract public function create($a, $type = false);

	/**
	*	关闭图片
	*
	*	无参数
	*
	*	返回值 bool
	**/
	abstract public function destroy();

	/**
	*	当前图像宽度
	*
	*	无参数
	*
	*	返回值 int
	**/
	abstract public function width();

	/**
	*	当前图像高度
	*
	*	无参数
	*
	*	返回值 int
	**/
	abstract public function height();


	/**
	*	返回图片类型
	*
	*	无参数
	*
	*	返回值 string
	**/
	abstract public function type($type = false);

	/**
	 * [frames 帧数量]
	 * @return bool or int
	 */
	abstract public function frames();

	/**
	 * [length 返回一个循环的时间毫秒]
	 * @return [type] [description]
	 */
	abstract public function length();

	/**
	*	旋转图像
	*
	*	1 参数 angle
	*
	*	返回值 bool
	**/
	abstract public function rotate($angle);

	/**
	*	图像反转
	*
	*	1 参数 反转模式 IMAGE_FLIP_HORIZONTAL = 水平翻转 IMAGE_FLIP_VERTICAL = 垂直翻转图像 IMAGE_FLIP_BOTH = 水平和垂直翻转图像
	*
	*	返回值bool
	*/
	abstract public function flip($mode = IMAGE_FLIP_HORIZONTAL);

	 /**
     * 图像添加文字
     * @param  string  $text   文字
     * @param  integer $size   大小
     * @param  string  $font   字体路径
     * @param  string  $color  颜色
     * @param  integer $top 上下位置
     * @param  integer $left 左右位置
     * @param  integer $angle  倾斜角度
     */
	//abstract public function text($text, $size,  $font, $color = '#000000', $top = 0, $left = 0, $angle = 0);

  /**
     * 图像添加图片
     * @param  string  $file   图片地址
     * @param  integer $top 上下位置
     * @param  integer $left 左右位置
     * @param  integer $alpha  透明度
     */
  // abstract public function insert($file, $top = 0, $left = 0, $alpha = 100);

	/**
	*	重采样拷贝部分图像并调整大小
	*
	*	参数 和 imagecopyresampled 移除1 2 参数 一样
	*
	*	无返回 bool
	*/
	abstract public function resampled($new_w, $new_h, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

	/**
	*	保存图像
	*
	*	2 参数 保存途径
	*	3 参数 保存类型
	*
	*	返回值 保存的路径
	**/
	abstract public function save($save, $type = false);

	/**
	*	显示图像
	*
	*	1 参数 输出类型
	*
	*	返回值 true false
	**/
	abstract public function show($type = false);

	/**
	*	剪切调整图像
	*
	*	1 参数 最大宽度
	*	2 参数 最大高度
	*	3 参数 是否剪切 默认 false false = 对等缩缩小 true = 缩小剪切
	*	4 参数 是否放大 默认 false false = 不允许放大 true = 允许放大
	*	5 参数 是否填补	默认 flash = 禁止填补 ture = 允许填补
	*
	*	返回值 文件绝对地址
	**/
	public function resize($max_w, $max_h, $crop = false, $enlarge = false, $fill = false) {

		// 强制控制大小
		$this->maxMin($max_w, $max_h);

		// 处理尺寸
		if (!$args = $this->resizeDimensions($max_w, $max_h, $crop, $enlarge, $fill)) {
			return false;
		}
		return  call_user_func_array([$this, 'resampled'], $args);
	}

	/**
	*	处理尺寸
	*
	*	1 参数 剪切最大宽度
	*	2 参数 剪切最大高度
	*	3 参数 是否剪切	默认 false = 对等缩小 ture = 剪切图片
	*	4 参数 是否放大	默认 flash = 禁止放大 ture = 允许放大
	*	5 参数 是否填补	默认 flash = 禁止填补 ture = 允许填补
	*
	*	返回值 数组 或者 false
	**/
	public function resizeDimensions($max_w = 0, $max_h = 0, $crop = false, $enlarge = false, $fill = false) {
		if (!$this->im || ($max_w <= 0 && $max_h <= 0)) {
			return false;
		}

		$w = $this->width();
		$h = $this->height();
		if ($crop) {
			// 剪切 最大可能获得原始图像 $max_w 	$max_h
			$aspect_ratio = $w / $h;
			if (!$enlarge) {
				$dst_w = min($max_w, $w);
				$dst_h = min($max_h, $h);
			} else {
				$dst_w = $max_w;
				$dst_h = $max_h;
			}
			if (!$dst_w) {
				$dst_w = $dst_h * $aspect_ratio;
			}

			if (!$dst_h) {
				$dst_h = $dst_w / $aspect_ratio;
			}

			$size_ratio = max($dst_w / $w, $dst_h / $h);
			$src_w = $dst_w / $size_ratio;
			$src_h = $dst_h / $size_ratio;
			if ($crop === 'top,left') {
				// 左边上
				$src_w = $dst_w;
				$src_h = $dst_h;
				$src_x = 0;
				$src_y = 0;
			} elseif ($crop === 'top') {
				// 上
				$src_x = ($w - $src_w) / 2;
				$src_y = 0;
			} elseif ($crop === 'top,right') {
				// 右上
				$src_x = $w - $src_w;
				$src_y = 0;
			} elseif ($crop === 'in,left') {
				// 中左
				$src_x = 0;
				$src_y = ($h - $src_h) / 2;
			} elseif ($crop === 'in,right') {
				// 右中
				$src_x = $w - $src_w;
				$src_y = ($h - $src_h) / 2;
			} elseif ($crop === 'bottom,left') {
				// 左下
				$src_x = 0;
				$src_y = $h - $src_h;
			} elseif ($crop === 'bottom') {
				// 下
				$src_x = ($w - $src_w) / 2;
				$src_y = $h - $src_h;
			} elseif ($crop === 'bottom,right') {
				// 右下
				$src_x = $w - $src_w;
				$src_y = $h - $src_h;
			} else {
				$src_x = ($w - $src_w) / 2;
				$src_y = ($h - $src_h) / 2;
			}
		} else {
			// 没有剪切 的
			$src_w = $w;
			$src_h = $h;

			$src_x = 0;
			$src_y = 0;

			list($dst_w, $dst_h) = $this->constrain_dimensions($max_w, $max_h, $enlarge);
		}
		$dst_x = 0;
		$dst_y = 0;
		$src_x = round($src_x);
		$src_y = round($src_y);
		$dst_w = max(round($dst_w), 1);
		$dst_h = max(round($dst_h), 1);
		$src_w = max(round($src_w), 1);
		$src_h = max(round($src_h), 1);
		$new_w = $dst_w;
		$new_h = $dst_h;


		if ($max_w && $max_h && $fill) {
			if ($max_w != $dst_w) {
				$dst_x = abs($max_w - $dst_w) / 2;
				$new_w = $max_w;
			}
			if ($max_h != $dst_h) {
				$dst_y = abs($max_h - $dst_h) / 2;
				$new_h = $max_h;
			}
		}

		// 返回的数组参数匹配到 第一个 第二个是新图像宽度高度 imagecopyresampled()
		$r = [$new_w, $new_h, (int)$dst_x, (int)$dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h];
		return $r;
	}

	/**
	*	计算新的尺寸为下采样图像。
	*	1 参数 现在宽度
	*	1 参数 现在高度
	*	3 参数 剪切最大宽度
	*	4 参数 剪切最大高度
	*	5 参数 是否放大 默认 false = 禁止放大 true = 允许放大
	*
	**/
	public function constrain_dimensions($max_w = 0, $max_h = 0, $enlarge = false) {
		if (!$this->im) {
			return false;
		}
		$w = $this->width();
		$h = $this->height();

		if (!$max_w && !$max_h)
			return [$w, $h];

		$width_ratio = $height_ratio = 1.0;
		$did_width = $did_height = false;


		if ($enlarge || ($max_w > 0 && $w > 0 && $w > $max_w)) {
			$width_ratio = $max_w / $w;
			$did_width = true;
		}

		if ($enlarge || ($max_h > 0 && $h > 0 && $h > $max_h)) {
			$height_ratio = $max_h / $h;
			$did_height = true;
		}

		// 计算较大 / 较小的比率
		$smaller_ratio = min($width_ratio, $height_ratio);
		$larger_ratio = max($width_ratio, $height_ratio);

		if (intval($w * $larger_ratio) > $max_w || intval($h * $larger_ratio) > $max_h) {
			// 较大的比例太大。它会导致溢出。
			$ratio = $smaller_ratio;
		} else {
			// 较大的比例配合，很可能是一个更 贴身 适合
			$ratio = $larger_ratio;
		}
		$w = intval($w * $ratio);
		$h = intval($h * $ratio);

		// 有时候，由于四舍五入，我们会结束这样一个结果：在177x177箱465x700是117x176像素的短
		// 我们也有在瞬息万变的结果导致递归调用的问题。制约约束的结果应该产生的结果。
		// 因此，我们期待的尺寸是一个像素的最大值害羞和凹凸
		if ($did_width && $w == $max_w - 1) {
			// 它向上舍入
			$w = $max_w;
		}
		if ($did_height && $h == $max_h - 1) {
			// 它向上舍入
			$h = $max_h;
		}
		$h = $h ? $h : 1;
		$w = $w ? $w : 1;
		return [$w, $h];
	}



	/**
	*	图像宽度高度检测
	*
	*	1 参数 图像宽度
	*	2 参数 图像高度
	*
	*	无返回值 直接引用
	**/
	private function maxMin(&$w, &$h) {

		// 最大宽度检测
		if ($w && $this->maxWidth && $this->maxWidth < $w) {
			$w = $this->maxWidth;
		}

		// 最大高度检测
		if ($h && $this->maxHeight && $this->maxHeight < $h) {
			$h = $this->maxHeight;
		}

		// 最小宽度检测
		if ($w && $this->minWidth && $this->minWidth < $w) {
			$w = $this->minWidth;
		}

		// 最小高度检测
		if ($h && $this->minHeight && $this->minHeight < $h) {
			$h = $this->minHeight;
		}
	}
}
