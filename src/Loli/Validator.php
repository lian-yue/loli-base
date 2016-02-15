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
use Loli\HTTP\Files;
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
			'accept' => '',				// 允许的文件类型
			'disabled' => '',			// 禁用
			'readonly' => '',			// 禁用
			'errormessage' => '',		// 错误消息
			'errorcode' => '',			// 错误消息代码
		],*/
	];

	protected $attributes = [
		'name',
		'required',
		'minLength',
		'maxLength',
		'size',
		'min',
		'max',
		'step',
		'option',
		'pattern',
		'accept',
		'exists',
		'unique',
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

	public function rules($rules = [], $merge = false) {
		$_rules = [];
		$order = 0;
		foreach ($rules as $name => $input) {
			$this->input($name, $input);
			$_rules[$input['name']] = ['order' => $order] + $input;
			++$order;
		}
		$rules = $_rules;
		foreach ($this->rules as $name => $rule) {
			if (empty($rules[$name])) {
				if (!$merge) {
					$rules[$name] = ['order' => $order] + $rule;
					++$order;
				}
			} else {
				$rules[$name] = array_merge($rule, $rules[$name]);
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
		$rules = $this->rules($rules, $merge);

		if ($merge) {
			foreach ($rules as $input) {
				if (!isset($data[$input['name']])) {
					if (isset($rules[$input['name']]['value'])) {
						$data[$input['name']] = $rules[$input['name']]['value'];
					} else {
						unset($data[$input['name']]);
					}
				}
			}
		}

		foreach ($data as $key => $value) {
			if ($merge && !isset($rules[$key])) {
				unset($data[$key]);
				continue;
			}
			if ($value === NULL) {
				if (isset($rules[$key]['value'])) {
					$data[$key] = $rules[$key]['value'];
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

			 // 设置类型
			if (isset($input['value'])) {
				settype($value, gettype($input['value']));
			} elseif (isset($merge[$key]['value'])) {
				settype($value, gettype($merge[$key]['value']));
			}

			// 类型检查
			$empty = $value === '' || $value === false || $value === [] || ($value instanceof Files && $value->count() === 0);

			$method = studly($input['type']) . 'Type';
			if (!method_exists($this, $method)) {
				throw new Exception('Validator form type does not exist');
			}

			if (is_int($errorcode = $this->$method($value, $empty, $input, $data, $message))) {
				$message = new Message([$input['errorcode'] + $errorcode, $input['title'], $input['name'], $input['type']], Message::ERROR, $message);
				continue;
			} elseif ($errorcode instanceof Message) {
				$message = $errorcode;
				continue;
			}



			// 属性
			$empty = $value === '' || $value === false || $value === [] || ($value instanceof Files && $value->count() === 0);
			$continue = false;
			foreach($this->attributes as $attribute) {
				if (!isset($input[$attribute]) || $input[$attribute] === false) {
					continue;
				}
				$method = studly($attribute) . 'Attribute';
				if (!method_exists($this, $method)) {
					throw new Exception('Validator form attribute does not exist');
				}
				$attributeValue = $input[$attribute];
				if (is_int($errorcode = $this->$method($value, $attributeValue, $empty, $input, $data, $message))) {
					$message = new Message([$input['errorcode'] + $errorcode, $input['title'], $input['name'], $attributeValue], Message::ERROR, $message);
					break;
				} elseif ($errorcode instanceof Message) {
					$message = $errorcode;
					break;
				}
			}
		}

		if ($message) {
			throw $message;
		}
		return $this;
	}










	protected function textType(&$value) {
		if (is_string($value)) {
			$value = str_replace(["\r", "\n"], '', $value);
		}
	}
	protected function hiddenType(&$value) {
		return $this->textType($value);
	}
	protected function passwordType(&$value) {
		$value = to_string($value);
		return $this->textType($value);
	}

	protected function numberType(&$value) {
		$value = (int) $value;
	}
	protected function rangeType(&$value) {
		return $this->rangeType($value);
	}

	protected function emailType(&$value, $empty) {
		if ($empty) {
			$value = '';
			return;
		}
		if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
			return 0;
		}
	}

	protected function searchType(&$value) {
		if (is_scalar($value)) {
			$value = (string) $value;
		} elseif (is_array($value)) {
			$value = implode(' ', $value);
		} elseif (is_object($value) && method_exists($value, '__toString')) {
			$value = $value->__toString();
		} else {
			return 0;
		}
	}

	protected function telType(&$value, $empty) {
		if ($empty) {
			$value = '';
			return;
		}
		$value = preg_replace('/\-+/', '-', preg_replace('/\s+/', ' ', trim($value)));
		if (!preg_match('/^(\+?\d+ ?)?(\(\d+\))?\d+([- ]?\d+){0,3}$/i', $value)) {
			return 0;
		}
		if (strlen($value) > 24) {
			return 0;
		}
	}

	protected function colorType(&$value, $empty) {
		if ($empty) {
			$value = '';
			return;
		}
		$value = strtolower(trim($value));
		if (!preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value)) {
			return 0;
		}
	}

	protected function yearType(&$value, $empty) {
		if ($empty) {
			$value = '';
			return;
		}
		if (!preg_match('/^\d{4}$/', $value)) {
			return 0;
		}
	}

	protected function monthType(&$value, $empty) {
		if ($empty) {
			$value = '';
			return;
		}
		if (!preg_match('/^\d{4}\-(?:0\d|1[0-2])$/', $value)) {
			return 0;
		}
	}

	protected function weekType(&$value, $empty) {
		if ($empty) {
			$value = '';
			return;
		}

		$value = strtoupper(trim($value));
		if (!preg_match('/^\d{4}\-W(?:0\d|1[0-2])$/', $value)) {
			return 0;
		}
	}
	protected function dateType(&$value, $empty) {
		if ($empty) {
			$value = '';
			return;
		}

		$value = trim($value);
		if (!preg_match('/^\d{4}\-(\d{2})\-(\d{2})$/', $value)) {
			return 0;
		}

		try {
			$datetime = new DateTime($date);
		} catch (\Exception $e) {
			return 0;
		}
		$value = $datetime->format('Y-m-d');
	}

	protected function datetimeType(&$value, $empty) {
		if ($empty) {
			$value = '';
			return;
		}
		$value = trim($value);

		try {
			$datetime = new DateTime($date);
		} catch (\Exception $e) {
			return 0;
		}
		$value = $datetime->format('Y-m-d h:i:s');
	}

	protected function datetimeLocalType(&$value, $empty) {
		if ($empty) {
			$value = '';
			return;
		}
		$value = trim($value);

		try {
			$datetime = new DateTime($date);
		} catch (\Exception $e) {
			return 0;
		}
		$value = $datetime->format('Y-m-d h:i:s');
	}

	protected function radioType(&$value) {
		if (!is_scalar($value) || !isset($input['option'][$value])) {
			return 0;
		}
	}


	protected function checkboxType(&$value, $empty) {
		if ($empty) {
			$value = [];
			return;
		}
		$value = array_values((array) $value);
		$value = array_intersect($value, $input['option']);
	}




	protected function selectType(&$value, $empty, &$input) {
 		if (empty($input['multiple'])) {
			if ($empty) {
				if (!is_scalar($value)) {
					$value = '';
				}
				if (isset($input['value'])) {
					settype($value, gettype($input['value']));
				}
				return;
			}

			if (!is_scalar($value)) {
				$value = reset($value);
			}
			if (!is_scalar($value) || !isset($input['option'][$value])) {
				return 0;
			}
		}

		if ($empty) {
			$value = [];
			return;
		}
		$value = array_unique(array_values((array) $value));
		$value = array_intersect($value, array_keys($input['option']));
	}

	protected function fileType(&$value, $empty, &$input, &$data, $message) {
		if ($empty) {
			if (!$value instanceof Files) {
				$value = new Files();
			}
			return;
		}
		if (!$value instanceof Files) {
			return 0;
		}
		if (empty($input['multiple']) && $value->count() > 1) {
			return new Message([$input['errorcode'] + 9, $input['title']. $input['name'], 1], Message::ERROR, $message);
		}
		foreach($value as $file) {
			if ($file->error !== UPLOAD_ERR_OK) {
				return new Message([$input['errorcode'] + 40 + $file->error, $input['title'], $input['name'], $file->error], Message::ERROR, $message);
			}
		}
	}


	protected function textareaType(&$value) {
		$value = (string) $value;
	}

	protected function nameAttribute(&$value, $attributeValue, $empty, &$input, &$data, $message) {
		// 重复字段错误
		if (substr($attributeValue, -6) === '_again') {
			if (isset($data[$again = substr($attributeValue, 0, -6)]) && $value != $data[$again]) {
				return new Message([$input['errorcode'] + 20, empty($this->rules[$again]['title']) ? $input['title'] : $this->rules[$again]['title'], $input['name'], $attributeValue], Message::ERROR, $message);
			}
			unset($data[$attributeValue]);
		}
	}

	protected function requiredAttribute(&$value, $attributeValue, $empty) {
		if ($empty) {
			return 1;
		}
	}

	protected function minLengthAttribute(&$value, $attributeValue) {
		if ($empty) {
			return;
		}

		if ($value instanceof Files) {
			$size = parse_size($attributeValue);
			foreach ($value as $file) {
				if ($file->size < $size) {
					return 10;
				}
			}
			return;
		}
		if (is_scalar($value) && mb_strlen($value) < $attributeValue) {
			return 7;
		}
	}

	protected function maxLengthAttribute(&$value, $attributeValue) {
		if ($empty) {
			return;
		}
		if ($value instanceof Files) {
			$size = parse_size($attributeValue);
			foreach ($value as $file) {
				if ($file->size > $size) {
					return 11;
				}
			}
			return;
		}
		if (is_scalar($value) && mb_strlen($value) > $attributeValue) {
			return 7;
		}
	}

	protected function sizeAttribute(&$value, $attributeValue, $empty, &$input) {
		if ($empty) {
			return;
		}

		if ($input['type'] === 'text') {
			$value = mb_substr($value, 0, $attributeValue);
			return;
		}

		if ($value instanceof Files) {
			$size = parse_size($attributeValue);
			foreach ($value as $file) {
				if ($file->size > $size) {
					return 11;
				}
			}
			return;
		}

		if (is_scalar($value) && mb_strlen($value) > $attributeValue) {
			return 7;
		}
	}

	protected function minAttribute(&$value, $attributeValue, $empty) {
		if ($empty) {
			return;
		}
		if ($value instanceof Files) {
			if ($value->count() < $attributeValue) {
				return 7;
			}
			return;
		}
		if ($value < $attributeValue) {
			return 4;
		}
		if ($value < $attributeValue) {
			return 4;
		}
	}

	protected function maxAttribute(&$value, $attributeValue, $empty) {
		if ($empty) {
			return;
		}
		if ($value instanceof Files) {
			if ($value->count() > $attributeValue) {
				return 8;
			}
			return;
		}
		if ($value > $attributeValue) {
			return 5;
		}
		if ($value > $attributeValue) {
			return 5;
		}
	}

	protected function stepAttribute(&$value, $attributeValue, $empty) {
		if ($empty) {
			return;
		}
		if ($value instanceof Files) {
			if ($value->count() % $attributeValue) {
				return 21;
			}
			return;
		}

		if (is_array($value)) {
			if (count($value) % $attributeValue) {
				return 21;
			}
			return;
		}

		if ($value % $attributeValue) {
			return 21;
		}
		if ($value % $attributeValue) {
			return 21;
		}
	}

	protected function optionAttribute() {
	}


	protected function patternAttribute(&$value, $attributeValue, $empty) {
		if (is_scalar($value)) {
			$subjects = [$value];
		} else {
			$subjects = $value;
		}
		$pattern = '/'. str_replace('/', '\\/', $attributeValue) .'/';
		foreach ($subjects as $subject) {
			if (preg_match($pattern,  $subject)) {
				return 0;
			}
		}
	}

	protected function acceptAttribute(&$value, $attributeValue, $empty, &$input) {
		if (!$value instanceof Files) {
			return 0;
		}
		$accept = array_map('trim', explode(',', strtolower($attributeValue)));

		$image = in_array('image/*', $accept, true);
		$audio = in_array('audio/*', $accept, true);
		$video = in_array('video/*', $accept, true);

		foreach($value as $file) {
			if ($file->error !== UPLOAD_ERR_OK) {
				return new Message([$input['errorcode'] + 40 + $file->error, $input['title'], $input['name'], $file->error], Message::ERROR, $message);
			}
			if (in_array($file->mime, $accept, true)) {

			} elseif ($image && in_array($file->mime, ['image/png', 'image/jpeg', 'image/webp', 'image/bmp', 'image/gif', 'image/svg', 'image/svg+xml'], true)) {

			} elseif ($audio && explode('/', $file->mime)[0] === 'audio') {

			} elseif ($video && explode('/', $file->mime)[0] === 'video') {

			} else {
				if (count(array_filter([$image, $audio, $video])) === 1) {
					if ($image) {
						return 31;
					}
					if ($audio) {
						return 32;
					}
					if ($video) {
						return 33;
					}
				}
				return 30;
			}
		}
		return;
	}


	protected function existsAttribute(&$value, $attributeValue, $empty, &$input) {
		// 不存在
		if (!$this->query($attributeValue, $input['name'], $value)) {
			return 2;
		}
	}

	protected function uniqueAttribute(&$value, $attributeValue, $empty, &$input) {
		if ($this->query($attributeValue, $input['name'], $value)) {
			return 3;
		}
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
