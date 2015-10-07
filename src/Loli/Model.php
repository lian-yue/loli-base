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
			'minlength' => 1,			// 最小字符串长度
			'maxlength' => 1,			// 最大字符串长度
			'errormessage' => '',		// 错误消息
		],*/
	];

	protected $tokens = [];

	protected $logins = [];

	protected $viewModel = false;

	protected $errorMessage = NULL;


	public function __construct(Route &$route, $viewModel = false) {
		$this->route = &$route;
		$this->viewModel = $viewModel;
		if ($this->viewModel) {
			$method = strtolower($this->model[1]);

			// rbac 权限判断
			if (!isset($this->rbacs[$this->model[1]]) || !empty($this->rbacs[$this->model[1]])) {
				$this->RBACMessage();
			}

			// token 判断
			in_array($method, array_map('strtolower', $this->tokens), true) && $this->tokenMessage();


			// 登录判断
			$login = 0;
			foreach ($this->logins as $key => $value) {
				if ($method === strtolower($key)) {
					$login = $value;
					break;
				}
			}
			$login && $this->loginMessage($login >= 1 ? 1 : -1);
		}
	}

	public function __get($name) {
		return $this->route->$name;
	}

	public function __set($name, $value) {
		$this->route->$name = $value;
	}

	public function __isset($name) {
		return isset($this->route->$name);
	}

	public function __unset($name) {
		unset($this->route->$name);
		return true;
	}



	public function __call($name, array $args) {
		throw new Message(404, Message::ERROR);
	}

	protected function RBACMessage() {
		if (empty($this->route->table['RBAC.Permission'])) {
			return true;
		}
		if ($this->route->table['RBAC.Permission']->has($this->route->model[0], $this->route->model[1])) {
			return true;
		}

		$this->route->response->setStatus(403);
		throw new Message([90, 'RBAC'], Message::ERROR);
	}

	protected function tokenMessage() {
		if (!empty($_SERVER['LOLI']['MODEL']['token'])) {
			return call_user_func($_SERVER['LOLI']['MODEL']['token'], $this->route);
		}
		if ($this->route->request->getToken(false, false) !== $this->route->request->getParam('_token', '')) {
			$this->route->response->setStatus(403);
			throw new Message([90, 'Token'], Message::ERROR);
		}
	}

	protected function loginMessage($is) {
		if (!empty($_SERVER['LOLI']['MODEL']['login'])) {
			return call_user_func($_SERVER['LOLI']['MODEL']['login'], $this->route, $is);
		}
		if (empty($this->route->table['RBAC.Token'])) {
			return true;
		}
		$userID = $this->route->table['RBAC.Token']->userID();
		if (($userID && $is === 1) || (!$userID && $is === -1)) {
			return true;
		}
		if ($is === 1) {
			throw new Message(91, Message::ERROR, [], '/user/login?redirect=' . urlencode($this->route->request->getURL()), new Message([90, 'Login'], Message::ERROR));
		}
		throw new Message(92, Message::NOTICE, ['userID' => $userID], true, 0);
	}




	protected function getForm($name = []) {
		$form = [];
		foreach ($this->form as $input) {
			if ($name && !in_array($input['name'], (array)$name, true)) {
				continue;
			}
			$input['title'] = $this->localize->translate(empty($input['title']) ? $input['name'] : $input['title']);
			if (!empty($input['errormessage'])) {
				$input['errormessage'] = $this->route->localize->translate([$input['errormessage'],  $input['name'], $input['title']], ['message']);
			}
			if (!empty($input['errormessage'])) {
				$input['placeholder'] = $this->route->localize->translate($input['errormessage']);
			}
			if (!empty($input['option'])) {
				foreach ($input['option'] as &$value) {
					$value = $this->route->localize->translate($value);
				}
			}
			$form[$input['name']] = $input;
		}
		return $form;
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
			$input['title'] = isset($input['title']) ? $this->route->localize->translate($input['title']) : $input['name'];


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
				$message = new Message([$input['errormessage'] + 1, $input['title'], $input['name']], Message::ERROR, $message);
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
				$message = new Message([$input['errormessage'], $input['title'], $input['name']], Message::ERROR, $message);
				continue;
			}

			// 最大 最小长度
			if ((!empty($input['maxlength']) && mb_strlen($value) > $input['maxlength']) || (!empty($input['minlength']) && mb_strlen($value) > $input['minlength'])) {
				$message = new Message([$input['errormessage'] + 2, $input['title'], $input['name']], Message::ERROR, $message);
				continue;
			}

			// 范围
			if ((isset($input['min']) && $input['min'] > $value) || (isset($input['max']) && $input['max'] < $value) || (!empty($input['step']) && ($value % $input['step']) !== 0)) {
				$message = new Message([$input['errormessage'] + 3, $input['title'], $input['name']], Message::ERROR, $message);
				continue;
			}

			// 规定数据
			if (isset($input['option']) && count((array)$value) === count(array_intersect((array)$value, array_keys($input['option'])))) {
				$message = new Message([$input['errormessage'] + 3, $input['title'], $input['name']], Message::ERROR, $message);
				continue;
			}

			// 正则
			if (isset($input['pattern']) && preg_match('/'. str_replace('/', '\\/', $input['pattern']) .'/', $value)) {
				$message = new Message([$input['errormessage'], $input['title'], $input['name']], Message::ERROR, $message);
				continue;
			}
		}
		if ($message) {
			throw new $message;
		}
	}

	protected function view($files , array $data = [], $cache = false) {
		return new View($files, $data, $cache);
	}

	protected function model($name) {
		$className = 'Model\\' . strtr($name, '/.', '\\\\');
		return new $className($this->route);
	}
}