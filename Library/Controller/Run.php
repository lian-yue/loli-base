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
/*	Updated: UTC 2015-01-23 11:03:39
/*
/* ************************************************************************** */
namespace Loli\Controller;
use Loli\Ajax, Loli\Message;
trait Run{

	private $_is = false;

	protected $var;

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
			if (!$value = $a->getNode($before, $current, $arg3, $rewrite)) {
				Message::set(404);
				Message::run();
			}
			$this->node[] = $value['node'];
			if (empty($value['class']) && empty($value['method'])) {
				Message::set(404);
				Message::run();
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
			Message::set(404);
			Message::run();
		}

		$_REQUEST = array_merge($_GET, $_POST, $rewrite);
		foreach ($this->quotes as $v) {
			$a->$v =& $this->$v;
		}

		if (!isset($value['permission']) || $value['permission'] !== false) {
			if (!$a->permission($a->node)) {
				Message::set(403);
				Message::run();
			}
		}
		$a->permission($a->node);
		$a->init();
		$this->var =& $a;
		return $a->$method();
	}
}