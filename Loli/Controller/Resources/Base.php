<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-01-15 13:01:52
/*	Updated: UTC 2015-01-09 12:03:35
/*
/* ************************************************************************** */
namespace Loli\Controller\Resources;
abstract class Base{

	// 全部注册信息
	protected $args = [];

	// 默认参数
	protected $default = [];

	// 不显示的attr
	protected $attr = [];


	public function __invoke() {
		return call_user_func_array([$this, 'run'], func_get_args());
	}


	// 添加优先级 添加 异步添加 多次执行


	/**
	*	添加资源
	*
	*	1 参数 key
	*	2 参数 文件
	*	3 参数 附加 args
	*
	*	返回值 bool
	**/
	public function add($key, $value, $args = []) {
		return empty($this->args[$key]) ? call_user_func([$this, 'set'], $key, $value, $args) : false;
	}


	/**
	*	写入资源
	*
	*	1 参数 key
	*	2 参数 文件
	*	3 参数 附加 args
	*
	*	返回值 bool
	**/
	public function set($key, $value, $args = []) {
		$value && ($args[''] = $value);
		$this->args[$key] = $args + (empty($this->args[$key]) ? [] : $this->args[$key]) + $this->default;
		return true;
	}

	/**
	*	移除资源
	*
	*	1 参数 key
	*
	*	返回值 bool
	**/
	public function remove($key) {
		if (empty($this->args[$key])) {
			return false;
		}
		unset($this->args[$key]);
		return true;
	}

	public function open($key) {
		return isset($this->args[$key]) && ($this->args[$key]['off'] = false);
	}

	public function off($key) {
		return isset($this->args[$key]) && ($this->args[$key]['off'] = true);
	}

	public function run() {
		empty($this->default['priority']) || prioritysort($this->args);
		foreach ($this->args as $k => $v) {
			$this->_for($k);
		}
		return true;
	}

	abstract protected function call($value, $args, $key);

	protected function attr($v) {
		ksort($v);
		$attr = '';
		foreach ( $v as $kk => $vv ) {
			if ( !in_array($kk, ['', 'priority', 'if', 'run','off', 'call', 'parent']) && !in_array($kk, $this->attr) ) {
				$attr .= ' ' . $kk .'="'. strtr($vv, ['"' => '&quot;', '\'' => '&#039;', '<' => '&lt;', '>' => '&gt;']) .'"';
			}
		}
		return $attr;
	}

	private function _for($k) {
		if ( empty($this->args[$k]) || !empty($this->args[$k]['run']) || !empty($this->args[$k]['off']) ) {
			return false;
		}
		$v = &$this->args[$k];

		empty($v['parent']) || $this->_for($v['parent']);
		$v['off'] = $v['run'] = true;
		$v['call'] =  !empty($v['call']) || is_object($v['']) || is_array($v['']) || (strpos($v[''], '.') === false && strpos($v[''], '/') === false && strpos($v[''], '?') === false);
		return $this->call($v[''], $v, $k);
	}
}