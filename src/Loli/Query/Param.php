<?php
namespace Loli\Query;
class Param{
	public function __construct(array $options = []) {
		foreach ($options as $k => $v) {
			$this->$k = $v;
		}
	}
	public function __get($name) {
		return NULL;
	}
}