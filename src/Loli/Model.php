<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-09-04 07:54:16
/*
/* ************************************************************************** */
namespace Loli;
use ArrayAccess;
class_exists('Loli\Route') || exit;
class Model{

	protected $route;

	// 表单验证
	protected $form = [
		/*[
			'title' => '',				//  表单标题
			'name' => '',				//  表单 name
			'type' => 'email',			//  表单类型
			'pattern' => '',			// 正则验证
			'value' => '',				// 默认值
			'required' => true,			// 是否允许空
			'option' => [],				// 允许的 isset()
			'min' => -1,				// 允许的最小值
			'max' => 10,				// 允许的最大值
			'step' => 3,				// 合法的数字间隔
			'placeholder' => 3,			// 表单的输入提示
			'maxlength' => 1,			// 最小字符串长度
			'errormessage' => '',		// 错误消息
		],*/
	];

	public function __construct(Route &$route) {
		$this->route = &$route;
	}

	public function getForm() {
		return $this->form;
	}

	// 表单验证
	protected function formVerify(array &$array, $message = NULL) {
		foreach ($this->form as $input) {
			if (empty($input['errormessage'])) {
				$input['errormessage'] = 1000;
			}
			if (in_array($input['type'], ['checkbox', 'radio', 'select'], true)) {
				$input['option'] = empty($input['option']) ? [] : (array) $input['option'];
			}
			$input['title'] = isset($input['title']) ? $this->route->localize($input['title']) : $input['name'];


			if (!isset($array[$input['name']])) {
				continue;
			}
			$name = $input['option'];
			$value = &$array[$input['name']];


			if (isset($input['value'])) {
				settype($value, gettype($input['value']));
			}

			// 空检查
			$empty = empty($value) && $value !== '0' && $value !== 0;
			if (!empty($input['required']) && $empty) {
				$message = new Message([$input['errormessage'] + 1, $input['name'], $input['title']], Message::ERROR, $message);
				continue;
			}


			// 表单类型检查
			$continue = false;
			switch ($input['type']) {
				case 'text':
				case 'password':
					$value = str_replace(["\r", "\n"], '', (string) $value);
					break;
				case 'textarea':
					$value = (string) $value;
					break;
				case 'email':
					$continue = !$empty && !filter_var($value, FILTER_VALIDATE_EMAIL);
					break;
				case 'number':
				case 'range':
					$value = (int) $value;
					break;
				case 'search':
					$value = is_array($value) ? implode(' ', $value) : (string) $value;
					break;
				case 'url':
					$continue = !$empty && !preg_match('/^[a-z]+\:\/\/\w+/i', $value);
				case 'tel':
					$continue = !$empty && !preg_match('/^\+?[0-9]+(\s[0-9]+)*$/i', $value);
				case 'color':
					$continue = !$empty && !preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value = strtolower($value));
					break;
				case 'year':
					$continue = !$empty && preg_match('/^\d{4}$/', $value);
					break;
				case 'month':
					$continue = !$empty && preg_match('/^\d{4}\-(?:0\d|1[0-2])$/', $value);
					break;
				case 'week':
					$continue = !$empty && preg_match('/^\d{4}\-W(?:0\d|1[0-2])$/', $value = strtoupper($value));
					break;
				case 'date':
					if (!$empty) {
						if (preg_match('/^\d{4}\-(\d{2})\-(\d{2})$/', $value) && ($time = strtotime($value))) {
							$value = date('Y-m-d', $value);
						} else {
							$continue = true;
						}
					}
					break;
				case 'datetime':
					if (!$empty) {
						if ($time = strtotime($value)) {
							$value = date('Y-m-d h:i:s', $value);
						} else {
							$continue = true;
						}
					}
					break;
				case 'datetime-local':
					break;
				case 'radio':
					if (!is_scalar($value) || !isset($input['option'][$value])) {
						$continue = true;
					}
					break;
				case 'checkbox':
					$value = array_values((array) $value);
					$value = array_intersect($value, $input['option']);
					if (!$value) {
						$continue = true;
					}
					break;
				case 'select':
					if (empty($input['multiple'])) {
						if (!is_scalar($value)) {
							$value = reset($value);
						}
						$continue = !is_scalar($value) || !isset($input['option'][$value]);
					} elseif (!$$value = array_intersect(array_values((array) $value), $input['option'])) {
						$continue = true;
					}
					break;
			}


			// 表单类型错误
			if ($continue) {
				$message = new Message([$input['errormessage'], $input['name'], $input['title']], Message::ERROR, $message);
				continue;
			}

			// 最大长度
			if (!empty($input['maxlength']) && mb_strlen($value) > $input['maxlength']) {
				$message = new Message([$input['errormessage'] + 2, $input['name'], $input['title']], Message::ERROR, $message);
				continue;
			}

			// 范围
			if ((isset($input['min']) && $input['min'] > $value) || (isset($input['max']) && $input['max'] < $value) || (!empty($input['step']) && ($value % $input['step']) !== 0)) {
				$message = new Message([$input['errormessage'] + 3, $input['name'], $input['title']], Message::ERROR, $message);
				continue;
			}

			// 规定数据
			if (isset($input['option']) && count((array)$value) === count(array_intersect((array)$value, array_keys($input['option'])))) {
				$message = new Message([$input['errormessage'] + 4, $input['name'], $input['title']], Message::ERROR, $message);
				continue;
			}

			// 正则
			if (isset($input['pattern']) && preg_match('/'. str_replace('/', '\\/', $input['pattern']) .'/', $value)) {
				$message = new Message([$input['errormessage'], $input['name'], $input['title']], Message::ERROR, $message);
				continue;
			}
		}
		if ($message) {
			throw new $message;
		}
	}
}