<?php
namespace Loli\Log;
use Exception;
use Psr\Log\AbstractLogger as PsrAbstractLogger;
use Loli\Traits\ConstructConfigTrait;
abstract class AbstractLogger extends PsrAbstractLogger{

	protected $filters = [];

	protected function interpolate($message, array $context = []) {
		if ($message instanceof Exception) {
			$message = $message->getMessage();
		} elseif (is_array($message)) {
			$message = json_encode($message);
		} elseif (is_object($message) && method_exists($message, '__toString')) {
			$message = $message->__toString();
		} else {
			$message = (string) $message;
		}

		$replace = [];
		foreach ($context as $key => $value) {
			$replace['{' . $key . '}'] = is_scalar($value) ? $value : json_encode($value);
		}
		return strtr($message, $replace);
	}
}
