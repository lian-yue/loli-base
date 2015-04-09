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
/*	Updated: UTC 2015-04-09 01:43:29
/*
/* ************************************************************************** */
namespace Loli\Image;
abstract class Base {


	const FLIP_HORIZONTAL = 1;

	const FLIP_VERTICAL = 2;

	const FLIP_BOTH = 3;






	const TYPE_JPEG = 1;

	const TYPE_GIF = 2;

	const TYPE_PNG = 3;

	const TYPE_WEBP = 4;





	/**
	 * $maxWidth 处理 图片 最大宽度 0 = 不限制
	 * @var integer
	 */
	public $maxWidth = 8192;

	/**
	 * $maxHeight 处理 图片 最大高度 0 = 不限制
	 * @var integer
	 */
	public $maxHeight = 8192;

	/**
	 * $maxPixels 处理 图片 最大像素 0 = 不限制
	 * @var integer
	 */
	public $maxPixels = 16777216;




	/**
	 * $minWidth 处理 图片 最小宽度 0 = 不限制
	 * @var integer
	 */
	public $minWidth = 0;

	/**
	 * $minHeight 处理 图片 最小高度 0 = 不限制
	 * @var integer
	 */
	public $minHeight = 0;

	/**
	 * $quality jpeg 处理质量
	 * @var integer
	 */
	public $quality = 90;

	/**
	 * $memory 处理图片内存限制
	 * @var string
	 */
	public $memory = '512M';

	// 文件后缀
	public $types = [1 => ['jpg', 'jpeg', 'jpe', 'jfif', 'jif'], 2 => ['gif'], 3 => ['png'], 4 => ['webp']];

	// mime类型
	public $mimes = [1 => 'image/jpeg', 2 => 'image/gif', 3 => 'image/png', 4 => 'image/webp'];

	/**
	 * __construct
	 * @param string         $file
	 * @param boolean|string $type
	 */
	public function __construct($file = '', $type = false) {
		@ini_set('memory_limit', $this->memory);
		$file && $this->create($file, $type);
	}

	/**
	 * create
	 * @param  string  $file
	 * @param  boolean $type
	 * @return this
	 */
	abstract public function create($file, $type = false);

	/**
	 * destroy
	 * @return this
	 */
	abstract public function destroy();

	/**
	 * width
	 * @return boolean|integer
	 */
	abstract public function width();

	/**
	 * height
	 * @return boolean|integer
	 */
	abstract public function height();


	/**
	 * type
	 * @return const
	 */
	abstract public function type();

	/**
	 * frames 帧数量
	 * @return boolean|integer
	 */
	abstract public function frames();

	/**
	 * length 返回一个循环的时间毫秒
	 * @return boolean|integer
	 */
	abstract public function length();


	/**
	 * rotate
	 * @param  integer|float $angle
	 * @return this
	 */
	abstract public function rotate($angle);

	/**
	 * flip
	 * @param  const $mode
	 * @return this
	 */
	abstract public function flip($mode = self::FLIP_HORIZONTAL);

	 /**
     * text
     * @param  string           $text
     * @param  string           $font
     * @param  integer          $size
     * @param  string|array     $color
     * @param  string|integer   $top 上下位置 负数 = 下边开始 10% = 百分比
     * @param  string|integer   $left 左右位置 负数 = 右边开始 10% = 百分比
     * @param  string|integer   $angle  倾斜角度
     */
	abstract public function text($text, $font, $size = 12, $color = '#000000', $x = 0, $y = 0, $angle = 0, $opacity = 1.0);


	/**
     * insert 图像添加图片
     * @param  string  $file   图片地址
     * @param  integer $top 上下位置 负数 = 下边开始
     * @param  integer $left 左右位置 负数 = 右边开始
     * @param  integer $alpha  透明度
     */
   abstract public function insert($file, $x = 0, $y = 0, $opacity = 1.0);

