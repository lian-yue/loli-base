<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-10 12:48:56
/*	Updated: UTC 2015-02-27 14:21:45
/*
/* ************************************************************************** */
namespace Loli\DB;
use Loli\Exception as Exception_;
class_exists('Loli\Exception') || exit;
class Exception extends Exception_{
	protected $query;
	public function __construct($query, $message, $code = 0, $file = __FILE__ , $line = __LINE__) {
		$this->query = $query;
		parent::__construct($message, $code);
		$this->file = $file;
		$this->line = $line;
	}

	public function getQuery() {
		return $this->query;
	}
}