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
/*	Updated: UTC 2015-03-27 08:14:54
/*
/* ************************************************************************** */
namespace Loli\HTML;
class Form{

	private static $_tags = ['span', 'div', 'p', 'a', 'code'];

	public static function get(array $a, $e = true) {
		$a['type'] = empty($a['type']) ? 'text' : $a['type'];
		if ( $a['type']{0} == '_' || strtolower($a['type']) == 'get' || !method_exists(__CLASS__, $a['type'])) {
			return false;
		}
		return call_user_func_array([__CLASS__, $a['type']], [$a, $e]);
	}



	public static function fieldset(array $a, $e = true, $class = '') {
		$class = (array) $class;
		$class[] = 'form-fieldset';
		$r = '<fieldset class="'. implode(' ', $class) .'">';
		if ( isset($a['legend'])) {
			$r .='<legend class="form-legend">'. $a['legend']  .'</legend>';
		}
		if ( isset($a['value'])) {
			if ( is_array($a['value'])) {
				foreach ( $a['value'] as $kk => $vv) {
					$r .='<div class="form-div form-div-'. $kk .'">'. (is_array($vv) ? self::get($vv, false) : $vv) . '</div>';
				}
			} else {
				$r.= $a['value'];
			}
		}
		$r .= '</fieldset>';

		return self::_result($r, $e);
	}

	/**
	*	text表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function text(array $a, $e = true) {
		return self::_input('text', $a, $e);
	}



	/**
	*	hidden 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function hidden(array $a, $e = true) {
		return self::_input('hidden', $a, $e);
	}

	/**
	*	file 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function file(array $a, $e = true) {
		return self::_input('file', $a, $e);
	}

	/**
	*	password 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function password(array $a, $e = true) {
		return self::_input('password', $a, $e);
	}


	/**
	*	email 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function email(array $a, $e = true) {
		return self::_input('email', $a, $e);
	}

	/**
	*	url 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function url(array $a, $e = true) {
		return self::_input('url', $a, $e);
	}

	/**
	*	search 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function search(array $a, $e = true) {
		return self::_input('search', $a, $e);
	}

	/**
	*	number 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function number(array $a, $e = true) {
		return self::_input('number', $a, $e);
	}

	/**
	*	color 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function color(array $a, $e = true) {
		return self::_input('color', $a, $e);
	}

	/**
	*	range 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function range(array $a, $e = true) {
		return self::_input('range', $a, $e);
	}


	/**
	*	tel 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function tel(array $a, $e = true) {
		return self::_input('tel', $a, $e);
	}

	/**
	*	datetime-local 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function datetime_local(array $a, $e = true) {
		return self::_input('datetime-local', $a, $e);
	}

	/**
	*	datetime-local 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function image(array $a, $e = true) {
		return self::_input('image', $a, $e);
	}
	/**
	*	date 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function datetime(array $a, $e = true) {
		return self::_input('datetime', $a, $e);
	}
	/**
	*	date 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function date(array $a, $e = true) {
		return self::_input('date', $a, $e);
	}

	/**
	*	month 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function month(array $a, $e = true) {
		return self::_input('month', $a, $e);
	}


	/**
	*	week 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function week(array $a, $e = true) {
		return self::_input('week', $a, $e);
	}

	/**
	*	time 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function time(array $a, $e = true) {
		return self::_input('time', $a, $e);
	}

	/**
	*	submit 按钮
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function submit(array $a, $e = true) {
		$a['name'] = isset($a['name']) ? 'submit' : $a['name'];
		return self::_input('submit', $a, $e);
	}
	/**
	*	submit 按钮
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function reset(array $a, $e = true) {
		return self::_input('reset', $a, $e);
	}


	/**
	*	button 按钮
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function button(array $a, $e = true) {
		$a['name'] = empty($a['name']) ? 'button' : $a['name'];
		self::_filter($a, 'button');
		$a['type'] = 'submit';
		$r = self::_label($a);
		$r .= '<button '. self::_attr($a, ['value']) . '><strong>'. $a['value'] .'</strong></button>';
		$r .= self::_tags($a);
		return self::_result($r, $e);
	}


	/**
	*	textarea 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function textarea(array $a, $e = true) {
		self::_filter($a, 'textarea');
		$r = self::_label($a);
		$r .= '<textarea '. self::_attr($a, ['value']) .' >'. $a['value'] .'</textarea>';
		$r .= self::_tags($a);
		return self::_result($r, $e);
	}


	/**
	*	select 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function select(array $a, $e = true) {
		self::_filter($a, 'select');
		$r = self::_label($a);
		$r .= '<select '. self::_attr($a, ['value', 'option']) .' >';
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
		$r .= self::_tags($a);
		return self::_result($r, $e);
	}


	/**
	*	radio 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function radio(array $a, $e = true) {
		self::_filter($a, 'radio');
		$r = '';
		foreach ($a['option'] as $k => $v) {
			$r .= '<label for="'. $a['id'] . '-' . $k .'" class="radio-label radio-'. $a['id'] .'  radio-'. $a['id'] .'-'. $k .'">';
			$r .= '<input type="'. $a['type'] .'" name="'. $a['name'] .'" id="' . $a['id'] . '-' . $k .'" class="'. $a['class'][$k] . '" ' . $a['disabled'][$k] . $a['readonly'][$k] . ' value="'. $k .'" '.($a['value'] == $k ? 'checked="checked"' : '').' />';
			$r .= '<span class="radio-span form-span">' . $v . '</span>';
			$r .= '</label>';
		}
		$r .= self::_tags($a);
		return self::_result($r, $e);
	}

	/**
	*	checkbo 表单
	*
	*	1 参数 数组
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	public static function checkbox(array $a, $e = true) {
		if (empty($a['option'])) {
			return self::_input('checkbox', $a, $e);
		}
		self::_filter($a, 'checkbox');
		$r = self::_label($a);
		foreach ($a['option'] as $k => $v) {
			$r .= '<label for="'. $a['id'] . '-' . $k .'" class="checkbox-label checkbox-'. $a['id'] .' checkbox-'. $a['id'] .'-'. $k .'">';
			$r .= '<input type="'. $a['type'] .'" name="'. $a['name'] .'" id="' . $a['id'] . '-' . $k .'" class="'. $a['class'][$k] . '" ' . $a['disabled'][$k] . $a['readonly'][$k].  ' value="'. $k .'" '.(in_array((string) $k,  $a['value']) ? 'checked="checked"' : '').' />';
			$r .= '<span class="checkbox-span form-span">' . $v . '</span>';
			$r .= '</label>';
		}
		$r .= self::_tags($a);
		return self::_result($r, $e);
	}


	/**
	*	统一 input 标签
	*
	*	1 参数 type
	*	2 参数 a 数组
	*	3 参数 e 是否显示
	*
	**/
	private static function _input($type,  array $a, $e = true) {
		self::_filter($a, $type);
		$r = self::_label($a);
		$r .= '<input '. self::_attr($a) . '/>';
		$r .= self::_tags($a);
		return self::_result($r, $e);
	}

