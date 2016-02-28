<?php
namespace Loli\Log;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;
class MemoryLogger extends AbstractLogger{

	private $logs = [];

	public function fetchAll() {
		return $this->logs;
	}

	public function clear() {
		$this->logs = [];
		return $this;
	}

	public function log($level, $message, array $context = []) {
		if (!in_array($level, [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR, LogLevel::WARNING, LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG], true)) {
			throw new InvalidArgumentException( __METHOD__ . '('.$level.') Log error level is unknown');
		}

		if ($this->filters && in_array($level, $this->filters, true)) {
			return;
		}
		$this->logs[] = [gmdate('c'), $message, $context];
	}
}
