<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-25 08:56:16
/*	Updated: UTC 2015-03-23 10:11:04
/*
/* ************************************************************************** */
namespace Loli\Log;
use Loli\Exception;
abstract class Base{

	// 信息 访问日志什么的
	const LEVEL_ACCESS = 0;

	// 通知
	const LEVEL_NOTICE = 1;

	// 警告
	const LEVEL_WARNING = 2;

	// 错误
	const LEVEL_ERROR = 3;

	// 警报 比如链接 mysql 什么的不可用
	const LEVEL_ALERT = 4;

	// DEBUG 日志
	const LEVEL_DEBUG = 9;

	// 日志
	protected $levels = [
		0 => 'access',
		1 => 'notice',
		2 => 'warning',
		3 => 'error',
		4 => 'alert',
		9 => 'debug',
	];

	// 允许写入的级别
	protected $writes = [0, 1, 2, 3, 4, 5 ,9];


	protected $dateFormat = 'c';

	// 进度
	protected $progress = [];

	// 实例化并传入参数
	public function __construct(array $args) {
		foreach ($args as $key => $value) {
			if ($value !== NULL && $key != 'levels' && isset($this->$key)) {
				$this->$key = $value;
			}
		}
	}

	public function __invoke() {
		return call_user_func_array([$this, 'write'], func_get_args());
	}

	public function __call($method, $args) {
		return isset($args[0]) && ($level = array_search($method, $this->levels, true)) !== false && $this->write($args[0], $level);
	}

	// 写入日志
	abstract public function write($message, $level = self::LEVEL_ACCESS);



	// 级别
	protected function getLevelName($level) {
		if (isset($this->levels[$level])) {
			return $this->levels[$level];
		}
		trigger_error('Error log level', E_USER_ERROR);
	}


	// 进度
	protected function getProgress($message, $level) {
		if (is_array($message)) {
			$message = var_export($message, true);
		} elseif (is_object($message) && !method_exists($message, '__toString')) {
			$message = json_encode($message);
		} else {
			$message = (string) $message;
		}
		$levelName = $this->getLevelName($level);
		foreach ($this->progress as $value) {
			$message = call_user_func($value, $message, $level, $levelName);
		}
		return $message;
	}

	// 格式化时间
	protected function formatDate() {
		return gmdate($this->dateFormat);
	}
}
