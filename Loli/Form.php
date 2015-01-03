<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-03-24 11:06:22
/*	Updated: UTC 2015-01-01 14:12:31
/*
/* ************************************************************************** */
namespace Model;
use Loli\Model;
class Form extends Model{

	private $_tags = ['span', 'div', 'p', 'a', 'code'];

	public function __invoke($a = [], $e = true) {
		$a['type'] = empty($a['type']) ? 'text' : $a['type'];
		if (in_array(strtolower($a['type']), ['attr', 'filter', 'label', '_tags', 'result', 'input', 'html']) || !method_exists($this->form, $a['type'])) {
			return false;
		}
		return $this->{$a['type']}($a, $e);
	}



	public function fieldset($a, $e = true, $class = '') {
		$class = (array) $class;
		$class[] = 'form-fieldset';
		$r = '<fieldset class="'. implode(' ', $class) .'">';
		if ( isset($a['legend'])) {
			$r .='<legend class="form-legend">'. $a['legend']  .'</legend>';
		}
		if ( isset($a['value'])) {
			if ( is_array($a['value'])) {
				foreach ( $a['value'] as $kk => $vv) {
					$r .='<div class="form-div form-div-'. $kk .'">'. (is_array($vv) ? $this->__invoke($vv, false) : $vv) . '</div>';
				}
			} else {
				$r.= $a['value'];
			}
		}
		$r .= '</fieldset>';

		return $this->_result($r, $e);
	}

