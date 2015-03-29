<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-03-27 08:14:37
/*	Updated: UTC 2015-03-29 02:40:14
/*
/* ************************************************************************** */
namespace Loli\HTML;
class Attribute{

	// 过滤回调函数
	protected $callbacks = [];

	// 过滤允许的属性名
	protected $attributes = [];

	// 标签默认的属性
	protected $defaults = [
		'a' => ['target' => '_blank'],
		'input' => ['type' => 'text'],
		'embed' => ['type' => 'application/x-shockwave-flash'],
		'object' => ['type' => 'application/x-shockwave-flash'],
		'script' => ['type' => 'text/javascript'],
		'style' => ['type' => 'text/css'],
	];

	// type 允许的参数
	protected $types = [
		'input' => ['hidden', 'text', 'password', 'reset', 'radio', 'checkbox','image', 'submit', 'button', 'email', 'url', 'number', 'range', 'search', 'color', 'date', 'month', 'week', 'time', 'datetime', 'datetime-local'],
		'embed' => ['application/x-shockwave-flash'],
		'object' => ['application/x-shockwave-flash'],
		'script' => ['text/javascript', 'text/plain'],
		'style' => ['text/css', 'text/plain'],
	];


	// url 允许的协议
	protected $schemes = ['http', 'https', 'ftp', 'gopher', 'news', 'telnet', 'rtsp', 'mms', 'callto', 'bctp', 'synacast', 'thunder', 'flashget', 'qqid', 'magnet', 'ed2k'];

	// name class  id 的前缀
	protected $prefix = '';

	// class 允许的值
	protected $class = [];

	// id 允许的值
	protected $id = [];

	// name 允许的值
	protected $name = [];

	// classid 允许的值 ie 控件的
	protected $classID = ['clsid:d27cdb6e-ae6d-11cf-96b8-444553540000'];

	// target 允许的值
	protected $targets = ['_blank'];

	// 允许的 rel
	protected $rels = ['stylesheet' => ['link']];

	// style 对象
	protected $style = NULL;

	// 当前标签名
	protected $tag;

	// 是否允许 on 开头的标签 也是 js 标签
	protected $on = true;

	/**
	 * __construct 默认执行
	 * @param Style|null $style 样式类对象
	 */
	public function __construct(Style $style = NULL) {
		// 样式表
		$this->style = $style;


		// 过滤回调函数
		$this->callbacks += [
			'src' => [$this, 'url'],
			'data' => [$this, 'url'],
			'cite' => [$this, 'url'],
			'action' => [$this, 'url'],
			'movie' => [$this, 'url'],
			'base' => [$this, 'url'],
			'href' => [$this, 'url'],

			'span' => [$this, 'intval'],
			'rows' => [$this, 'intval'],
			'cols' => [$this, 'intval'],
			'size' => [$this, 'intval'],
			'border' => [$this, 'intval'],
			'colspan' => [$this, 'intval'],
			'rowspan' => [$this, 'intval'],
			'maxlength' => [$this, 'intval'],
			'cellpadding' => [$this, 'intval'],
			'cellspacing' => [$this, 'intval'],
			'frameborder' => [$this, 'intval'],
			'tabIndex' => [$this, 'intval'],


			'width' => [$this, 'widthHeight'],
			'height' => [$this, 'widthHeight'],

			'value' => [$this, 'value'],
			'title' => [$this, 'value'],
			'alt' => [$this, 'value'],
			'vars' => [$this, 'value'],
			'flashvars' => [$this, 'value'],
			'themenu' => [$this, 'value'],
			'salign' => [$this, 'value'],
			'allowfullscreen' => [$this, 'value'],
			'contenteditable' => [$this, 'value'],
			'spellcheck' => [$this, 'value'],
			'defaultvalue' => [$this, 'value'],
			'pattern' => [$this, 'value'],
			'placeholder' => [$this, 'value'],

			'min' => [$this, 'minMaxStep'],
			'max' => [$this, 'minMaxStep'],
			'step' => [$this, 'minMaxStep'],

			'color' => [$this, 'color'],
			'bgcolor' => [$this, 'color'],

			'controls' => [$this, 'one'],
			'autoplay' => [$this, 'one'],
			'checked' => [$this, 'one'],
			'disabled' => [$this, 'one'],
			'readonly' => [$this, 'one'],
			'required' => [$this, 'one'],
			'autofocus' => [$this, 'one'],
			'async' => [$this, 'one'],
			'defer' => [$this, 'one'],


			'id' => [$this, 'id'],
			'for' => [$this, 'id'],
			'list' => [$this, 'id'],
			'form' => [$this, 'id'],

			'media' => [$this, 'media'],
			'style' => [$this, 'style'],
			'class' => [$this, 'class_'],

			'rel' => [$this, 'rel'],
			'name' => [$this, 'name'],
			'type' => [$this, 'type'],
			'target' => [$this, 'target'],
			'datetime' => [$this, 'datetime'],
			'lang' => [$this, 'lang'],
			'dir' => [$this, 'dir'],
			'usemap' => [$this, 'usemap'],
			'shape' => [$this, 'shape'],
			'coords' => [$this, 'coords'],
			'method' => [$this, 'method'],
			'align' => [$this, 'align'],
			'rules' => [$this, 'rules'],
			'frame' => [$this, 'frame'],
			'valign' => [$this, 'valign'],
			'wmode' => [$this, 'wmode'],
			'quality' => [$this, 'quality'],
			'scale' => [$this, 'scale'],
			'autocomplete' => [$this, 'autoComplete'],
			'classid' => [$this, 'classID'],
		];

		$this->attributes = array_keys($this->callbacks);
	}


