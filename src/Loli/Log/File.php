<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-25 08:56:01
/*	Updated: UTC 2015-02-27 13:01:29
/*
/* ************************************************************************** */
namespace Loli\Log;
class_exists('Loli\Log\Base') || exit;
class File extends Base{
	protected $path = './$date/$level-$time.log';

	public function write($message, $level = self::LEVEL_ACCESS) {

		// 过滤
		if (!$this->isRecord($level)) {
			return false;
		}
		// 进度
		$message = $this->getProgress($message, $level);

		// 时间
		$datetime = explode(' ', gmdate('Y-m-d H-i'));

		// 路径
		$path = strtr($this->path, ['$date' => $datetime[0], '$time' => $datetime[1], '$level' => $this->getLevelName($level)]);

		// 自动创建目录
		is_dir($dir = dirname($path)) || mkdir($dir, 0755, true);

		// 写入日志
		error_log('[' . $this->formatDate() . ']' . $this->getLevelName($level) . "\n" . $message . "\n\n", 3, $path);

		return true;
	}
}