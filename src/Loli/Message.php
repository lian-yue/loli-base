<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-21 09:17:20
/*	Updated: UTC 2015-02-16 09:24:24
/*
/* ************************************************************************** */
namespace Loli;
class Message extends ErrorException{
	protected $code;
	protected $message;
	protected $data;
	protected $args;
	public function __construct($message = [], $data = [], $severity = E_USER_WARNING, $file = __FILE__, $line = __LINE__, \Exception $previous = null) {
		$message = $message ? (array) $message : [500];

		$this->data = (array) $data;
		$this->args = $message;
		reset($this->args);
		unset($this->args[key($this->args)]);

		$code = reset($message);
		$message = $message[0];
		parent::__construct($message, $code, $severity, $file, $line, $previous);
	}

	public function getData() {
		return $this->data;
	}

	public function getArgs() {
		return $this->args;
	}
}