	/**
	*	表单 判断是否显示
	*
	*	1 参数 显示或 返回值
	*	2 参数 是否显示
	*
	*	返回值 或者显示
	**/
	private static function _result($r, $e) {
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
	private static function _label(array $a) {
		return empty($a['label']) ? '' : '<label for="'. $a['id'] . '" class="label-'. $a['id'] . ' form-label">'. $a['label'] . '</label>';
	}

	/**
	*	表单 tags
	*
	*	1 参数 表单数组
	*
	*	返回值 label 标签 或 ''
	**/
	private static function _tags(array $a, array $tags = []) {
		$r = '';
		foreach ( array_intersect_key($a, array_flip($tags?(array)$tags:self::$_tags)) as $k => $v) {
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
	private static function _attr(array $a, array $in = []) {
		$r = '';
		foreach ($a as $k => $v) {
			if ( $k == 'label' || $k == 'legend' || in_array($k, self::$_tags) || in_array($k, $in) || (!$v && !in_array($k, ['value', 'min', 'max']))) {
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
	private static function _filter(array &$a, $type = 'text') {
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
		self::_html($a);
		return true;
	}

	private static function _html(array &$a, array $tags = []) {
		foreach ($a as $k => &$v) {
			if (in_array($k, $tags ? (array)$tags : self::$_tags)) {
				continue;
			}
			if (is_array($v)) {
				self::_html($v, []);
			} else {
				$v = strtr($v, ['"' => '&quot;', '\'' => '&#039;', '<' => '&lt;', '>' => '&gt;']);
			}
		}
	}
}
