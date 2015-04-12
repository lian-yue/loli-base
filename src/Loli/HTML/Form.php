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
/*	Updated: UTC 2015-04-12 10:45:44
/*
/* ************************************************************************** */
namespace Loli\HTML;
class Form{

	private static $_tags = ['span', 'div', 'p', 'a', 'code'];
	private static $_types = ['text', 'hidden', 'file', 'password', 'email', 'url', 'search', 'number', 'color', 'range', 'tel', 'datetime-local', 'image', 'datetime', 'date', 'month', 'week', 'time', 'submit', 'reset', 'button', 'textarea', 'select', 'radio', 'checkbox', 'fieldset', 'table', 'lists'];


	/**
	 * $_forms
	 * @var array
	 */
	private $_forms = [];

	public function __construct(array $array = []) {
		$array && $this->__invoke($array);
	}

	/**
	 * __toString 输出
	 */
	public function __toString() {
		return implode('', $this->_forms);
	}

	/**
	 * __call 回调函数
	 * @param  string $name 表单类型名
	 * @param  array $args  回调的参数
	 * @return this
	 */
	public function __call($name, $args) {
		$args[0]['type'] = $name;
		$this->_forms[] = call_user_func_array([__CLASS__, 'get'], [$args[0], false]);
		if (!empty($args[1])) {
			echo implode('', $this->_forms);
			$this->_forms = [];
		}
		return $this;
	}

	/**
	 * __invoke 作为函数执行
	 * @param  array  $array 数组
	 * @return this
	 */
	public function __invoke(array $array) {
		return $this->__call(empty($array['type']) ? reset(self::$_types) : $array['type'], func_get_args());
	}


















	/**
	 * get 获得
	 * @param  array            $array  表单数组
	 * @param  boolean          $echo   是否显示
	 * @return string|output
	 */

	public static function get(array $array, $echo = true) {
		$array['type'] = empty($array['type']) ? reset(self::$_types) : $array['type'];
		if (!in_array($array['type'], self::$_types)) {
			return false;
		}
		return call_user_func_array([__CLASS__, $array['type']], [$array, $echo]);
	}

