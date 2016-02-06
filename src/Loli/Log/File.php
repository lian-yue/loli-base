<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-21 13:42:16
/*
/* ************************************************************************** */
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-25 08:56:01
/*	Updated: UTC 2015-03-23 10:11:33
/*
/* ************************************************************************** */
namespace Loli\Log;

class File extends Base{
	protected $path = './$date/$level-$time.log';

	public function write($message, $level = self::LEVEL_ACCESS) {
		// 不允许写入的
		if (!in_array($level, $this->writes, true)) {
			return false;
		}

		// 进度
		if (!$message = $this->getProgress($message, $level)) {
			return false;
		}

		// 时间
		$datetime = explode(' ', gmdate('Y-m-d H-i'));

		// 路径
		$path = strtr($this->path, ['$date' => $datetime[0], '$time' => $datetime[1], '$level' => $this->getLevelName($level)]);

		// 自动创建目录
		is_dir($dir = dirname($path)) || mkdir($dir, 0755, true);

		// 写入日志
		error_log('[' . $this->formatDate() . '] ' . $message . "\n", 3, $path);

		return true;
	}
}