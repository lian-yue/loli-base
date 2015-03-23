<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-10 12:48:56
/*	Updated: UTC 2015-03-22 08:01:00
/*
/* ************************************************************************** */
namespace Loli\DB;
use Loli\LogException;
class_exists('Loli\LogException') || exit;
class Exception extends LogException{
	protected $state = '42000';
	protected $message;
	protected $query;
	protected $level = 3;
	public function __construct($query, $message, $state = '', $code = 0, Exception $previous = NULL) {
		$message = is_array($message) || is_object($message) ? var_export($message, true) : $message;
		$this->query = is_array($query) || is_object($query) ? var_export($query, true) : $query;
		$this->state = $state && $state !== '00000' ? $state : $this->state;
		$this->log = $message .  "\n" . $this->query;
		parent::__construct($message, $code, $previous);
	}
	public function getState() {
		return $this->state;
	}

	public function getQuery() {
		return $this->query;
	}

}