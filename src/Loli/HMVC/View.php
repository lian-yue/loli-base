<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-07 19:45:05
/*	Updated: UTC 2015-02-22 03:35:43
/*
/* ************************************************************************** */
namespace Loli\HMVC;
use Loli\Model;
trait_exists('Loli\Model', true) || exit;
class View{
	use Model;
	private $_data = [];

	private $_file;

	private $_dir = './';

	public function __construct($file, $data = []) {
		$this->_file = $file;
		$this->_data = $data;
	}

	public function getData() {
		return $this->_data;
	}

	public function addData($data) {
		$this->data += $data;
		return true;
	}

	public function setData($data) {
		$this->data = $data + $this->_data;
		return true;
	}

	public function load($_file, $_once = true) {
		foreach ((array)$_file as $v) {
			if ($_is = is_file($_f = $this->_dir .'/' . $v . '.php')) {
				break;
			}
		}
		if (empty($_is)) {
			return false;
		}
		foreach ($this->_data as $k => $v) {
			if (!$k || $v === null || !is_string($k) || $k{0} == '_' || $k == 'this' || $k == 'GLOBALS') {
				unset($this->_data[$k]);
			}
		}
		unset($k, $v);
		extract($this->_data);
		if ($_once) {
			require_once $_f;
		} else {
			require $_f;
		}
		return true;
	}

	public function __toString() {
		$this->load($this->_file);
		return  '';
	}
}