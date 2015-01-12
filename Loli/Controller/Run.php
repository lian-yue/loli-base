<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-12-31 10:37:27
/*	Updated: UTC 2015-01-12 17:07:54
/*
/* ************************************************************************** */
namespace Loli\Controller;
use Loli\Ajax;
class Run{

	private $_is = false;

	protected function run() {
		if ($this->_is) {
			return false;
		}
		$this->_is = true;
		$this->load('include.php');
		$class = get_class($this);
		$doKey = strtr($class, '\\', '/');
		do_array_call($doKey, [&$this]);
		$rewrite = [];
		$doKey .= '.';
		$a = $this;
		if ($a->keys) {
			$keys = $a->keys;
			while($keys) {
				$key = array_shift($keys);
				$a->has($key) && $a->err(404);
				$value = $a->get($key);
				if (empty($value['rewrite'])) {
					$rewrite = array_merge($rewrite, $value['rewrite']);
				}
				if (!empty($value['file'])) {
					require $value['file'];
				}
				if (!empty($value['class'])) {
					$class .= '\\' .  $value['class'];
					$a = new $class;
					$doKey .=  $value['class'] . '/';
					do_array_call(rtrim($doKey, '/'), [&$a]);
					$this->data = array_merge($this->data, $a->data);
				}
				if (!empty($value['method'])) {
					$method = $value['method'];
					$keys && $a->err(404);
				}
			}
		} else {
			$path = $a->path();
			$slash = $path != '/' && substr($path, -1, 1) == '/' ? '/' : '';
			$before = '';
			$after = trim($after, '/');
			while($after !== false) {
				$arr = explode('/', $after, 2);
				if (!isset($arr[1]) && $arr[0] === '') {
					break;
				}
				$current = $arr[0]
				$after = isset($arr[1]) ? $arr[1] : '';
				($key = $a->key($before, $current, $after . $slash)) || $a->err(404);
				$before .= $before === '' ? $arr[0] : '/' . $arr[0];
				$value = $a->get($key);
				if (empty($value['rewrite'])) {
					$rewrite = array_merge($rewrite, $value['rewrite']);
				}
				if (!empty($value['file'])) {
					require $value['file'];
				}
				if (!empty($value['class'])) {
					$class .= '\\' .  $value['class'];
					$a = new $class;
					$doKey .=  $value['class'] . '/';
					do_array_call(rtrim($doKey, '/'), [&$a]);
					$this->data = array_merge($this->data, $a->data);
				}
				if (!empty($value['method'])) {
					$method = $value['method'];
					break;
				}
			}
		}
		empty($method) && $a->err(404);
		$_REQUEST = array_merge($_GET, $_POST, $rewrite);
		foreach (['url', 'dir', 'keys', 'data'] as $v) {
			$a->$v =& $this->$v;
		}
		$method = empty($method) ? $a->method : $method;
		method_exists($a, $method) || $a->err(404);

		// 权限
		//if (!isset($value['permission']) || $value['permission'] !== false) {
		//	$a->permission($this->keys) || $this->err(401);
		//}
		$a->permission($a->keys);
		$a->init();
		$file = $a->$method();
		$a->ajax && Ajax::$is && $a->msg(1);
		return $file;
	}

	public function index() {
		return '/index.php';
	}
}