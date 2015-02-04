<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-12-10 05:18:47
/*	Updated: UTC 2015-01-22 08:32:05
/*
/* ************************************************************************** */
namespace Model;
use Loli\Model, Loli\Token;
trait_exists('Loli\Model', true) || exit;
class Session{
	use Model;
	public function __invoke() {
		return call_user_func_array([$this, 'get'], func_get_args());
	}
	public function get($key) {
		return $this->Cache->get(Token::get() . $key, __CLASS__);
	}
	public function add($key, $value, $ttl = 3600) {
		return $this->Cache->add($value, Token::get() . $key, __CLASS__, $ttl);
	}
	public function set($key, $value, $ttl = 3600) {
		return $this->Cache->set($value, Token::get() . $key, __CLASS__, $ttl);
	}
	public function delete($key) {
		return $this->Cache->delete($key, __CLASS__);
	}
}

return new Session;