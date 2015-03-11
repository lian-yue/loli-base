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
/*	Updated: UTC 2015-02-25 04:41:24
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
	protected $_ID;
	protected $_class;

	protected $_dir = './';

	public function __construct($files, $data = []) {
		$files = (array) $files;
		$this->_dir = empty($_SERVER['LOLI']['VIEW']['dir']) ? './' : $_SERVER['LOLI']['VIEW']['dir'];
		$this->_files = $files;
		$this->_data = $data;
		$this->_ID = strtr(reset($files), '/', '-');
		$this->_class = strtr(reset($files), '/', ' ');
	}

	public function getID() {
		return $this->_ID;
	}
	public function getClass() {
		return $this->_class;
	}

	public function getData() {
		return $this->_data;
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
			if (!$key || $value === NULL || !is_string($key) || $key{0} == '_' || $key == 'this' || $key == 'GLOBALS') {
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