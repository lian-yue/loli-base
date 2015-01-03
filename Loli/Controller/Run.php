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
/*	Updated: UTC 2015-01-01 06:19:38
/*
/* ************************************************************************** */
namespace Loli\Controller;
class Run extends Base{

	public $is = false;

	public function __construct() {
		$this->_reg('Style', ['class' => __NAMESPACE__ . '\Resources\Style', 'match' => false]);
		$this->_reg('Script', ['class' =>__NAMESPACE__ .  '\Resources\Script', 'match' => false]);
	}

	public function __invoke() {
		if ($this->is) {
			return false;
		}
		$this->is = true;
		$this->load('/include.php');
		$a = $this;
		if ($this->path) {
			$path = $this->path;
			if ($this->name == end($this->path)) {
				$call = array_pop($path);
			} else {
				$this->name = '';
			}
			foreach($path as $v) {
				$a = $a->$v;
			}
		} else {
			$path = $this->path();
			$slash = $path != '/' && substr($path, -1, 1) == '/' ? '/' : '';
			$path = trim($path, '/');
			$parent = '';
			while($path !== false) {
				$arr = explode('/', $path, 2);
				$break = false;
				prioritysort($a->_DATA);
				foreach($a->_DATA as $k => $v) {
					if (empty($v['match'])) {
						if (isset($v['match'])) {
							continue;
						}
						$v['match'] = [preg_quote($k, '') => []];
					}
					foreach($v['match'] as $kk => $vv) {
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
							foreach($matches as $kkk => $vvv) {
								if (isset($vv[$kkk])) {
									$this->rewrite[$vv[$kkk]] = $vvv;
								}
							}
							$this->path[] = $k;
							$a = $a->$k;
							$break = true;
							break 2;
						}
					}
				}

				if (!$break) {
					break;
				}
				$path = isset($arr[1]) && empty($end) ? $arr[1] : false;
				$parent .= $arr[0] . '/'. (isset($arr[1]) && !empty($end) ? $arr[1] . '/' : '');
			}
			if (empty($break)) {
				foreach ($a->match as $k => $v) {
					foreach($v as $kk => $vv) {
						if (!$reset = ($kk{0} == '^')) {
							$kk = '^' . $kk;
						}
						if (!$end = (substr($kk, -1, 1) == '$')){
							$kk .= '$';
						}
						if ($reset && $end) {
							$subject = $parent . $path . $slash;
						} elseif ($reset) {
							$subject = $parent . $path;
						} elseif ($end) {
							$subject = $path . $slash;
						} else {
							$subject = $arr[0];
						}
						if (preg_match('/'. strtr($kk, ['/' => '\\/']) .'/', $subject, $matches)) {
							foreach($matches as $kkk => $vvv) {
								if (isset($vv[$kkk])) {
									$this->rewrite[$vv[$kkk]] = $vvv;
								}
							}
							$this->name = $call = $k;
							$this->path[] = $k;
							$break = true;
							break 2;
						}
					}
				}
			}
			$break || $this->err(404);
		}
		$_REQUEST = array_merge($_GET, $_POST, $this->rewrite);
		foreach ($a->variable as $v) {
			$a->$v = $this->$v;
		}
		$call = empty($call) ? 'index' : $call;
		method_exists($a, $call) || $this->err(404);
		$a->init();
		$file = $a->$call();
		$this->Ajax->is && $this->msg(1);

		$this->load($file);
		return true;
	}
}