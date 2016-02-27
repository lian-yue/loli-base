<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2016-02-01 09:32:01
/*
/* ************************************************************************** */
namespace Loli;
class Assets extends URL{
	public function __construct($url = false, $version = false) {
		parent::__construct($url);
		$this->__set('version', $version ? $version : '1.0.0');
		if (!$this->host) {
			if (!empty($_SERVER['LOLI']['assets']['host'])) {
				$this->host = $_SERVER['LOLI']['assets']['host'];
			}
			if (!empty($_SERVER['LOLI']['assets']['base'])) {
				$this->path = trim($_SERVER['LOLI']['assets']['base'], '/') . '/';
			}
		}

		if (strpos($this->path, '{version}') && strpos($this->host, '{version}')) {
			$this->query('version', '{version}');
		}
		if (strpos($this->path, '{language}') && strpos($this->host, '{language}')) {
			$this->query('language', '{language}');
		}
	}

	public function __toString() {
		return strtr(parent::__toString(), ['{version}' => $this->version, '%7Bversion%7D' => $this->version, '{language}' => Language::name(), '%7Blanguage%7D' => Language::name()]);
	}
}
