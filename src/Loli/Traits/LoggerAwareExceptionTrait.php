<?php
namespace Loli\Traits;

use Psr\Log\LogLevel;
use Psr\Log\LoggerAwareTrait;
trait LoggerAwareExceptionTrait{
	use LoggerAwareTrait;
	public function throwLog(\Exception $exception, $level = LogLevel::ERROR, array $context = []) {
		$this->logger && $this->logger->log($level, $exception->getMessage(), ['exception' => $exception] + $context);
		throw $exception;
	}
}
