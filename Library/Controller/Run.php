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
/*	Updated: UTC 2015-01-26 12:39:35
/*
/* ************************************************************************** */
namespace Loli\Controller;
use Loli\Ajax, Loli\Message;
trait Run{

	private $_is = false;

	public function get() {
		if ($this->_is) {
			return false;
		}
		$this->_is = true;
		$this->load('include.php');
		$class = get_class($this);
		$doKey = strtr($class, '\\', '/') . '.';
		$a = $this;
		$rewrite = [];
		$before = '/';
		$after =  substr($a->path(), 1);
		while($after !== false) {
			list($current, $arg3) = explode('/', $after, 2) + [1 => false];

			// 没解析到
			if (!$value = $a->getNode($before, $current, $arg3, $rewrite)) {
				Message::set(404);
				Message::run();
			}

			// 无下一个
			if (empty($value['class']) && empty($value['method'])) {
				Message::set(404);
				Message::run();
			}

			// 类型必须不是0
			if (!empty($value['method']) && !empty($value['type'])) {
				Message::set(500);
				Message::run();
			}

			// 类型必须是0
			if (!empty($value['type']) && empty($value['method'])) {
				Message::set(500);
				Message::run();
			}


			// 选择节点
			$this->node[] = $value['node'];

			// 有类的
			if (!empty($value['class'])) {
				$class .= '\\' .  $value['class'];
				$a = new $class;
				$doKey .=  $value['class'] . '/';
				do_array_call(rtrim($doKey, '/'), [&$a]);
			}

			// 有方法的 跳出
			if (!empty($value['method'])) {
				$method = $value['method'];
				break;
			}

			// 要跳过的
			if (!isset($value['skip']) || $value['skip'] !== false) {
				$after = $arg3;
				$before .= $before == '/' ? $current : '/' . $current;
			}
		}

		// 没方法  404
		if (empty($method)) {
			Message::set(404);
			Message::run();
		}

		// 方法不存在
		if (method_exists($a, $method)) {
			Message::set(500);
			Message::run();
		}

		// 重写 _REQUEST
		$_REQUEST = array_merge($_GET, $_POST, $rewrite);


		// 对象引用
		foreach ($this->quotes as $v) {
			$a->$v =& $this->$v;
		}

		// 需要判断权限的
		if (!isset($value['permission']) || $value['permission'] !== false) {
			if (!$a->permission($a->node)) {
				Message::set(403);
				Message::run();
			}
		}

		// 默认执行的
		$a->init();


		// 返回数据
		return $a->$method();
	}
}