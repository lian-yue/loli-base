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
/*	Updated: UTC 2015-03-21 12:03:53
/*
/* ************************************************************************** */
namespace Loli;
class ErrorException extends \Exception{

	protected $severity = 1;

	public function __construct($message = '', $code = 0, $severity = NULL, Exception $previous = NULL) {
		if ($severity > 0) {
			$this->previous = $severity;
		}
		parent::__construct($message, $code, $previous);
	}

	final public function getSeverity() {
		return $this->severity;
	}
}