	/**
	 * __invoke 过滤属性
	 * @return 回调到 get 方法
	 */
	public function __invoke() {
		return call_user_func_array([$this, 'get'], func_get_args());
	}



	/**
	 * getAttributes 获得允许的属性 js 属性除外
	 * @return array
	 */
	public function getAttributes() {
		return $this->attributes;
	}



	/**
	 * addAttributes 添加允许的属性
	 * @param array $attributes 属性数组
	 * @return this
	 */
	public function addAttributes(array $attributes) {
		$this->attributes = array_merge($attributes, $this->attributes);
		return $this;
	}


	/**
	 * setAttributes 设置允许的属性
	 * @param array $attributes 属性数组
	 * @return this
	 */
	public function setAttributes(array $attributes) {
		$this->attributes = $attributes;
		return $this;
	}

	/**
	 * removeAttributes 移除允许的属性
	 * @param  array  $attributes 属性数组
	 * @return this
	 */
	public function removeAttributes(array $attributes) {
		$this->attributes = array_diff($this->attributes, $attributes);
		return $this;
	}



	/**
	 * getOn 获得是否允许支持js 属性
	 * @return boolean
	 */
	public function getOn() {
		return $this->on;
	}


	/**
	 * 设置是否允许js属性
	 * @param boolean $on 是否允许
	 * @return this
	 */
	public function setOn($on) {
		$this->on = $on;
		return $this;
	}


	/**
	 * get 过滤属性
	 * @param  array  $attributes 属性数组
	 * @param  string $tag        标签名
	 * @return array
	 */
	public function get(array $attributes, $tag) {
		$this->tag = $tag;
		$results = [];
		foreach ($attributes as $attribute => $value) {
			if (!$attribute || !$this->on || substr($attribute, 0, 2) !== 'on') {
				if (!in_array($attribute, $this->attributes) || empty($this->callbacks[$attribute])) {
					continue;
				}
				$value = call_user_func($this->callbacks[$attribute], $value, $attribute);
			}
			if ($value === NULL || $value === false) {
				continue;
			}
			$results[$attribute] = $value;
		}
		if (!empty($this->defaults[$tag])) {
			$results += $this->defaults[$tag];
		}
		return $results;
	}





	protected function one() {
		return '1';
	}


	protected function value($value) {
		return $value;
	}


	protected function intval($value) {
		return intval($value);
	}

	protected function url($value) {
		if (!$value || !($parse = parse_url($value))) {
			return NULL;
		}
		if (!empty($parse['scheme']) && !in_array(strtolower($parse['scheme']), $this->schemes)) {
			return NULL;
		}
		return $value;
	}

	protected function class_($value) {
		if (!$value = trim($value)) {
			return NULL;
		}
		$results = [];
		foreach (explode(' ', trim($value)) as $class) {
			if (!($class = trim($class)) || (!in_array($class, $this->class) && $this->prefix && substr($class, 0, strlen($this->prefix)) !== $this->prefix) || !preg_match('/^[0-9a-z_-]+$/i', $class)) {
				continue;
			}
			$results[] = $class;
		}
		return $results ? implode(' ', $results) : NULL;
	}


