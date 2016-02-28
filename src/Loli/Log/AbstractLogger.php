<?php
namespace Loli\Log;
use Exception;
use Psr\Log\AbstractLogger as PsrAbstractLogger;
use Loli\Traits\ConstructConfigTrait;
abstract class AbstractLogger extends PsrAbstractLogger{
	use ConstructConfigTrait;
	protected $filters = [];

	protected function interpolate($message, array $context = []) {
		if ($message instanceof Exception) {
			$context['exception'] = $message;
			$message = $message->getMessage();
		} elseif (is_array($message)) {
			$message = json_encode($message);
		} elseif (is_object($message) && method_exists($message, '__toString')) {
			$message = $message->__toString();
		} else {
			$message = (string) $message;
		}

		if (!empty($context['exception']) && $context['exception'] instanceof \Exception) {
			$exception = $context['exception'];
			unset($context['exception']);
			$context['exception_file'] = $exception->getFile();
			$context['exception_line'] = $exception->getLine();
			$context['exception'] = $exception->getTraceAsString();
		}
		$array = $replace = [];
		foreach ($context as $key => $value) {
			$value = is_scalar($value) ? $value : json_encode($value);;
			$replace['{' . $key . '}'] = $value;

			$array[] = $key;
			$array[] = $value;
		}
		if ($array) {
			$string =  "\n" . implode("\n", $array);
		} else {
			$string = '';
		}
		return strtr($message, $replace) . $string . "\n";
	}
}
