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
/*	Updated: UTC 2014-12-31 07:49:33
/*
/* ************************************************************************** */
namespace Model;
use Loli\Model;
class Session extends Model{
	public function __invoke() {
		return call_user_func_array([$this, 'get'], func_get_args());
	}
	public function get($key) {
		return $this->Cache->get($this->Token() . $key, __CLASS__);
	}
	public function add($key, $value, $ttl = 3600) {
		return $this->Cache->add($value, $this->Token() . $key, __CLASS__, $ttl);
	}
	public function set($key, $value, $ttl = 3600) {
		return $this->Cache->set($value, $this->Token() . $key, __CLASS__, $ttl);
	}
	public function delete($key) {
		return $this->Cache->delete($key, __CLASS__);
	}
}