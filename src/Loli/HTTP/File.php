<?php

namespace Loli\HTTP;

use Loli\Storage;
use Loli\MimeType;
use Loli\ArrayObject;

class File extends ArrayObject{


	public function __construct($args) {


		$file = [
			'tmp_name' => $args['tmp_name'],
			'name' => !empty($args['name']) ? pathinfo($args['name'], PATHINFO_BASENAME) : 'unknown',
			'size' => isset($args['size']) && $args['size'] > 0 ? (int) $args['size'] : 0,
			'type' => !empty($args['type']) ? strtolower($args['type']) : 'application/octet-stream',
			'error' => isset($args['error']) ? (int) $args['error'] : UPLOAD_ERR_OK,
			'extension' => '',
			'filename' => '',
			'dirname' => '',
			'basename' => '',
			'encoding' => '',
		];


		foreach (pathinfo($file['name']) as $key => $value) {
			$file[$key] = $value;
		}
		$file['extension'] = strtolower($file['extension']);

		if ($file['error'] !== UPLOAD_ERR_OK) {
			// 有错误的
		} elseif (!is_file($file['tmp_name'])) {
			// 文件不存在
			$file['error'] = UPLOAD_ERR_NO_FILE;
		} elseif (!$mime = Storage::mime($file['tmp_name'])) {
			// mime 错误
			$file['error'] = UPLOAD_ERR_EXTENSION;
		}

		if ($file['error'] === UPLOAD_ERR_OK) {
			$file['size'] = filesize($file['tmp_name']);
			$file['type'] = $mime['type'];
			$file['encoding'] = $mime['encoding'];
		} else {
			if ($file['extension'] &&($type = MimeType::get($file['extension']))) {
				$file['type'] = $type;
			}
		}

		$this->write($file);
	}


	public function __toString() {
		return $this->__get('name');
	}

	public function jsonSerialize() {
		return $this->__get('name');
	}










}
