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
/*	Updated: UTC 2015-01-11 15:56:21
/*
/* ************************************************************************** */
namespace Loli\Controller;
use Loli\Ajax;
trait Run{

	private $_is = false;

	protected function get() {
		if ($this->_is) {
			return false;
		}
		$this->_is = true;
		$this->load('include.php');
		$class = get_class($this);
		$doKey = $class . '.';
		do_array_call($class, [&$this]);
		$a = $this;
		if ($a->keys) {
			$keys = $a->keys;
			while($keys) {
				$key = array_shift($keys);
				empty($a->match[$key]) && $a->err(404);
				$value = $a->match[$key];
				empty($value['class']) && $keys && $a->err(404);
				if (!empty($value['file'])) {
					require $value['file'];
				}
				if (!empty($value['class'])) {
					$class .= '\\' .  $value['class'];
					$doKey .= '/' .  $value['class'];
					$a = new $class;
					do_array_call($doKey, [&$a]);
				}
				if (!empty($value['method'])) {
					$method = $value['method'];
				}
			}
		} elseif (!$a->match) {
			$value = [];
		} else {
			$path = $this->path();
			$slash = $path != '/' && substr($path, -1, 1) == '/' ? '/' : '';
			$path = trim($path, '/');
			$parent = '';
			while($path !== false) {
				$arr = explode('/', $path, 2);
				$break = false;
				prioritysort($a->match);
				foreach($a->match as $key => $value) {
					if (!isset($value['pattern'])) {
						$value['pattern'] = [preg_quote($key, '') => []];
					}
					foreach((array)$value['pattern'] as $kk => $vv) {
						if (!in_array($_SERVER['REQUEST_METHOD'], empty($vv['method']) ? ['POST', 'GET'] : (array) $vv['method'])) {
							continue;
						}
						if (!$reset = ($kk{0} == '^')) {
							$kk = '^' . $kk;
						}
						if (!$end = (substr($kk, -1, 1) == '$')) {
							$kk .= '$';
						}
						if ($reset && $end) {
							$subject = $parent . $path . $slash;
						} elseif ($reset) {
							$subject = $parent . $arr[0];
						} elseif ($end) {
							$subject = $path . $slash;
						} else {
							$subject = $arr[0];
						}
						if (preg_match('/'. strtr($kk, ['/' => '\\/']) .'/', $subject, $matches)) {
							$rewrite = [];
							foreach($matches as $kkk => $vvv) {
								if (isset($vv['query'][$kkk])) {
									$rewrite[$vv['query'][$kkk]] = $vvv;
								}
							}
							if (empty($value['key']) || ($key = $this->$value['key']($rewrite + $this->rewrite))) {
								$this->keys[] = $key;
								$this->rewrite = $rewrite + $this->rewrite;
								empty($value['class']) && !$end && isset($arr[1]) && $a->err(404);
								if (!empty($value['file'])) {
									require $value['file'];
								}
								if (!empty($value['class'])) {
									$class .= '\\' .  $value['class'];
									$doKey .= '/' .  $value['class'];
									$a = new $class;
									do_array_call($doKey, [&$a]);
								}
								if (!empty($value['method'])) {
									$method = $value['method'];
								} else {
									unset($method);
								}
								$break = true;
								break 2;
							}
						}
					}
				}
				if (!$break) {
					break;
				}
				$path = isset($arr[1]) && empty($end) ? $arr[1] : ($arr[0] === '' ? false : '');
				$parent .= $arr[0] . '/'. (isset($arr[1]) && !empty($end) ? $arr[1] . '/' : '');
			}
			!$break && ($arr[0] !== '' || isset($arr[1])) && $a->err(404);
		}

		$_REQUEST = array_merge($_GET, $_POST, $this->rewrite);
		foreach ($a->variable as $v) {
			$a->$v =& $this->$v;
		}
		$method = empty($method) ? $a->method : $method;
		method_exists($a, $method) || $a->err(404);

		// 权限
		if (!isset($value['permission']) || $value['permission'] !== false) {
			$a->permission($this->keys) || $this->err(401);
		}
		$a->init();
		$file = $a->$method();
		$a->ajax && Ajax::$is && $a->msg(1);
		return $file;
	}

	public function index() {
		return '/index.php';
	}
}