	/**
	 * __callStatic 静态回调方法
	 * @param  string $name 方法名
	 * @param  array $args 回调参数二维数组
	 * @return string or echo
	 */
	public static function __callStatic($name, $args) {
		$name = strtr($name, '_', '-');
		if (!in_array($name, self::$_types)) {
			throw new Exception('Form type unknown');
		}
		$args += [[], true];
		list($array, $echo) = $args;


		$result .= self::_label($array);

		switch ($name) {
			case 'lists':
				$class = empty($array['class']) ? [] : (array) $array['class'];
				$class[] = 'form-lists';
				$current = empty($array['url']) ? (empty($array['query']) ? [] : parse_string($array['query'])) : (is_array($array['url']) ? ['query' => $array['url']] : parse_url($array['url']));
				$thead = empty($array['thead']) ? (empty($array['head']) ? [] : $array['head']) : $array['thead']
				$thead = array_unnull($thead);
				$result .= '<table class="'. implode(' ', $class) .'" >';
				if ($thead) {
					$result .= '<thead>';
					$result .= '<tr>';
					foreach ($thead as $key => $value) {
						if (is_array($value)) {
							$value += ['url' => [], 'value' => ''];
							// 自动设置url
							if (!$value['url'] || is_array($value['url'])) {
								$parse['query'] = parse_string($value['url']);
								$parse['query'] = array_intersect_key($parse['query'],  ['$order' => NULL] + array_flip($parse['url']));
								//if (is_array($v['url'])) {
								//	$parse['query'] = array_intersect_key($parse['query'],  ['$order' => NULL] + array_flip($v['url']));
								//}
								//$parse['query']['$orderby'] = $k;
								//$parse['query']['$order'] = empty($parse['query']['$order']) || strtoupper($parse['query']['$order']) != 'ASC' ? 'ASC' : 'DESC';
								//$parse['query'] = merge_string($parse['query']);
								//$v['url'] = merge_url($parse);


							} else {
								$isEmptyScheme = substr($value['url'], 0, 2) == '//';
								$parse = parse_url($isEmptyScheme ? 'http:' . $value['url'] : $value['url']);
								if ($isEmptyScheme) {
									unset($parse['scheme']);
								}
							}
							//$parse['query'] = empty($parse['query']) ? [] : parse_string($parse['query']);
							//$order = '';
							//if (r('$orderby') == $k) {
							//	if ($parse['query'] && !empty($parse['query']['$order']) && strtoupper($parse['query']['$order']) == 'ASC') {
							//		$order = 'desc';
							//	} else {
							//		$order = 'asc';
							//	}
							//}
							$result .= '<td class=" td-'. $key .' '. $order .'"><a href="'. $value['url'] .'"><span>' . $value['value'] . '</span><sorting></sorting></a></td>';











						} else {
							$result .= '<td class="td-'. $key .'"><span>' .$value. '</span></td>';
						}
					}


				break;
			case 'table':
				$class = empty($array['class']) ? [] : (array) $array['class'];
				$class[] = 'form-table';
				$result .= '<table class="' . implode(' ', $class) .'" >';
				$result .= '<tbody>';
				$i = 0;
				foreach (empty($array['value']) ? (empty($array['tbody']) ? [] : $array['tbody']) : $array['value'] as $key => $value) {
					$value = is_string($value) ? ['value' => $value, 'class' => []] : $value + ['class' => []];

					if (isset($value['value']) && is_array($value['value'])) {
						$value['value'] = self::get($value['value'], false);
					}
					$value['class'] = (array) $value['class'];
					$value['class'][] = $i % 2 ? 'odd' : 'even';
					$value['class'][] ='tr-'. $key;

					if (isset($value['title']) || isset($value['value'])) {
						$result .= '<tr class="'. implode(' ', $value['class']) .'">';
						if (isset($value['title'])) {
							$result .= '<th class="title" ' . (isset($value['value']) ? '' : 'colspan="2"') . '>' .$value['title']. '</th>';
						}
						if (isset($value['value'])) {
							$result .= '<td class="value" ' . (isset($value['title']) ? '' : 'colspan="2"') . '>' .$value['value']. '</td>';
						}
						$result .= '</tr>';
					}
					++$i;
				}
				$result .= '</tbody>';
				$result .= '</table>';
				break;
			case 'fieldset':
				$class = empty($array['class']) ? [] : (array) $array['class'];
				$class[] = 'form-fieldset';
				$result .= '<fieldset class="'. implode(' ', $class) .'">';
				if (isset($array['legend'])) {
					$result .='<legend class="form-legend">'. $array['legend']  .'</legend>';
				}
				if (isset($array['value'])) {
					if (is_array($array['value'])) {
						foreach ($array['value'] as $key => $value) {
							$result .='<div class="form-div form-div-'. $key .'">'. (is_array($value) ? self::get($value, false) : $value) . '</div>';
						}
					} else {
						$result .= $array['value'];
					}
				}
				$result .= '</fieldset>';
				break;
			case 'checkbox':
				foreach ($array['option'] as $key => $value) {
					$result .= '<label for="'. $array['id'] . '-' . $key .'" class="checkbox-label checkbox-'. $array['id'] .' checkbox-'. $array['id'] .'-'. $key .'">';
					$result .= '<input type="'. $array['type'] .'" name="'. $array['name'] .'" id="' . $array['id'] . '-' . $key .'" class="'. $array['class'][$key] . '" ' . $array['disabled'][$key] . $array['readonly'][$key].  ' value="'. $key .'" '.(in_array((string) $key,  $array['value']) ? 'checked="checked"' : '').' />';
					$result .= '<span class="checkbox-span form-span">' . $value . '</span>';
					$result .= '</label>';
				}
				break;
			case 'radio':
				foreach ($array['option'] as $key => $value) {
					$result .= '<label for="'. $array['id'] . '-' . $key .'" class="radio-label radio-'. $array['id'] .'  radio-'. $array['id'] .'-'. $key .'">';
					$result .= '<input type="'. $array['type'] .'" name="'. $array['name'] .'" id="' . $array['id'] . '-' . $key .'" class="'. $array['class'][$key] . '" ' . $array['disabled'][$key] . $array['readonly'][$key] . ' value="'. $key .'" '.($array['value'] == $key ? 'checked="checked"' : '').' />';
					$result .= '<span class="radio-span form-span">' . $value . '</span>';
					$result .= '</label>';
				}
				break;
			case 'select':
				$result .= '<select '. self::_attributes($array, ['value', 'option']) .' >';
				foreach ($array['option'] as $key => $value) {
					if (is_array($value) && isset($value['label']) && isset($value['value'])) {
							$result .= '<optgroup class="optgroup-'. $key .'" label="'. $value['label'] .'">';
							foreach ($value['value'] as $k => $v) {
								$result .= '<option '. (in_array($k, $array['value']) ? ' selected="selected"' : '') .' class="select-'. $k .' select-'. $key .'-'. $k .'" value="'. $k .'">'. $v .'</option>';
							}
							$result .= '</optgroup>';
					} else {
						$result .= '<option '. (in_array($key, $array['value']) ? ' selected="selected"' : '') .' class="select-'. $key .'" value="'. $key .'">'. $value .'</option>';
					}
				}
				$result .= '</select>';
				break;
			case 'textarea':
				$result .= '<textarea '. self::_attributes($array, ['value']) .' >'. $array['value'] .'</textarea>';
				break;
			case 'button':
				$array['name'] = empty($array['name']) ? 'button' : $array['name'];
				$array['type'] = 'submit';


				$result .= '<button '. self::_attributes($array, ['value']) . '><strong>'. $array['value'] .'</strong></button>';
				break;
			case 'submit':
				$array['name'] = isset($array['name']) ? 'submit' : $array['name'];
			default:
				$result .= '<input '. self::_attributes($array) . '/>';
		}





		$result .= self::_tags($array);
	}




	/**
	 * _label 标签
	 * @param  array  $array 数组
	 * @return string
	 */
	private static function _label(array $array) {
		return empty($array['label']) ? '' : '<label for="'. $array['id'] . '" class="label-'. $array['id'] . ' form-label">'. $array['label'] . '</label>';
	}


	/**
	 * _tags tags名
	 * @param  array  $array 数组
	 * @param  array  $tags  只能匹配的key
	 * @return string
	 */
	private static function _tags(array $array, array $tags = []) {
		$result = '';
		foreach (array_intersect_key($array, array_flip($tags ? $tags : self::$_tags)) as $key => $value) {
			if (!$value) {
				continue;
			}
			if (is_array($value)) {
				$this->_text($value, []);
				$result .= '<' . $key . is_array($value) ? self::_attributes($value) . '>';
			} else {
				$result .= '<' . $key .' class="'. $key .'-'. $array['id'] . ' ' . $array['type'] .'-'. $key .' form-'. $key .'">' . $value;
			}
			$result .= '</'. $key .'>';
		}
		return $result;
	}



	/**
	 * _attributes
	 * @param  array  $array 数组
	 * @param  array  $names 不使用的names
	 * @return this
	 */
	private static function _attributes(array $array, array $names) {
		$result = '';
		foreach ($array as $key => $value) {
			if ($key == 'label' || $key == 'legend' || in_array($key, self::$_tags) || in_array($key, $names) || (!$value && !in_array($key, ['value', 'min', 'max']))) {
				continue;
			}
			$value = is_array($value) || is_object($value) ? reset($value) : $value;
			$result .= ' '. $key .'="'. $value .'"';
		}
		return $result;
	}


	/**
	 * _text 过滤html 代码
	 * @param  array  &$array 数组
	 * @param  array  $tags = NULL   不过滤的标签
	 */
	private static function _text(array &$array, array $tags = NULL) {
		foreach ($array as $key => &$value) {
			if (in_array($key, $tags === NULL ? self::$_tags : $tags)) {
				continue;
			}
			if (is_array($value)) {
				self::_text($value, []);
			} else {
				$value = strtr($value, ['"' => '&quot;', '\'' => '&#039;', '<' => '&lt;', '>' => '&gt;']);
			}
		}
	}





	private static function _filter(array &$array, $type = 'text') {
		$array['name'] = empty($array['name']) ? 'form' : $array['name'];
		$i = 'form-' . preg_replace('/[^0-9a-z_-]/i','_', $array['name']);
		$array = $array + [ 'name' => '', 'class' => '', 'id' => $i, 'value' => '', 'option' => []];

		$array['type'] = $type;
		$array['option'] = (array) $array['option'];

		if ($in_array = in_array($array['type'], ['checkbox', 'radio']) && !empty($array['option'])) {
			$class = [];
			foreach ($array['option'] as $k =>$v) {
				if (is_array($array['class']) && !empty($array['class'][$k])) {
					$class[$k] = preg_replace("/[^0-9a-zA-Z_ -]/",'', $array['class'][$k]);
				} else {
					$class[$k] = !is_array($array['class']) && !empty($array['class']) ? preg_replace("/[^0-9a-zA-Z_ -]/",'', $array['class']) : $i;
				}
				$class[$k] .= ' '. $type;
			}
			$array['class'] = $class;
		} else {
			$array['class'] =  $array['class'] ? preg_replace("/[^0-9a-zA-Z_ -]/",'', is_array($array['class']) ? implode(' ', $array['class']) : $array['class']) : $i;
			$array['class'] .= ' '. $type;
		}

		if ($in_array) {
			$disabled = [];
			foreach ($array['option'] as $k =>$v) {
				$disabled[$k] =! empty($array['disabled']) && (!is_array($array['disabled']) || in_array((string) $k, $array['disabled'])) ? 'disabled' : '';
				if ($disabled[$k]) {
					$array['class'][$k] .= ' disabled';
				}
			}
			$array['disabled'] = $disabled;
		} elseif (!empty($array['disabled'])) {
			$array['disabled'] = 'disabled';
			$array['class'] .= ' disabled';
		}


		if ($in_array) {
			$readonly = [];
			foreach ($array['option'] as $k =>$v) {
				$readonly[$k] =! empty($array['readonly']) && (!is_array($array['readonly']) || in_array((string) $k, $array['readonly'])) ? 'readonly' : '';
				if ($readonly[$k]) {
					$array['class'][$k] .= ' readonly';
				}
			}
			$array['readonly'] = $readonly;
		} elseif (!empty($array['readonly'])) {
			$array['readonly'] = 'readonly';
			$array['class'] .= ' readonly';
		}




		if ($array['type'] == 'select' && !empty($array['multiple'])) {
			$array['class'] .= ' multiple';
		}

		// 值是数组
		if (in_array($array['type'], ['checkbox', 'select'])) {
			$array['value'] = (array) $array['value'];
		} elseif (is_array($array['value']) || is_object($array['value'])) {
			$array['value'] = htmlspecialchars(json_encode($array['value']), ENT_QUOTES);
		}

		// html 转义
		self::_text($array);
		return true;
	}
}
