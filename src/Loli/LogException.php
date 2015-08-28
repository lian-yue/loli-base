<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-21 13:42:16
/*
/* ************************************************************************** */
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-25 13:37:57
/*	Updated: UTC 2015-03-23 10:12:19
/*
/* ************************************************************************** */
namespace Loli;
class_exists('Loli\Exception') || exit;
class LogException extends Exception{

	protected $level = 1;
	protected $log;

	public function __construct($message = '', $code = 0, $level = -1, Exception $previous = NULL) {
		parent::__construct($message, $code, $previous);
		if ($level > 0) {
			$this->level = $level;
		}
		if (!$this->log) {
			$this->log = $message;
		}
		Log::write($this->log, $this->level);
	}

	final public function getLevel() {
		return $this->level;
	}
}