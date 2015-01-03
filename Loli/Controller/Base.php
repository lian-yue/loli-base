<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-11-20 03:56:25
/*	Updated: UTC 2015-01-03 08:58:38
/*
/* ************************************************************************** */
namespace Loli\Controller;
use Loli\Model;
class Base extends Model{

	// URL 地址
	public $url = '';

	// load 载入
	public $load = './';

	public $menu = [];

	public $name = '';

	public $path = [];

	public $method = '';

	public $match = [];

	public $title = [];

	public $dateFormat = 'F j, Y';

	public $rewrite = [];

	public $data = [];

	public $variable = ['url', 'load', 'menu', 'name', 'path', 'method', 'title', 'rewrite', 'data'];

	public function init() {

	}

	public function load($file, $once = true) {
		foreach ((array)$file as $v) {
			if ($is = is_file($__F = $this->load .'/' . $v)) {
				break;
			}
		}
		if (empty($is)) {
			return false;
		}
		extract($this->data, EXTR_OVERWRITE);
		if ($once) {
			require_once $__F;
		} else {
			require $__F;
		}
		return true;
	}

	public function date($time, $format = false, $human = 31536000) {
		if ($human === true || ($human && $time <= time() && (time() - $time) <= $human)) {
			return $this->date->human($time);
		} else {
			return $this->date->format($format ? $format : $this->dateFormat, $time);
		}
	}

	public function url($path = '', $query = [], $ssl = null) {
		if (is_array($path)) {
			$path = '/' . implode('/', $path);
		}
		if ($path && $path{0} != '/') {
			$v = rtrim($v = $this->path(), '/') == $v || !$v ? $v : dirname($v);
			$path = '/'. $path . '/' . $v;
		}
		$query = merge_string($query);
		$url = $this->url. $path . ($query ? '?' . $query : '');
		if ($ssl !== null) {
			$parse = parse_url($url);
			$parse['scheme'] = $ssl ? 'https' : 'http';
			$url = merge_url($parse);
		}
		return $url;
	}

	public function path() {
		return url_path();
	}

	public function getNonce() {
		return $this->token();
	}

	public function doNonce() {
		return $this->Form->hidden(['name' => '_nonce', 'value' => r('_nonce') ? r('_nonce') : call_user_func_array([$this, 'getNonce'], func_get_args())]);
	}

	public function isNonce() {
		return r('_nonce') === call_user_func_array([$this, 'getNonce'], func_get_args());
	}

	public function exitNonce() {
		return call_user_func_array([$this, 'isNonce'], func_get_args()) || $this->err();
	}


	/**
	 *	消息结束
	 * @param  string $a 消息结束
	 * @return 无返回值直接结束
	 */
	public function msg($msg = '', $to = true, $args = []) {
		$this->data += ['msg' => $msg, 'to' => $to ? to([$to, 'referer'], [$this->url()]) : $to, 'args' => $args];
		$this->load('msg.php') || $this->die->msg($this->data['msg'], $this->data['to'], $this->data['args']);
	}

	/**
	 *	错误结束
	 * @param  string $a 错误消息
	 * @return 无返回值直接结束
	 */
	public function err($err = '', $to = true, $args = []) {
		$this->data += ['err' =>  $err ? $err : 403, 'to' => $to && $to !== true ? to([$to, 'referer'], [$this->url()]) : $to, 'args' => $args];
		$this->load('err.php') || $this->die->err($this->data['err'], $this->data['to'], $this->data['args']);
	}

	public function lang($a, $b = true) {
		static $s;
		if (isset($s)) {
			$s = $this->Lang;
		}
		return $s->__($a, ['default'], $b);
	}

	public function title($sep = ' _ ') {
		return $sep ? implode($sep, $this->title) : $this->title;
	}


	public function menu() {
		if (!$this->menu) {
			return false;
		}

		$a = [];
		$name = false;
		foreach ($this->menu as $k => $v) {
			$v += ['key' => [], 'value' => [], 'name' => '', 'class' => $k && is_string($k) ? [$k] : []];

			$g = [];
			foreach ($v['key'] as $key) {
				if (isset($_REQUEST[$key])) {
					$g[$key] = $_REQUEST[$key];
				}
			}

			if ($v['name']) {
				$name = true;
			}

			$aa = [];
			foreach ($v['value'] as $kk => $vv) {
				$vv += ['query' => [], 'name' => '', 'class' => $kk && is_string($kk) ? [$kk] : []];
				$vv['class'] = (array) $vv['class'];
				$break = false;
				foreach ($vv['query'] as $kkk => $vvv) {
					if ((isset($_REQUEST[$kkk]) && ($vvv === null || (!is_array($_REQUEST[$kkk]) ? (string) $_REQUEST[$kkk] : $_REQUEST[$kkk]) !== (!is_array($vvv) ? (string) $vvv : $vvv))) || (!isset($_REQUEST[$kkk]) && $vvv !== null)) {
						$break = true;
						break;
					}
				}
				if (!$break) {
					$vv['class'][] = 'current';
				}
				if ( is_string($kk)) {
					$vv['class'][] = $kk;
				}
				$aa[$kk] = '<li class="' . implode(' ', $vv['class']) . '"><a href="'. $this->url($this->path(), $vv['query']) . '">'. $vv['name'] . (isset($vv['count']) ? '<span class="count">('.$vv['count'].')</span>' : '') . '</a></li>';

			}

			$a[] = ['name' => $v['name'] ? $this->__(['$1:', $v['name']]) : '', 'class' => implode(' ', (array) $v['class']), 'value' => implode(' ', $aa)];

		}

		$r = '<div id="menu"><table><tbody>';
		foreach ($a as $v) {
			$r .= '<tr class="'. $v['class'] .'">';
			$r .= $name ? '<th class="">'. $v['name'] .'</th>' : '';
			$r .= '<td><ul class="cl">' . $v['value'] . '</ul></td>';
			$r .= '</tr>';
		}
		$r .= '</tbody></table></div>';
		echo $r;
	}
}