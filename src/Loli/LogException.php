<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-25 13:37:57
/*	Updated: UTC 2015-03-22 08:12:36
/*
/* ************************************************************************** */
namespace Loli;
class LogException extends Exception{

	protected $level = 1;
	protected $log;

	public function __construct($message = '', $code = 0, $level = -1, Exception $previous = NULL) {
		if ($level > 0) {
			$this->level = $level;
		}
		parent::__construct($message, $code, $previous);
		if (!$this->log) {
			$this->log = $message;
		}
		Log::write($this->log, $level);
	}

	final public function getLevel() {
		return $this->level;
	}
}