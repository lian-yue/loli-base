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
use Iterator, Loli\Exception, Loli\Lang;
class Error extends Exception implements Iterator{

	protected $code;
	protected $message;
	protected $data;
	protected $args;
	protected $results = [];
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


		$message = $this;
		do {
			$this->results[$message->getCode()] = $message;
		} while ($message = $message->getPrevious());
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


	public function __invoke() {
		return $this->__toString();
	}

	public function __toString() {
		$string = '<div id="errors">';
		foreach ($this as $message) {
			$string .= '<div class="error" code="'. htmlspecialchars($message->getCode(), ENT_QUOTES) .'">'. $message->getMessage() .'</div>';
		}
		$string .= '</div>';
		return $string;
	}


	public function rewind() {
		reset($this->results);
	}

	public function current() {
		return current($this->results);
	}

	public function key() {
		return key($this->results);
	}

	public function next() {
		return next($this->var);
	}

	public function valid() {
		$key = key($this->var);
		return ($key !== null && $key !== false);
	}
}