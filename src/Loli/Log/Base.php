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
/*	Updated: UTC 2015-02-25 14:55:39
/*
/* ************************************************************************** */
namespace Loli\Log;
use Loli\Exception;
abstract class Base{

	// 信息 访问日志什么的
	const LEVEL_ACCESS = 2;

	// 通知
	const LEVEL_NOTICE = 4;

	// 警告
	const LEVEL_WARNING = 8;

	// 错误
	const LEVEL_ERROR = 16;

	// 警报 比如链接 mysql 什么的不可用
	const LEVEL_ALERT = 32;


	// 查询日志
	const LEVEL_QUERY = 32818;

	// debug
	const LEVEL_DEBUG = 65636;

	// 日志
	protected $levels = [
		2 => 'access',
		4 => 'notice',
		8 => 'warning',
		16 => 'error',
		32 => 'alert',
		32818 => 'query',
		65636 => 'debug',
	];

	protected $dateFormat = 'c';


	// 不用记录的 log
	protected $record = 98516;

	// 实例化并传入参数
	abstract function __construct(array $args);

	public function __invoke() {
		return call_user_func_array([$this, 'write'], func_get_args());
	}

	// 写入日志
	abstract public function write($message, $level = self::LEVEL_ACCESS);


	public function access($message) {
		return $this->write($message, self::LEVEL_ACCESS);
	}

	public function notice($message) {
		return $this->write($message, self::LEVEL_NOTICE);
	}

	public function warning($message) {
		return $this->write($message, self::LEVEL_WARNING);
	}

	public function error($message) {
		return $this->write($message, self::LEVEL_ERROR);
	}

	public function alert($message) {
		return $this->write($message, self::LEVEL_ALERT);
	}

	public function query($message) {
		return $this->write($message, self::LEVEL_QUERY);
	}

	public function debug($message) {
		return $this->write($message, self::LEVEL_DEBUG);
	}

	protected function isRecord($level) {
		if ($this->filter == -1) {
			return true;
		}
		if ($this->filter === false) {
			return true;
		}
		if ($this->filter != 1 && !($this->filter & $filter)) {
			return true;
		}
		return false;
	}

	// 级别
	protected function getLevelName($level) {
		if (isset($this->levels[$level])) {
			return $this->levels[$level];
		}
		trigger_error('Level "'.$level.'" is not defined', E_USER_ERROR);
	}

	// 格式化时间
	protected function formatDate() {
		return gmtdate($this->dateFormat);
	}

    // 格式化日志
	protected function formatMessage($message) {
		if (is_array($message)) {
			return var_export($message, true);
		} elseif (is_object($message) && !method_exists($message, '__toString')) {
			return json_encode($message);
		}
		return (string) $message;
	}
}
