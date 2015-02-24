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
/*	Updated: UTC 2015-02-24 04:41:33
/*
/* ************************************************************************** */
namespace Loli\HMVC;
use Loli\Model;
trait_exists('Loli\Model', true) || exit;
class View{
	use Model;
	protected $_data = [];

	protected $_files;
	protected $_file;
	protected $_once;

	protected $_dir = './';

	public function __construct($files, $data = []) {
		$this->_dir = empty($_SERVER['LOLI']['VIEW']['dir']) ? './' : $_SERVER['LOLI']['VIEW']['dir'];
		$this->_files = $files;
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

	public function load($files, $once = true) {
		foreach ((array)$files as $file) {
			if ($is = is_file($this->_file = $this->_dir .'/' . strtolower($file) . '.php')) {
				break;
			}
		}
		if (empty($is)) {
			return false;
		}
		$this->_once = $once;
		unset($files, $file, $is, $once);
		foreach ($this->_data as $key => $value) {
			if (!$key || $value === null || !is_string($key) || $key{0} == '_' || $key == 'this' || $key == 'GLOBALS') {
				unset($this->_data[$key]);
			}
		}
		unset($key, $value);


		extract($this->_data);
		if ($this->_once) {
			require_once $this->_file;
		} else {
			require $this->_file;
		}
		return true;
	}

	public function __invoke() {
		$this->load($this->_files);
	}
}