	/**
	*	text表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function text($a = [], $e = true) {
		return $this->_input('text', $a, $e);
	}



	/**
	*	hidden 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function hidden($a = [], $e = true) {
		return $this->_input('hidden', $a, $e);
	}

	/**
	*	file 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function file($a = [], $e = true) {
		return $this->_input('file', $a, $e);
	}

	/**
	*	password 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function password($a = [], $e = true) {
		return $this->_input('password', $a, $e);
	}


	/**
	*	email 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function email($a = [], $e = true) {
		return $this->_input('email', $a, $e);
	}

	/**
	*	url 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function url($a = [], $e = true) {
		return $this->_input('url', $a, $e);
	}

	/**
	*	search 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function search($a = [], $e = true) {
		return $this->_input('search', $a, $e);
	}

	/**
	*	number 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function number($a = [], $e = true) {
		return $this->_input('number', $a, $e);
	}

	/**
	*	color 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function color($a = [], $e = true) {
		return $this->_input('color', $a, $e);
	}

	/**
	*	range 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function range($a = [], $e = true) {
		return $this->_input('range', $a, $e);
	}


	/**
	*	tel 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function tel($a = [], $e = true) {
		return $this->_input('tel', $a, $e);
	}

	/**
	*	datetime-local 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function datetimeLocal($a = [], $e = true) {
		return $this->_input('datetime-local', $a, $e);
	}

	/**
	*	datetime-local 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function image($a = [], $e = true) {
		return $this->_input('image', $a, $e);
	}
	/**
	*	date 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function datetime($a = [], $e = true) {
		return $this->_input('datetime', $a, $e);
	}
	/**
	*	date 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function date($a = [], $e = true) {
		return $this->_input('date', $a, $e);
	}

	/**
	*	month 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function month($a = [], $e = true) {
		return $this->_input('month', $a, $e);
	}


	/**
	*	week 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function week($a = [], $e = true) {
		return $this->_input('week', $a, $e);
	}

	/**
	*	time 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function time($a = [], $e = true) {
		return $this->_input('time', $a, $e);
	}

	/**
	*	submit 按钮
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function submit($a = [], $e = true) {
		$a['value'] = empty($a['value']) ? $this->lang->__('Submit') : $a['value'];
		$a['name'] = empty($a['name']) ? 'submit' : $a['name'];
		return $this->_input('submit', $a, $e);
	}
	/**
	*	submit 按钮
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function reset($a = [], $e = true) {
		return $this->_input('reset', $a, $e);
	}


	/**
	*	button 按钮
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function button($a = [], $e = true) {
		$a['value'] = empty($a['value']) ? $this->lang->__('Submit') : $a['value'];
		$a['name'] = empty($a['name']) ? 'button' : $a['name'];
		$this->_filter($a, 'button');
		$a['type'] = 'submit';
		$r = $this->_label($a);
		$r .= '<button '. $this->_attr($a, ['value']) . '><strong>'. $a['value'] .'</strong></button>';
		$r .= $this->_tags($a);
		return $this->_result($r, $e);
	}


	/**
	*	textarea 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function textarea($a = [], $e = true) {
		$this->_filter($a, 'textarea');
		$r = $this->_label($a);
		$r .= '<textarea '. $this->_attr($a, ['value']) .' >'. $a['value'] .'</textarea>';
		$r .= $this->_tags($a);
		return $this->_result($r, $e);
	}


	/**
	*	select 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function select($a = [], $e = true) {
		$this->_filter($a, 'select');
		$r = $this->_label($a);
		$r .= '<select '. $this->_attr($a, ['value', 'option']) .' >';
		foreach ($a['option'] as $k => $v) {
			if (is_array($v) && isset($v['label']) && isset($v['value'])) {
					$r .= '<optgroup class="optgroup-'. $k .'" label="'. $v['label'] .'">';
					foreach ($v['value'] as $kk => $vv) {
						$r .= '<option '. (in_array((string) $kk, $a['value']) ? ' selected="selected"' : '') .' class="select-'. $kk .' select-'. $k .'-'. $kk .'" value="'. $kk .'">'. $vv .'</option>';
					}
					$r .= '</optgroup>';
			} else {
				$r .= '<option '. (in_array((string) $k, $a['value']) ? ' selected="selected"' : '') .' class="select-'. $k .'" value="'. $k .'">'. $v .'</option>';
			}
		}
		$r .= '</select>';
		$r .= $this->_tags($a);
		return $this->_result($r, $e);
	}


	/**
	*	radio 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function radio($a = [], $e = true) {
		$this->_filter($a, 'radio');
		$r = '';
		foreach ($a['option'] as $k => $v) {
			$r .= '<label for="'. $a['id'] . '-' . $k .'" class="radio-label radio-'. $a['id'] .'  radio-'. $a['id'] .'-'. $k .'">';
			$r .= '<input type="'. $a['type'] .'" name="'. $a['name'] .'" id="' . $a['id'] . '-' . $k .'" class="'. $a['class'][$k] . '" ' . $a['disabled'][$k] . $a['readonly'][$k] . ' value="'. $k .'" '.($a['value'] == $k ? 'checked="checked"' : '').' />';
			$r .= '<span class="radio-span form-span">' . $v . '</span>';
			$r .= '</label>';
		}
		$r .= $this->_tags($a);
		return $this->_result($r, $e);
	}

	/**
	*	checkbo 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public function checkbox($a = [], $e = true) {
		if (empty($a['option'])) {
			return $this->_input('checkbox', $a, $e);
		}
		$this->_filter($a, 'checkbox');
		$r = $this->_label($a);
		foreach ($a['option'] as $k => $v) {
			$r .= '<label for="'. $a['id'] . '-' . $k .'" class="checkbox-label checkbox-'. $a['id'] .' checkbox-'. $a['id'] .'-'. $k .'">';
			$r .= '<input type="'. $a['type'] .'" name="'. $a['name'] .'" id="' . $a['id'] . '-' . $k .'" class="'. $a['class'][$k] . '" ' . $a['disabled'][$k] . $a['readonly'][$k].  ' value="'. $k .'" '.(in_array((string) $k,  $a['value']) ? 'checked="checked"' : '').' />';
			$r .= '<span class="checkbox-span form-span">' . $v . '</span>';
			$r .= '</label>';
		}
		$r .= $this->_tags($a);
		return $this->_result($r, $e);
	}


	/**
	*	统一 input 标签
	*
	*	1 参数 type
	*	2 参数 a 数组
	*	3 参数 e 是否显示
	*
	**/
	private function _input($type,  $a = [], $e = true) {
		$this->_filter($a, $type);
		$r = $this->_label($a);
		$r .= '<input '. $this->_attr($a) . '/>';
		$r .= $this->_tags($a);
		return $this->_result($r, $e);
	}

	/**
	*	表单 判断是否显示
	*
	*	1 参数 显示或 返回值
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	private function _result($r, $e) {
		if (!$e) {
			return $r;
		}
		echo $r;
	}

	/**
	*	表单 label
	*
	*	1 参数 表单数组
	*
	*	返回值 label 标签 或 ''
	**/
	private function _label($a) {
		return empty($a['label']) ? '' : '<label for="'. $a['id'] . '" class="label-'. $a['id'] . ' form-label">'. $a['label'] . '</label>';
	}

