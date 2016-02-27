<?php
namespace Loli;

use Loli\Crypt\Code;

class Token {

	private $token;

	private $isNew = false;

	public function __construct($token, $isNew = false) {
		try {
			$this->set($token, $isNew);
		} catch (\Exception $e) {
			$this->set($this->create(), true);
		}

	}

	public function create() {
		$token = uniqid();
		$token .= Code::random(16 - strlen($token), '0123456789qwertyuiopasdfghjklzxcvbnm');
		$token .= Code::key(__CLASS__ . $token, 16);
		return $token;
	}

	public function isNew() {
		return $this->isNew;
	}

	public function get($isKey = false) {
		return $isKey ? $this->token : substr($this->token, 0, 16);
	}

	public function set($token, $isNew = false) {
		if (!is_string($token) || strlen($token) !== 32 || Code::key(__CLASS__ . substr($token, 0, 16), 16) !== substr($token, 16)) {
			throw new \InvalidArgumentException('Access token is invalid');
		}
		$this->token = $token;
		$this->isNew = $isNew;
		return true;
	}
	public function __toString(){
		return $this->get();
	}
}
