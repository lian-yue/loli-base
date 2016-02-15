<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2016-02-13 12:26:56
/*
/* ************************************************************************** */
namespace Loli\HTTP;

use Loli\Storage;
use Loli\MimeType;
use Loli\ArrayObject;

class Files extends ArrayObject{

	public function __set($name, $value) {
		if ($value instanceof Files) {
			return $this->__set(NULL, $value);
		}

		if (!is_array($value) && !isset($value['tmp_name'])) {
			throw new Exception("Set file error", 400);
		}

		if (is_array($value['tmp_name'])) {
			foreach($value['tmp_name'] as $key => $tmp_name) {
				$file = [
					'tmp_name' => $tmp_name,
					'name' => !empty($value['name']) && isset($value['name'][$key]) && is_array($value['name'][$key]) ? $value['name'][$key] : NULL,
					'type' => !empty($value['type']) && isset($value['type'][$key]) && is_array($value['type'][$key]) ? $value['type'][$key] : NULL,
					'error' => !empty($value['error']) && isset($value['error'][$key]) && is_array($value['error'][$key]) ? $value['error'][$key] : NULL,
					'size' => !empty($value['size']) && isset($value['size'][$key]) && is_array($value['size'][$key]) ? $value['size'][$key] : NULL,
				];
				if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
					$this->__set(NULL, new File($file));
				}
			}
			return true;
		}
		if (!isset($valie['error']) || $valie['error'] !== UPLOAD_ERR_NO_FILE) {
			return $this->__set(NULL, new File($value));
		}
		return;
	}

	public function __get($name) {
		if (is_int($name)) {
			return parent::__get($name);
		}
		if (($key = $this->key()) !== NULL) {
			return parent::__get($key)->__get($name);
		}
		return NULL;
	}

	public function __call($name, $args) {
		if (($key = $this->key()) !== NULL) {
			return parent::__get($key)->__call($name, $args);
		}
		throw new Exception('files.'. $name .'()', 'The results is empty');
	}

	public function __toString() {
		$names = [];
		foreach ($this as $file) {
			$names[] = $file->name;
		}
		return implode(',', $names);
	}
}
