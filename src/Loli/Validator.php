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
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
		],*/
	];

	protected $attributes = [
		'name',
		'required',
		'minlength',
		'maxlength',
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

	public function make($data, array $rules = [], $merge = false, $message = NULL) {
		if ($data instanceof ServerRequestInterface) {
			$data = array_merge($data->getQueryParams(), ($parsedbody = $data->getParsedBody()) ? to_array($parsedbody) : [], $data->getUploadedFiles());
		}
		if (is_scalar($data)) {
			throw new \InvalidArgumentException('Verification data is scalar');
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
				if (is_array($input['value'])) {
 					$value = to_array($value);
				} elseif (is_string($input['value'])) {
 					$value = to_string($value);
				} else {
 					settype($value, gettype($input['value']));
 				}
			 }

			// 类型检查
			$empty = $value === '' || $value === false || $value === [] || ($value instanceof UploadedFileInterface && $value->getError() === UPLOAD_ERR_NO_FILE);
			$method = lcfirst(studly($input['type'])) . 'Type';
			if (!method_exists($this, $method)) {
				throw new \BadFunctionCallException(static::class .'::' .$method . '() Validator form type does not exist');
			}
			$error = $this->$method($value, $empty, $input, $data, $message);
			if ($error instanceof Message) {
				$message = $error;
				continue;
			} elseif ($error) {
				$message = new Message(['message' => $error, 'title' => $input['title'], 'name' => $input['name'], 'rule' => $input['type']], 50, $message);
				continue;
			}



			// 属性
			$empty = $value === '' || $value === false || $value === [] || ($value instanceof UploadedFileInterface && $value->getError() === UPLOAD_ERR_NO_FILE);
			foreach($this->attributes as $attribute) {
				if (!isset($input[$attribute]) || $input[$attribute] === false) {
					continue;
				}
				$method = lcfirst(studly($attribute)) . 'Attribute';
				if (!method_exists($this, $method)) {
					throw new \BadFunctionCallException(static::class .'::' .$method . '() Validator form attribute does not exist');
				}
				$attributeValue = $input[$attribute];

				$error = $this->$method($value, $attributeValue, $empty, $input, $data, $message);
				if ($error instanceof Message) {
					$message = $error;
					break;
				} elseif ($error) {
					$message = new Message(['message' => $error, 'title' => $input['title'], 'name' => $input['name'], 'rule' => $attributeValue], 50, $message);
					break;
				}
			}
		}

		if ($message) {
			throw $message;
		}
		return $data;
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
			return 'validator';
		}
	}

	protected function urlType(&$value, $empty) {
		if ($empty) {
			$value = '';
			return;
		}
		$value = to_string($value);
		if ($error = $this->textType($value)) {
			return $error;
		}
		if (!preg_match('/^[a-z]+[a-z0-9_-]*[a-z0-9]*\:\/\/\w+/i', $value)) {
			return 'validator';
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
			return 'validator';
		}
	}

	protected function telType(&$value, $empty) {
		if ($empty) {
			$value = '';
			return;
		}
		$value = preg_replace('/\-+/', '-', preg_replace('/\s+/', ' ', trim($value)));
		if (!preg_match('/^(\+?\d+ ?)?(\(\d+\))?\d+([- ]?\d+){0,3}$/i', $value)) {
			return 'validator';
		}
		if (strlen($value) > 24) {
			return 'validator';
		}
	}

	protected function colorType(&$value, $empty) {
		if ($empty) {
			$value = '';
			return;
		}
		$value = strtolower(trim($value));
		if (!preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value)) {
			return 'validator';
		}
	}

	protected function yearType(&$value, $empty) {
		if ($empty) {
			$value = '';
			return;
		}
		if (!preg_match('/^\d{4}$/', $value)) {
			return 'validator';
		}
	}

	protected function monthType(&$value, $empty) {
		if ($empty) {
			$value = '';
			return;
		}
		if (!preg_match('/^\d{4}\-(?:0\d|1[0-2])$/', $value)) {
			return 'validator';
		}
	}

	protected function weekType(&$value, $empty) {
		if ($empty) {
			$value = '';
			return;
		}

		$value = strtoupper(trim($value));
		if (!preg_match('/^\d{4}\-W(?:0\d|1[0-2])$/', $value)) {
			return 'validator';
		}
	}
	protected function dateType(&$value, $empty) {
		if ($empty) {
			$value = '';
			return;
		}

		$value = trim($value);
		if (!preg_match('/^\d{4}\-(\d{2})\-(\d{2})$/', $value)) {
			return 'validator';
		}

		try {
			$datetime = new DateTime($date);
		} catch (\Exception $e) {
			return 'validator';
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
			return 'validator';
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
			return 'validator';
		}
		$value = $datetime->format('Y-m-d h:i:s');
	}

	protected function radioType(&$value) {
		if (!is_scalar($value) || !isset($input['option'][$value])) {
			return 'validator';
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
				return 'validator';
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
		if (!is_array($value)) {
			$value = $empty ? [] : [$value];
		}
		foreach($value as $key => $uploadedFile) {
			if (!$uploadedFile instanceof UploadedFileInterface) {
				return 'validator';
			}
			if ($uploadedFile->getError() === 4) {
				unset($value[$key]);
			}
		}


		if (empty($input['multiple'])) {
			if (!$value) {
				$value = '';
				return;
			}
			if (count($value) !== 1) {
				return new Message(['message' => 'validator_max_count', 'title' => $input['title'], 'name' => $input['name'], 'rule' => 1], 50, $message);
			}
			$value = reset($value);
			if ($value->getError() !== UPLOAD_ERR_OK) {
				return new Message(['message' => 'validator_file_' . $value->getError(), 'title' => $input['title'], 'name' => $input['name'], 'rule' => $value->getError()], 50, $message);
			}
			return;
		}


		if ($empty) {
			$value = [];
			return;
		}

		foreach($value as $uploadedFile) {
			if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
				return new Message(['message' => 'validator_file_' . $uploadedFile->getError(), 'title' => $input['title'], 'name' => $input['name'], 'rule' => $uploadedFile->getError()], 50, $message);
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
				return new Message(['message' => 'validator_again', 'title' => empty($this->rules[$again]['title']) ? $input['title'] : $this->rules[$again]['title'], 'name' => $input['name'], 'rule' => $attributeValue], 50, $message);
			}
			unset($data[$attributeValue]);
		}
	}

	protected function requiredAttribute(&$value, $attributeValue, $empty) {
		if ($empty) {
			return 'validator_required';
		}
	}

	protected function minLengthAttribute(&$value, $attributeValue, $empty) {
		if ($empty) {
			return;
		}

		if ($value instanceof UploadedFileInterface) {
			if ($value->getSize() < parse_size($attributeValue)) {
				return 'validator_min_size';
			}
			return;
		}

		if (is_array($value) && reset($value) instanceof UploadedFileInterface) {
			$size = parse_size($attributeValue);
			foreach ($value as $uploadedFile) {
				if ($uploadedFile->getSize() < $size) {
					return 'validator_min_size';
				}
			}
			return;
		}


		if (is_scalar($value) && mb_strlen($value) < $attributeValue) {
			return 'validator_min_length';
		}
	}

	protected function maxLengthAttribute(&$value, $attributeValue, $empty) {
		if ($empty) {
			return;
		}
		if ($value instanceof UploadedFileInterface) {
			if ($value->getSize() > parse_size($attributeValue)) {
				return 'validator_max_size';
			}
			return;
		}

		if (is_array($value) && reset($value) instanceof UploadedFileInterface) {
			$size = parse_size($attributeValue);
			foreach ($value as $uploadedFile) {
				if ($uploadedFile->getSize() > $size) {
					return 'validator_max_size';
				}
			}
			return;
		}


		if (is_scalar($value) && mb_strlen($value) > $attributeValue) {
			return 'validator_max_length';
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

		if ($value instanceof UploadedFileInterface) {
			if ($value->getSize() > parse_size($attributeValue)) {
				return 'validator_max_size';
			}
			return;
		}

		if (is_array($value) && reset($value) instanceof UploadedFileInterface) {
			$size = parse_size($attributeValue);
			foreach ($value as $uploadedFile) {
				if ($uploadedFile->getSize() > $size) {
					return 'validator_max_size';
				}
			}
			return;
		}

		if (is_scalar($value) && mb_strlen($value) > $attributeValue) {
			return 'validator_max_length';
		}
	}

	protected function minAttribute(&$value, $attributeValue, $empty) {
		if ($empty) {
			return;
		}
		if (is_array($value)) {
			if (count($value) < $attributeValue) {
				return reset($value) instanceof UploadedFileInterface ? 'validator_min_count' : 'validator_min_count';
			}
			return;
		}

		if ($value instanceof UploadedFileInterface && $attribute > 1) {
			return 'validator_min_count';
		}

		if ($value < $attributeValue) {
			return 'validator_min_numeric';
		}
	}

	protected function maxAttribute(&$value, $attributeValue, $empty) {
		if ($empty) {
			return;
		}

		if (is_array($value)) {
			if (count($value) > $attributeValue) {
				return reset($value) instanceof UploadedFileInterface ? 'validator_max_count' : 'validator_max_count';
			}
			return;
		}

		if ($value instanceof UploadedFileInterface && $attribute < 1) {
			return 'validator_max_count';
		}

		if ($value > $attributeValue) {
			return 'validator_max_numeric';
		}
	}

	protected function stepAttribute(&$value, $attributeValue, $empty) {
		if ($empty) {
			return;
		}
		if ($value instanceof UploadedFileInterface) {
			$count = 1;
			if ($count % $attributeValue) {
				return 'validator_step_count';
			}
			return;
		}

		if (is_array($value)) {
			if (count($value) % $attributeValue) {
				return reset($value) instanceof UploadedFileInterface ? 'validator_step_count' : 'validator_step_count';
			}
			return;
		}


		if ($value % $attributeValue) {
			return 'validator_step_numeric';
		}
	}

	protected function optionAttribute() {
	}


	protected function patternAttribute(&$value, $attributeValue, $empty) {
		if ($empty) {
			return;
		}

		if (is_scalar($value)) {
			$subjects = [$value];
		} else {
			$subjects = $value;
		}
		$pattern = '/'. str_replace('/', '\\/', $attributeValue) .'/';
		foreach ($subjects as $subject) {
			if (!preg_match($pattern,  $subject)) {
				return 'validator';
			}
		}
	}

	protected function acceptAttribute(&$value, $attributeValue, $empty, &$input) {
		if ($empty) {
			return;
		}
		if ($value instanceof UploadedFileInterface) {
			$uploadedFiles  = [$value];
		} else {
			$uploadedFiles = $value;
		}

		if (!is_array($uploadedFiles)) {
			return 'validator_accept';
		}

		$accept = array_map('trim', explode(',', strtolower($attributeValue)));

		$image = in_array('image/*', $accept, true);
		$audio = in_array('audio/*', $accept, true);
		$video = in_array('video/*', $accept, true);

		foreach($uploadedFiles as $uploadedFile) {
			if (!$uploadedFile instanceof UploadedFileInterface) {
				return 'validator_accept';
			}

			if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
				return new Message(['message' => 'validator_file_' . $uploadedFile->getError(), 'title' => $input['title'], 'name' => $input['name'], 'rule' => $uploadedFile->getError()], 50, $message);
			}

			if (in_array($file->getClientMediaType(), $accept, true)) {

			} elseif ($image && in_array($file->mime, ['image/png', 'image/jpeg', 'image/webp', 'image/bmp', 'image/gif', 'image/svg', 'image/svg+xml'], true)) {

			} elseif ($audio && explode('/', $file->mime)[0] === 'audio') {

			} elseif ($video && explode('/', $file->mime)[0] === 'video') {

			} else {
				if (count(array_filter([$image, $audio, $video])) === 1) {
					if ($image) {
						return 'validator_accept_image';
					}
					if ($audio) {
						return 'validator_accept_audio';
					}
					if ($video) {
						return 'validator_accept_video';
					}
				}
				return 'validator_accept';
			}
		}
		return;
	}


	protected function existsAttribute(&$value, $attributeValue, $empty, &$input) {
		// 不存在
		if (!$this->query($attributeValue, $input['name'], $value)) {
			return 'validator_exists';
		}
	}

	protected function uniqueAttribute(&$value, $attributeValue, $empty, &$input) {
		if ($this->query($attributeValue, $input['name'], $value)) {
			return 'validator_unique';
		}
	}




	protected function input($name, &$input) {
		if (!is_array($input) || ($input && is_int(key($input)))) {
			$input = ['value' => $input];
		}
		if (empty($input['name'])) {
			$input['name'] = $name;
		}

		$input['type'] = empty($input['type']) ? (empty($this->rules[$input['name']]['type']) ? 'text' : $this->rules[$input['name']]['type']) : $input['type'];

		if (isset($input['title']) || !isset($this->rules[$input['name']]['title'])) {
			$input['title'] = Language::translate(empty($input['title']) ? trim(ucfirst(strtolower(str_replace(['-', '_'], ' ', $input['name'])))) : $input['title'], $this->group);
		}

		if (!empty($input['errormessage'])) {
			$input['errormessage'] = Message::translate(['message' => $input['errormessage'], 'title' => $input['title'], 'name' => $input['name'], 'rule' => $input['type']]);
		}

		if (!empty($input['placeholder'])) {
			$input['placeholder'] = Language::translate($input['placeholder'], $this->group);
		}

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
				throw new \InvalidArgumentException('Validator unique value verification model class error');
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
