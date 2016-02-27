<?php
namespace Loli\Log;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;
class FileLogger extends AbstractLogger {
	protected $path = './{group}/{date}/{level}-{time}.log';

	protected $group = '';

	public function log($level, $message, array $context = []) {

		if (!in_array($level, [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR, LogLevel::WARNING, LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG], true)) {
			throw new InvalidArgumentException( __METHOD__ . '('.$level.') Log error level is unknown');
		}

		if ($this->filters && in_array($level, $this->filters, true)) {
			return;
		}

		// 时间
		$datetime = explode(' ', gmdate('Y-m-d H-i'));

		// 路径
		$path = strtr($this->path, ['{date}' => $datetime[0], '{time}' => $datetime[1], '{level}' => $level, '{group}' => $group]);

		// 自动创建目录
		is_dir($dir = dirname($path)) || mkdir($dir, 0755, true);

		if ($context) {
			$json = "\n" . json_encode($context);
		} else {
			$json = '';
		}

		// 写入日志
		error_log('[' . gmdate('c') . '] ' . $this->interpolate($message, $context) . $json . "\n", 3, $path);
	}
}
