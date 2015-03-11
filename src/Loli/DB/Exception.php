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
/*	Updated: UTC 2015-03-09 15:53:43
/*
/* ************************************************************************** */
namespace Loli\DB;
use Loli\ErrorException;
class_exists('Loli\ErrorException') || exit;
class Exception extends ErrorException{
	protected $state = '42000';
	protected $message;
	protected $query;
	protected $severity = 2;
	public function __construct($query, $message, $state = '', $code = 0, Exception $previous = NULL) {
		$message = is_array($message) || is_object($message) ? var_export($message, true) : $message;
		$this->query = $query;
		$this->state = $state && $state !== '00000' ? $state : $this->state;
		parent::__construct($message, $code, $previous);
	}
	public function getState(){
		return $this->state;
	}

	public function getQuery(){
	}

}