	/**
	*	表单 tags
	*
	*	1 参数 表单数组
	*
	*	返回值 label 标签 或 ''
	**/
	private function _tags($a, $tags = []) {
		$r = '';
		foreach ( array_intersect_key($a, array_flip($tags?(array)$tags:$this->_tags)) as $k => $v) {
			if ( $v) {
				if ( is_array( $v)) {
					$r .= '<' . $k;
					foreach ($v as $kk => $vv) {
						$r .= ' '. preg_replace('/[^0-9a-z_-]/i','_', $kk) .'="'. strtr($vv, ['"' => '&quot;', '\'' => '&#039;', '<' => '&lt;', '>' => '&gt;']) .'"';
					}
					$r .= '>';
				} else {
					$r .= '<'. $k .' class="'. $k .'-'. $a['id'] . ' ' . $a['type'] .'-'. $k .' form-'. $k .'">'. $v;
				}
				$r .= '</'. $k .'>';
			}
		}
		return $r;
	}


	/**
	*	标签 attr
	*
	*	回调函数
	*
	*	请勿直接使用
	***/
	private function _attr($a, $in = []) {
		$r = '';
		foreach ($a as $k => $v) {
			if ( $k == 'label' || $k == 'legend' || in_array($k, $this->_tags) || in_array($k, $in) || (!$v && !in_array($k, ['value', 'min', 'max']))) {
				continue;
			} else {
				$v = is_array($v) || is_object($v) ? reset($v) : $v;
				$r .= ' '. $k .'="'. $v .'"';
			}
		}
		return $r;
	}





	/**
	*	表单 数组整理
	*
	*	1 参数 数组
	*	2 参数 表单类型
	*
	*	引用返回
	**/
	private function _filter(&$a, $type = 'text') {
		$a['name'] = empty($a['name']) ? 'form' : $a['name'];
		$i = 'form-' . preg_replace('/[^0-9a-z_-]/i','_', $a['name']);
		$a = $a + [ 'name' => '', 'class' => '', 'id' => $i, 'value' => '', 'option' => []];

		$a['type'] = $type;
		$a['option'] = (array) $a['option'];

		if ($in_array = in_array($a['type'], ['checkbox', 'radio']) && !empty($a['option'])) {
			$class = [];
			foreach ($a['option'] as $k =>$v) {
				if (is_array($a['class']) && !empty($a['class'][$k])) {
					$class[$k] = preg_replace("/[^0-9a-zA-Z_ -]/",'', $a['class'][$k]);
				} else {
					$class[$k] = !is_array($a['class']) && !empty($a['class']) ? preg_replace("/[^0-9a-zA-Z_ -]/",'', $a['class']) : $i;
				}
				$class[$k] .= ' '. $type;
			}
			$a['class'] = $class;
		} else {
			$a['class'] =  $a['class'] ? preg_replace("/[^0-9a-zA-Z_ -]/",'', is_array($a['class']) ? implode(' ', $a['class']) : $a['class']) : $i;
			$a['class'] .= ' '. $type;
		}

		if ($in_array) {
			$disabled = [];
			foreach ($a['option'] as $k =>$v) {
				$disabled[$k] =! empty($a['disabled']) && (!is_array($a['disabled']) || in_array((string) $k, $a['disabled'])) ? 'disabled' : '';
				if ($disabled[$k]) {
					$a['class'][$k] .= ' disabled';
				}
			}
			$a['disabled'] = $disabled;
		} elseif (!empty($a['disabled'])) {
			$a['disabled'] = 'disabled';
			$a['class'] .= ' disabled';
		}


		if ($in_array) {
			$readonly = [];
			foreach ($a['option'] as $k =>$v) {
				$readonly[$k] =! empty($a['readonly']) && (!is_array($a['readonly']) || in_array((string) $k, $a['readonly'])) ? 'readonly' : '';
				if ($readonly[$k]) {
					$a['class'][$k] .= ' readonly';
				}
			}
			$a['readonly'] = $readonly;
		} elseif (!empty($a['readonly'])) {
			$a['readonly'] = 'readonly';
			$a['class'] .= ' readonly';
		}




		if ($a['type'] == 'select' && !empty($a['multiple'])) {
			$a['class'] .= ' multiple';
		}

		// 值是数组
		if (in_array($a['type'], ['checkbox', 'select'])) {
			$a['value'] = (array) $a['value'];
		} elseif ( is_array($a['value']) || is_object($a['value'])) {
			$a['value'] = htmlspecialchars(json_encode($a['value']), ENT_QUOTES);
		}

		// html 转义
		$this->_html($a);
		return true;
	}

	private function _html(&$a, $tags = []) {
		foreach ($a as $k => &$v) {
			if (in_array($k, $tags ? (array)$tags : $this->_tags)) {
				continue;
			}
			if (is_array($v)) {
				$this->_html($v, []);
			} else {
				$v = strtr($v, ['"' => '&quot;', '\'' => '&#039;', '<' => '&lt;', '>' => '&gt;']);
			}
		}
	}
}
