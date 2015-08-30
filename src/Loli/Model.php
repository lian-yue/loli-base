<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-22 06:52:42
/*
/* ************************************************************************** */
namespace Loli;
use Loli\DB\Cursor, Loli\DB\Param, Loli\DB\Iterator;
class_exists('Loli\Route') || exit;
class Model extends Cursor{
	protected $route;

	protected $callback = true;

	protected $options = [
		'limit' => 10,
	];

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
		$this->DB = $route->DB;
	}

	public function getForm() {
		return $this->form;
	}

	public function flush() {
		$this->fields = $this->querys = $this->values = $this->documents = $this->options = $this->unions = $this->data = [];
		$this->builder && $this->builder->flush();
		$this->increment = 0;
		return $this;
	}

	protected function write(Iterator $value = NULL) {
		if ($this->values) {
			$this->documents[] = $this->values;
			$this->values = [];
		}

		if (!$value) {
			// 添加默认值
			$defaults = [];
			foreach ($this->form as $input) {
				if (isset($input['value'])) {
					$defaults[$input['name']] = $input['value'];
				}
			}
			foreach ($this->documents as &$document) {
				$addDefaults = $defaults;
				foreach ($document as $name => &$value) {
					if (!$value instanceof Param) {
						$value = new Param(['name' => $name, 'value' => $value]);
					}
					if (is_string($value->name)) {
						unset($addDefaults[$value->name]);
					}
				}
				unset($value);
				foreach ($addDefaults as $name => $value) {
					$document[] = new Param(['name' => $name, 'value' => $value]);
				}
			}
			unset($document);
		}

		// 表单验证
		$form = [];
		foreach ($this->form as $input) {
			if (empty($input['errormessage'])) {
				$input['errormessage'] = 1000;
			}
			$form[$input['name']] = $input;
			if (in_array($input['type'], ['checkbox', 'radio', 'select'], true)) {
				$input['option'] = empty($input['option']) ? [] : (array) $input['option'];
			}
			$input['title'] = isset($input['title']) ? $this->route->localize($input['title']) : $input['name'];
		}

		foreach ($this->documents as &$document) {
			$message = NULL;
			foreach ($document as $name => &$value) {
				if (!$value instanceof Param) {
					$value = new Param(['name' => $name, 'value' => $value]);
				}
				if (!is_string($value->name)) {
					continue;
				}
				if (empty($form[$value->name])) {
					continue;
				}
				if (is_object($value->value)) {
					continue;
				}
				if ($value->value === NULL) {
					continue;
				}

				// 设置数据类型
				$input = $form[$value->name];
				if (isset($input['value'])) {
					settype($value->value, gettype($input['value']));
				}

				// 空检查
				$empty = empty($value->value) && $value->value !== '0' && $value->value !== 0;
				if (!empty($input['required']) && $empty) {
					$message = new Message([$input['errormessage'] + 1, $input['name'], $input['title']], Message::ERROR, $message);
					continue;
				}


				// 表单类型检查
				$continue = false;
				switch ($input['type']) {
					case 'text':
					case 'password':
						$value->value = str_replace(["\r", "\n"], '', (string) $value->value);
						break;
					case 'textarea':
						$value->value = (string) $value->value;
						break;
					case 'email':
						$continue = !$empty && !filter_var($value->value, FILTER_VALIDATE_EMAIL);
						break;
					case 'number':
					case 'range':
						$value->value = (int) $value->value;
						break;
					case 'search':
						$value->value = is_array($value->value) ? implode(' ', $value->value) : (string) $value->value;
						break;
					case 'url':
						$continue = !$empty && !preg_match('/^[a-z]+\:\/\/\w+/i', $value->value);
					case 'tel':
						$continue = !$empty && !preg_match('/^\+?[0-9]+(\s[0-9]+)*$/i', $value->value);
					case 'color':
						$continue = !$empty && !preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value->value = strtolower($value->value));
						break;
					case 'year':
						$continue = !$empty && preg_match('/^\d{4}$/', $value->value);
						break;
					case 'month':
						$continue = !$empty && preg_match('/^\d{4}\-(?:0\d|1[0-2])$/', $value->value);
						break;
					case 'week':
						$continue = !$empty && preg_match('/^\d{4}\-W(?:0\d|1[0-2])$/', $value->value = strtoupper($value->value));
						break;
					case 'date':
						if (!$empty) {
							if (preg_match('/^\d{4}\-(\d{2})\-(\d{2})$/', $value->value) && ($time = strtotime($value->value))) {
								$value->value = date('Y-m-d', $value->value);
							} else {
								$continue = true;
							}
						}
						break;
					case 'datetime':
						if (!$empty) {
							if ($time = strtotime($value->value)) {
								$value->value = date('Y-m-d h:i:s', $value->value);
							} else {
								$continue = true;
							}
						}
						break;
					case 'datetime-local':
						break;
					case 'radio':
						if (!is_scalar($value->value) || !isset($input['option'][$value->value])) {
							$continue = true;
						}
						break;
					case 'checkbox':
						$value->value = array_values((array) $value->value);
						$value->value = array_intersect($value->value, $input['option']);
						if (!$value->value) {
							$continue = true;
						}
						break;
					case 'select':
						if (empty($input['multiple'])) {
							if (!is_scalar($value->value)) {
								$value->value = reset($value->value);
							}
							$continue = !is_scalar($value->value) || !isset($input['option'][$value->value]);
						} elseif (!$$value->value = array_intersect(array_values((array) $value->value), $input['option'])) {
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
				if (!empty($input['maxlength']) && mb_strlen($value->value) > $input['maxlength']) {
					$message = new Message([$input['errormessage'] + 2, $input['name'], $input['title']], Message::ERROR, $message);
					continue;
				}

				// 范围
				if ((isset($input['min']) && $input['min'] > $value->value) || (isset($input['max']) && $input['max'] < $value->value) || (!empty($input['step']) && ($value->value % $input['step']) !== 0)) {
					$message = new Message([$input['errormessage'] + 3, $input['name'], $input['title']], Message::ERROR, $message);
					continue;
				}

				// 规定数据
				if (isset($input['option']) && count((array)$value->value) === count(array_intersect((array)$value->value, array_keys($input['option'])))) {
					$message = new Message([$input['errormessage'] + 4, $input['name'], $input['title']], Message::ERROR, $message);
					continue;
				}

				// 正则
				if (isset($input['pattern']) && preg_match('/'. str_replace('/', '\\/', $input['pattern']) .'/', $value->value)) {
					$message = new Message([$input['errormessage'], $input['name'], $input['title']], Message::ERROR, $message);
					continue;
				}
			}
			if ($message) {
				throw new $message;
			}
		}
	}
}