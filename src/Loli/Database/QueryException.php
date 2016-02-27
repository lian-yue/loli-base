<?php
namespace Loli\Database;

class QueryException extends \RuntimeException {
	protected $state = '42000';
	protected $message;
	protected $query;
	public function __construct($query, $message, $state = '', $code = 0, Exception $previous = NULL) {
		$message = is_array($message) || is_object($message) ? json_encode($message) : $message;
		$this->query = is_array($query) || is_object($query) ? json_encode($query) : $query;
		$this->state = $state && $state !== '00000' ? $state : $this->state;
		parent::__construct($message, $code, $previous);
	}

	public function getState() {
		return $this->state;
	}

	public function getQuery() {
		return $this->query;
	}
}
