<?php
namespace Loli\Traits;

trait ConstructConfigTrait {
	public function __construct(array $args = []) {
		foreach($args as $name => $value) {
			if ($value !== null && $name !== 'names' && property_exists($this, $name) && (!isset($this->names) || !in_array($name, $this->names, true))) {
				$this->$name = $value;
			}
		}
	}
}
