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
/*	Updated: UTC 2015-01-13 15:45:21
/*
/* ************************************************************************** */
namespace Loli\Controller;
use Loli\Ajax;
trait Run{

	private $_is = false;

	public function get() {
		if ($this->_is) {
			return false;
		}
		$this->_is = true;
		$this->load('include.php');
		$class = get_class($this);
		$doKey = strtr($class, '\\', '/');
		do_array_call($doKey, [&$this]);
		$doKey .= '.';
		$a = $this;
		$rewrite = [];
		$before = '/';
		$after =  substr($a->path(), 1);
		while($after !== false) {
			list($current, $arg3) = explode('/', $after, 2) + [1 => false];
			($value = $a->getNode($before, $current, $arg3)) || $a->err(404);
			$this->nodes[] = $value['node'];

			empty($value['class']) && empty($value['method']) && $a->err(500);
			if (empty($value['rewrite'])) {
				$rewrite = array_merge($rewrite, $value['rewrite']);
			}

			if (!empty($value['class'])) {
				$class .= '\\' .  $value['class'];
				$a = new $class;
				$doKey .=  $value['class'] . '/';
				do_array_call(rtrim($doKey, '/'), [&$a]);
			}
			if (!empty($value['method'])) {
				$method = $value['method'];
				break;
			}
			if (empty($value['skip'])) {
				$after = $arg3;
				$before .= $before == '/' ? $current : '/' . $current;
			}
		}
		if (empty($method) || !method_exists($a, $method)) {
			$a->err(404);
		}

		$_REQUEST = array_merge($_GET, $_POST, $rewrite);
		foreach (['url', 'dir', 'nodes'] as $v) {
			$a->$v =& $this->$v;
		}

		if (!isset($value['permission']) || $value['permission'] !== false) {
			$a->permission($a->nodes) || $a->err(401);
		}
		$a->permission($a->nodes);
		$a->init();
		return $a->$method();
	}
}