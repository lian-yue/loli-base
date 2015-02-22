<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-16 13:21:40
/*	Updated: UTC 2015-02-22 04:17:51
/*
/* ************************************************************************** */
namespace Loli\HMVC;
use Loli\Exception, Loli\Lang;
class Error extends Exception{

	protected $code;
	protected $message;
	protected $data;
	protected $args;
	public function __construct($message = [], array $data = [], Error $previous = null) {
		$message = $message ? (array) $message : [500];

		// data
		$this->data = $data;

		// args
		$this->args = $message;
		reset($this->args);
		unset($this->args[key($this->args)]);

		// code
		$code = reset($message);

		// message
		$message = Lang::get($message, ['message', 'default']);


		parent::__construct($message, $code, $previous);
	}

	public function getData() {
		return $this->data;
	}

	public function getArgs() {
		return $this->args;
	}

	public function getTitle() {
		return [Lang::get(['Error Messages', ['message', 'default']])];
	}

	public function hasCode($codes = []) {
		if (!$codes) {
			return true;
		}
		$codes = (array) $codes;
		$message = $this;
		do {
			if (in_array($message->getCode(), $codes)) {
				return true;
			}
		} while ($message = $message->getPrevious());
		return false;
	}

	public function results() {
		$message = $this;
		do {
			$results[$message->getCode()] = $message;
		} while ($message = $message->getPrevious());
		return $results;
	}

	public function __toString() {
		$string = '<div id="errors">';
		foreach ($this->results() as $message) {
			$string .= '<div class="error" code="'. htmlspecialchars($message->getCode(), ENT_QUOTES) .'">'. $message->getMessage() .'</div>';
		}
		$string .= '</div>';
		return $string;
	}
}