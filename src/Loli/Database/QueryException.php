<?php
namespace Loli\Database;

class QueryException extends \RuntimeException implements DatabaseException{
	protected $state = '42000';
	protected $message;
	protected $query;
	public function __construct($query, $message, $state = '', $code = 0, \Exception $previous = null) {
		if (!is_scalar($message)) {
			$message = json_encode($message);
		}

		$this->query = $query;

		if ($state && $state !== '00000') {
			$this->state = $state;
		}
		parent::__construct($message, $code, $previous);
	}

	public function getState() {
		return $this->state;
	}

	public function getQuery() {
		return $this->query;
	}
}