	/**
	 * resampled 重采样拷贝部分图像并调整大小 参数 和 imagecopyresampled 移除1 2 参数 一样
	 * @param  integer $newWidth
	 * @param  integer $newHeight
	 * @param  integer $dstX
	 * @param  integer $dstY
	 * @param  integer $srcX
	 * @param  integer $srcY
	 * @param  integer $dstWidth
	 * @param  integer $dstHeight
	 * @param  integer $srcWidth
	 * @param  integer $srcHeight
	 * @return this
	 */
	abstract public function resampled($newWidth, $newHeight, $dstX, $dstY, $srcX, $srcY, $dstWidth, $dstHeight, $srcWidth, $srcHeight);



	/**
	 * 保存图像
	 * @param  string         $save  file
	 * @param  string|boolean $type
	 * @return this
	 */
	abstract public function save($save, $type = false);


	/**
	 * show  输出图像
	 * @param  string|boolean $type
	 * @return this
	 */
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

	public function resize($maxWidth, $minHeight, $crop = false, $enlarge = false, $fill = false) {
		// 强制控制大小
		$this->_maxMin($maxWidth, $minHeight);
		return  call_user_func_array([$this, 'resampled'], $this->resizeDimensions($maxWidth, $minHeight, $crop, $enlarge, $fill));
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
	public function resizeDimensions($maxWidth = 0, $maxHeight = 0, $crop = false, $enlarge = false, $fill = false) {
		if ($maxWidth <= 0 && $maxHeight <= 0) {
			throw new Exception('Resize');
		}

		$width = $this->width();
		$height = $this->height();
		if ($crop) {
			// 剪切 最大可能获得原始图像 $maxWidth 	$maxHeight
			$aspect_ratio = $width / $height;
			if (!$enlarge) {
				$dstWidth = min($maxWidth, $width);
				$dstHeight = min($maxHeight, $height);
			} else {
				$dstWidth = $maxWidth;
				$dstHeight = $maxHeight;
			}
			if (!$dstWidth) {
				$dstWidth = $dstHeight * $aspect_ratio;
			}

			if (!$dstHeight) {
				$dstHeight = $dstWidth / $aspect_ratio;
			}

			$size_ratio = max($dstWidth / $width, $dstHeight / $height);
			$srcWidth = $dstWidth / $size_ratio;
			$srcHeight = $dstHeight / $size_ratio;
			if ($crop === 'top,left') {
				// 左边上
				$srcWidth = $dstWidth;
				$srcHeight = $dstHeight;
				$srcX = 0;
				$srcY = 0;
			} elseif ($crop === 'top') {
				// 上
				$srcX = ($width - $srcWidth) / 2;
				$srcY = 0;
			} elseif ($crop === 'top,right') {
				// 右上
				$srcX = $width - $srcWidth;
				$srcY = 0;
			} elseif ($crop === 'in,left') {
				// 中左
				$srcX = 0;
				$srcY = ($height - $srcHeight) / 2;
			} elseif ($crop === 'in,right') {
				// 右中
				$srcX = $width - $srcWidth;
				$srcY = ($height - $srcHeight) / 2;
			} elseif ($crop === 'bottom,left') {
				// 左下
				$srcX = 0;
				$srcY = $height - $srcHeight;
			} elseif ($crop === 'bottom') {
				// 下
				$srcX = ($width - $srcWidth) / 2;
				$srcY = $height - $srcHeight;
			} elseif ($crop === 'bottom,right') {
				// 右下
				$srcX = $width - $srcWidth;
				$srcY = $height - $srcHeight;
			} else {
				$srcX = ($width - $srcWidth) / 2;
				$srcY = ($height - $srcHeight) / 2;
			}
		} else {
			// 没有剪切 的
			$srcWidth = $width;
			$srcHeight = $height;

			$srcX = 0;
			$srcY = 0;

			list($dstWidth, $dstHeight) = $this->constrainDimensions($maxWidth, $maxHeight, $enlarge);
		}
		$dstX = 0;
		$dstY = 0;
		$srcX = round($srcX);
		$srcY = round($srcY);
		$dstWidth = max(round($dstWidth), 1);
		$dstHeight = max(round($dstHeight), 1);
		$srcWidth = max(round($srcWidth), 1);
		$srcHeight = max(round($srcHeight), 1);
		$newWidth = $dstWidth;
		$newHeight = $dstHeight;


		if ($maxWidth && $maxHeight && $fill) {
			if ($maxWidth != $dstWidth) {
				$dstX = abs($maxWidth - $dstWidth) / 2;
				$newWidth = $maxWidth;
			}
			if ($maxHeight != $dstHeight) {
				$dstY = abs($maxHeight - $dstHeight) / 2;
				$newHeight = $maxHeight;
			}
		}

		// 返回的数组参数匹配到 第一个 第二个是新图像宽度高度 imagecopyresampled()
		$r = [$newWidth, $newHeight, (int)$dstX, (int)$dstY, $srcX, $srcY, $dstWidth, $dstHeight, $srcWidth, $srcHeight];
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
	/**
	 * constrainDimensions
	 * @param  integer $maxWidth   [description]
	 * @param  integer $maxHeight   [description]
	 * @param  boolean $enlarge [description]
	 * @return [type]           [description]
	 */
	public function constrainDimensions($maxWidth = 0, $maxHeight = 0, $enlarge = false) {
		$width = $this->width();
		$height = $this->height();

		if (!$maxWidth && !$maxHeight)
			return [$width, $height];

		$width_ratio = $height_ratio = 1.0;
		$did_width = $did_height = false;


		if ($enlarge || ($maxWidth > 0 && $width > 0 && $width > $maxWidth)) {
			$width_ratio = $maxWidth / $width;
			$did_width = true;
		}

		if ($enlarge || ($maxHeight > 0 && $height > 0 && $height > $maxHeight)) {
			$height_ratio = $maxHeight / $height;
			$did_height = true;
		}

		// 计算较大 / 较小的比率
		$smaller_ratio = min($width_ratio, $height_ratio);
		$larger_ratio = max($width_ratio, $height_ratio);

		if (intval($width * $larger_ratio) > $maxWidth || intval($height * $larger_ratio) > $maxHeight) {
			// 较大的比例太大。它会导致溢出。
			$ratio = $smaller_ratio;
		} else {
			// 较大的比例配合，很可能是一个更 贴身 适合
			$ratio = $larger_ratio;
		}
		$width = intval($width * $ratio);
		$height = intval($height * $ratio);

		// 有时候，由于四舍五入，我们会结束这样一个结果：在177x177箱465x700是117x176像素的短
		// 我们也有在瞬息万变的结果导致递归调用的问题。制约约束的结果应该产生的结果。
		// 因此，我们期待的尺寸是一个像素的最大值害羞和凹凸
		if ($did_width && $width == $maxWidth - 1) {
			// 它向上舍入
			$width = $maxWidth;
		}
		if ($did_height && $height == $maxHeight - 1) {
			// 它向上舍入
			$height = $maxHeight;
		}
		$height = $height ? $height : 1;
		$width = $width ? $width : 1;
		return [$width, $height];
	}


	/**
	*	图像宽度高度检测
	*
	*	1 参数 图像宽度
	*	2 参数 图像高度
	*
	*	无返回值 直接引用
	**/
	private function _maxMin(&$maxWidth, &$maxHeight) {

		// 最大宽度检测
		if ($maxWidth && $this->maxWidth && $this->maxWidth < $maxWidth) {
			$maxWidth = $this->maxWidth;
		}

		// 最大高度检测
		if ($maxHeight && $this->maxHeight && $this->maxHeight < $maxHeight) {
			$maxHeight = $this->maxHeight;
		}

		// 最小宽度检测
		if ($maxWidth && $this->minWidth && $this->minWidth < $maxWidth) {
			$maxWidth = $this->minWidth;
		}

		// 最小高度检测
		if ($maxHeight && $this->minHeight && $this->minHeight < $maxHeight) {
			$maxHeight = $this->minHeight;
		}

		// 最大像素
		while (($maxHeight * $maxWidth) > $this->maxPixels) {
			// 最大比例缩小
			if (($maxHeight / $this->maxHeight) > ($maxWidth / $this->maxWidth)) {
				--$maxHeight;
			} else {
				--$maxWidth;
			}
		}
	}
}
