<?php
namespace Loli\Query;
class Option extends Param{
	public function __construct($name, $value, array $options = []) {
		parent::__construct(['name' => $name, 'value' => $value] + $options);
	}
}