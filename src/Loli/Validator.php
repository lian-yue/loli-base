<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2016-02-03 04:06:05
/*
/* ************************************************************************** */
namespace Loli;
class Validator {
	// 模块验证表格
	protected $rules = [
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
			'minlength' => 1,			// 最小字符串长度
			'maxlength' => 1,			// 最大字符串长度
			'unique' => '',				// 必须不存在
			'exists' => '',				// 必须存在
			'disabled' => '',			// 禁用
			'readonly' => '',			// 禁用
			'errormessage' => '',		// 错误消息
			'errorcode' => '',			// 错误消息代码
		],*/
	];

	protected $model;

	protected $group;

	public function __construct(array $rules, $group = ['default']) {
		$this->group = (array) $group;

		foreach ($rules as $name => $input) {
			$this->input($name, $input);
			if (empty($this->rules[$input['name']])) {
				$this->rules[$input['name']] = [];
			}
			$this->rules[$input['name']] += $input;
		}
	}

	public function rules(...$args) {
		if (!$args) {
			return $this->rules;
		}
		$rules = [];
		foreach ($args as $arg) {
			foreach ((array)$arg as $key => $value) {
				if (!empty($this->rules[$value])) {
					$rules[$value] = $this->rules[$value];
				}
			}
		}
		return $rules;
	}

	public function model($model) {
		$this->model = is_object($model) ? get_class($model) : $model;
		return $this;
	}

	public function make(&$data, array $rules = [], $merge = false, $message = NULL) {
		if (is_scalar($data)) {
			throw new Message(500, new Message([1, 'Verification data is scalar'], Message::ERROR));
		}
		$_rules = [];
		foreach ($rules as $name => $input) {
			$this->input($name, $input);
			$_rules[$input['name']] = $input;
		}
		$rules = array_merge($this->rules, $_rules);
		if ($merge) {
			foreach ($_rules as $value) {
				if (!isset($data[$value['name']])) {
					if (isset($rules[$value['name']]['value'])) {
						$data[$value['name']] = $rules[$value['name']]['value'];
					} else {
						unset($data[$value['name']]);
					}
				}
			}
		}
		foreach ($data as $key => $value) {
			if ($merge && !isset($_rules[$key])) {
				unset($data[$key]);
				continue;
			}
			if ($value === NULL) {
				if (isset($rules[$key]['value'])) {
					$data[$value] = $rules[$key]['value'];
				} else {
					unset($data[$value]);
				}
			}
		}

		unset($value);

		foreach ($rules as $input) {
			if (!isset($data[$input['name']]) || !empty($input['readonly']) || !empty($input['disabled'])) {
				unset($data[$input['name']]);
				continue;
			}

			$value = &$data[$input['name']];


			if (isset($input['value'])) {
				settype($value, gettype($input['value']));
			} elseif (isset($merge[$key]['value'])) {
				settype($value, gettype($merge[$key]['value']));
			}

			// 空检查
			$empty = empty($value) && $value !== '0' && $value !== 0;
			if (!empty($input['required']) && $empty) {
				$message = new Message([$input['errorcode'] + 1, $input['title'], $input['name']], Message::ERROR, $message);
				continue;
			}

			if (in_array($input['type'], ['text', 'email', 'search', 'password', 'tel'], true)) {
				$value = to_string($value);
				// 长度截断
				if (!empty($input['size'])) {
					$value = mb_substr($value, 0, $input['size']);
				}
			}


			// 表单类型检查
			$continue = false;
			switch ($input['type']) {
				case 'text':
				case 'password':
				case 'hidden':
					$value = str_replace(["\r", "\n"], '', $value);
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
					$value = is_array($value) ? implode(' ', $value) : $value;
					break;
				case 'url':
					$continue = !$empty && !preg_match('/^[a-z]+\:\/\/\w+/i', $value = trim($value));
					break;
				case 'tel':
				case 'phone':
					$continue = !$empty && !preg_match('/^(\+?\d+ ?)?(\(\d+\))?\d+([- ]?\d+){0,3}$/i', $value = preg_replace('/\-+/', '-', preg_replace('/\s+/', ' ', trim($value)))) && strlen($value) <= 24;
					break;
				case 'color':
					$continue = !$empty && !preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value = strtolower(trim($value)));
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
					} elseif (!$value = array_intersect(array_values((array) $value), $input['option'])) {
						$continue = true;
					}
					break;
				case 'file':


			}


			// 表单类型错误
			if ($continue) {
				$message = new Message([$input['errorcode'], $input['title'], $input['name']], Message::ERROR, $message);
				continue;
			}

			// 重复字段错误
			if (substr($input['name'], -6) === '_again') {
				if (isset($data[$again = substr($input['name'], 0, -6)]) && $value != $data[$again]) {
					$message = new Message([$input['errorcode'] + 9, empty($this->rules[$again]['title']) ? $input['title'] : $this->rules[$again]['title'] , $input['name']], Message::ERROR, $message);
				}
				unset($data[$input['name']]);
				continue;
			}

			// 最小长度
			if (!empty($input['minlength']) && mb_strlen($value) < $input['minlength']) {
				$message = new Message([$input['errorcode'] + 2, $input['title'], $input['name']], Message::ERROR, $message);
				continue;
			}

			// 最大长度
			if (!empty($input['maxlength']) && mb_strlen($value) > $input['maxlength']) {
				$message = new Message([$input['errorcode'] + 3, $input['title'], $input['name']], Message::ERROR, $message);
				continue;
			}

			// 范围小
			if (isset($input['min']) && $input['min'] > $value) {
				$message = new Message([$input['errorcode'] + 6, $input['title'], $input['name']], Message::ERROR, $message);
				continue;
			}
			// 范围大
			if (isset($input['max']) && $input['max'] < $value) {
				$message = new Message([$input['errorcode'] + 7, $input['title'], $input['name']], Message::ERROR, $message);
				continue;
			}

			// 合法
			 if (!empty($input['step']) && ($value % $input['step']) !== 0) {
			 	$message = new Message([$input['errorcode'], $input['title'], $input['name']], Message::ERROR, $message);
				continue;
			 }

			// 规定数据
			if (isset($input['option']) && count((array) $value) !== count(array_intersect((array)$value, array_keys($input['option'])))) {
				$message = new Message([$input['errorcode'] + 7, $input['title'], $input['name']], Message::ERROR, $message);
				continue;
			}

			// 正则
			if (isset($input['pattern']) && preg_match('/'. str_replace('/', '\\/', $input['pattern']) .'/', $value)) {
				$message = new Message([$input['errorcode'], $input['title'], $input['name']], Message::ERROR, $message);
				continue;
			}

			// 不存在
			if (isset($input['exists']) && !$this->query($input['exists'], $input['name'], $value)) {
				$message = new Message([$input['errorcode'] + 4, $input['title'], $input['name']], Message::ERROR, $message);
				continue;
			}

			// 存在
			if (isset($input['unique']) && $this->query($input['unique'], $input['name'], $value)) {
				$message = new Message([$input['errorcode'] + 5, $input['title'], $input['name']], Message::ERROR, $message);
				continue;
			}
		}

		if ($message) {
			throw $message;
		}
		return $this;
	}

	protected function input($name, &$input) {
		if (!is_array($input) || ($input && is_int(key($input)))) {
			$input = ['value' => $input];
		}
		if (empty($input['name'])) {
			$input['name'] = $name;
		}

		if (isset($input['title']) || !isset($this->rules[$input['name']]['title'])) {
			$input['title'] = Language::translate(empty($input['title']) ? trim(ucfirst(strtolower(str_replace(['-', '_'], ' ', $input['name'])))) : $input['title']);
		}

		if (isset($input['errorcode']) || !isset($this->rules[$input['name']]['errorcode'])) {
			if (!empty($input['errormessage']) && is_int($input['errormessage'])) {
				$input['errorcode'] = $input['errormessage'];
			} else {
				$input['errorcode'] = 1000;
			}
		}

		if (!empty($input['errormessage'])) {
			if (is_int($input['errormessage'])) {
				$input['errormessage'] = Message::translate([$input['errormessage'], $input['title'], $input['name']], $this->group);
			} else {
				$input['errormessage'] = Language::translate([$input['errormessage'], $input['title'], $input['name']], $this->group);
			}
		}

		if (!empty($input['placeholder'])) {
			$input['placeholder'] = Language::translate($input['placeholder'], $this->group);
		}
		$input['type'] = empty($input['type']) ? (empty($this->rules[$input['name']]['type']) ? 'text' : $this->rules[$input['name']]['type']) : $input['type'];

		if (in_array($input['type'], ['checkbox', 'radio', 'select'], true) && (isset($input['option']) || !isset($this->rules[$input['name']]['option']))) {
			$input['option'] = empty($input['option']) ? [] : (array) $input['option'];
		}

		if (!empty($input['option'])) {
			foreach($input['option'] as &$value) {
				$value = Language::translate($value, $this->group);
			}
		}
	}

	protected function query($rule, $column, $value) {
		$rule = explode('|', $rule, 3);
		if (empty($rule[0]) || $rule[0] === 'self' || $rule[0] === 'this') {
			$class = $this->model;
			if (!$class) {
				throw new Message(500, new Message([1, 'Validator unique value verification model class error'], Message::ERROR));
			}
		} else {
			$class = 'App\\' . strtr($rule[0], '/.@', '\\\\\\');
		}
		if (!empty($input['unique'][1])) {
			$column = $input['unique'][1];
		}
		$model = $class::query($column, $value, '=');
		if (!empty($input['unique'][2]) && ($json = json_decode($input['unique'][2]))) {
			foreach ($json as $column => $value) {
				if (is_object($value) && property_exists($value, 'value')) {
					$model->query($column, $value->value, isset($value->compare) ? $value->compare : '');
				} else {
					$model->query($column, $value);
				}
			}
		}
		return $model->selectRow();
	}
}