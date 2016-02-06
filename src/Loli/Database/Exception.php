<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2016-01-27 10:22:32
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
/*	Created: UTC 2015-02-10 12:48:56
/*	Updated: UTC 2015-05-04 14:45:18
/*
/* ************************************************************************** */
namespace Loli\Database;
use Loli\LogException;

class Exception extends LogException{
	protected $state = '42000';
	protected $message;
	protected $query;
	protected $level = 3;
	public function __construct($query, $message, $state = '', $code = 0, Exception $previous = NULL) {
		$message = is_array($message) || is_object($message) ? json_encode($message) : $message;
		$this->query = is_array($query) || is_object($query) ? json_encode($query) : $query;
		$this->state = $state && $state !== '00000' ? $state : $this->state;
		$this->log = $message .  '		' . $this->query;
		parent::__construct($message, $code, $previous);
	}
	public function getState() {
		return $this->state;
	}

	public function getQuery() {
		return $this->query;
	}
}