	protected function id($value) {
		if (!$value) {
			return NULL;
		}
		if (in_array($value, $this->id)) {
			return $value;
		}
		if ($this->prefix && substr($value, 0, strlen($this->prefix)) !== $this->prefix) {
			return NULL;
		}
		if (!preg_match('/^[0-9a-z_-]+$/i', $value)) {
			return NULL;
		}
		return $value;
	}


	protected function name($value) {
		if (!$value) {
			return NULL;
		}
		if (in_array($value, $this->name)) {
			return $value;
		}
		if ($this->prefix && substr($value, 0, strlen($this->prefix)) !== $this->prefix) {
			return NULL;
		}
		return $value;
	}


	protected function align($value) {
		if (!in_array($value, ['left', 'right', 'top', 'bottom', 'center', 'middle'])) {
			return NULL;
		}
		return $value;
	}

	protected function color($value) {
		if (!preg_match('/^\s*([0-9a-z#]+|rgba?\([0-9,. ]\))\s*$/i', $value)) {
			return NULL;
		}
		return $value;
	}




	protected function type($value) {
		// 其他允许的标签
		if (!empty($this->types[$this->tag]) && ($this->types[$this->tag] === true || in_array($value = strtolower($value), $this->types[$this->tag]))) {
			return $value;
		}

		// 视频 和 音频标签
		if (in_array($this->tag, ['video', 'audio']) && preg_match('/^(video|audio|image)\/[0-9a-z_-]+$/i', $value)) {
			return $value;
		}
		return NULL;
	}


	protected function target($value) {
		return in_array($value, $this->targets) ? $value : NULL;
	}



	protected function datetime($value) {
		return preg_replace('/[^0-9a-z_: -]/i', '', $value);
	}


	protected function lang($value) {
		if (!preg_match('/^[a-z_-]{2,10}$/i', $value)) {
			return NULL;
		}
		return $value;
	}


	protected function dir($value) {
		if (!in_array($value, ['rtl', 'rtl'])) {
			return NULL;
		}
		return $value;
	}


	protected function usemap($value) {
		if (!preg_match('/^[#.]'. $this->prefix .'[0-9a-z_-]+$/i', $value)) {
			return NULL;
		}
		return $value;
	}



	protected function shape($value) {
		if (!in_array($value, ['default', 'rect', 'circ', 'poly'])) {
			return NULL;
		}
		return $value;
	}



	protected function coords($value) {
		return preg_replace('/[^0-9a-z,]/i', '', $value);
	}



	protected function method($value) {
		return $value ? (strtoupper($value) == 'POST' ? 'POST' : 'GET') : NULL;
	}



	protected function rules($value){
		if (!in_array($value, ['none', 'groups', 'rows', 'cols', 'all'])) {
			return NULL;
		}
		return $value;
	}


	protected function frame($value) {
		if (!in_array($value, ['void', 'above', 'below', 'hsides', 'vsides', 'lhs', 'rhs', 'box', 'border'])) {
			return NULL;
		}
		return $value;
	}




	protected function valign($value) {
		if (!in_array($value, ['top', 'middle', 'bottom', 'baseline'])) {
			return NULL;
		}
		return $value;
	}




	protected function wmode($value) {
		return in_array($value = strtolower($value), ['transparent', 'window', 'opaque']) ? $value : 'window';
	}





	protected function quality($value) {
		return in_array($value = strtolower($value), ['low', 'medium', 'high', 'autolow', 'autohigh', 'best']) ? $value : 'high';
	}





	protected function scale($value) {
		return in_array($value = strtolower($value), ['default', 'showall', 'noborder', 'exactfit', 'noscale']) ? $value : 'default';
	}




	protected function widthHeight($value) {
		return preg_replace('/[^0-9.%]/i', '', $value);
	}




	protected function autoComplete($value) {
		return in_array(strtolower($value), ['on', 'off']) ? $value : NULL;
	}




	protected function classID($value) {
		return $this->tag == 'object' && in_array(strtolower($value), $this->classID) ? $value : NULL;
	}




	protected function rel($value) {
		if (!empty($this->rels[$value]) && ($this->rels[$value] === true || in_array($this->tag, $this->rels[$value]))) {
			return $value;
		}
		return NULL;
	}



	protected function minMaxStep($value) {
		return preg_replace('/^[^0-9a-z_:% -]$/i', '', $value);
	}




	protected function style($value) {
		return $value && $this->style ? $this->style->values($value) : NULL;
	}



	protected function media($value) {
		if ($this->tag !== 'style' || !$this->style) {
			return NULL;
		}
		return $this->style->media($value);
	}

}