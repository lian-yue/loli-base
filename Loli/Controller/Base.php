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
/*	Updated: UTC 2015-01-13 17:00:05
/*
/* ************************************************************************** */
namespace Loli\Controller;
use Loli\Model, Loli\Date, Loli\Lang, Loli\Form, Loli\Exit_, Loli\Token;
abstract class Base{
	use Model;

	// URL 地址
	public $url = '';

	// dir 载入
	public $dir = './';

	// 节点
	public $nodes = [];

	// 全部节点
	public $allNode = [];

	// 时间格式
	public $dateFormat = 'F j, Y';

	// 返回的数据
	public $data = ['title' => [], 'menu' => []];

	public function getNode($before, $current, $after) {
		foreach($this->allNode as $value) {
			if (empty($value['pattern'])) {
				$value['pattern'] = ['^' . preg_quote(empty($value['method'])?$value['node'] : $value['node'] . '/', '') . '$' => []];
			}
			foreach($value['pattern'] as $pattern => $vv) {
				if (!in_array($_SERVER['REQUEST_METHOD'], empty($vv['method']) ? ['POST', 'GET'] : (array) $vv['method']) && !empty($value['method'])) {
					continue;
				}
				if (!preg_match('/'. strtr($pattern, ['/' => '\\/']) .'/', empty($value['method']) ? $current . ($after !== false ? '' : '/'. $after) : $current, $matches)) {
					continue;
				}
				foreach($matches as $kkk => $vvv) {
					if (isset($vv['query'][$kkk])) {
						$value['rewrite'][$vv['query'][$kkk]] = $vvv;
					}
				}
				return array_intersect_key($value, ['node' => '', 'method' => '', 'class' => '', 'rewrite' => '', 'permission' => '', 'form' => '', 'skip' => '']);
			}
		}
		return false;
	}
	public function allNode() {
		$r = [];
		foreach ($this->allNode as $key => $value) {
			$r[] = array_intersect_key($value, ['node' => '', 'method' => '', 'class' => '', 'rewrite' => '', 'permission' => '', 'form' => '', 'skip' => '']);;
		}
		return $r;
	}

	abstract public function permission($nodes, $column = '', $value = '', $compare = '=');

	// 默认执行
	public function init() {

	}

	// 载入文件
	public function load($file, $once = true) {
		foreach ((array)$file as $v) {
			if ($is = is_file($__F = $this->dir .'/' . $v)) {
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
		return true;
	}

	// 时间
	public function date($time, $format = false, $human = 31536000) {
		if ($human === true || ($human && $time <= time() && (time() - $time) <= $human)) {
			return Date::human($time);
		} else {
			return Date::format($format ? $format : $this->dateFormat, $time);
		}
	}

	// url地址
	public function url($path = '', $query = [], $ssl = null) {
		if (is_array($path)) {
			$path = '/' . implode('/', $path);
		}
		if ($path && $path{0} != '/') {
			$v = rtrim($v = $this->path(), '/') == $v || !$v ? $v : dirname($v);
			$path = '/'. $path . '/' . $v;
		}
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

	public function getNonce() {
		return Token::get();
	}

	public function doNonce() {
		return Form::hidden(['name' => '_nonce', 'value' => r('_nonce') ? r('_nonce') : call_user_func_array([$this, 'getNonce'], func_get_args())]);
	}

	public function isNonce() {
		return r('_nonce') === call_user_func_array([$this, 'getNonce'], func_get_args());
	}

	public function exitNonce() {
		return call_user_func_array([$this, 'isNonce'], func_get_args()) || $this->err();
	}

	public function lang() {
		return call_user_func_array(['Lang', 'get'], func_get_args());
	}

	public function path() {
		return url_path();
	}

	/**
	 *	消息结束
	 * @param string $a 消息结束
	 * @return 无返回值直接结束
	 */
	public function msg($msg = '', $to = true, $args = []) {
		$this->data += ['msg' => $msg, 'to' => $to ? to([$to, 'referer'], [$this->url()]) : $to, 'args' => $args];
		$this->load('msg.php') || Exit_::msg($this->data['msg'], $this->data['to'], $this->data['args']);
	}

	/**
	 *	错误结束
	 * @param string $a 错误消息
	 * @return 无返回值直接结束
	 */
	public function err($err = '', $to = true, $args = []) {
		$this->data += ['err' => $err ? $err : 403, 'to' => $to && $to !== true ? to([$to, 'referer'], [$this->url()]) : $to, 'args' => $args];
		$this->load('err.php') || Exit_::err($this->data['err'], $this->data['to'], $this->data['args']);
	}
	public function title($sep = ' _ ') {
		if (empty($this->data['title'])) {
			return $sep ? false : [];
		}
		return $sep ? implode($sep, $this->data['title']) : $this->data['title'];
	}
	public function menu() {
		if (!$this->data['menu']) {
			return false;
		}

		$a = [];
		$name = false;
		foreach ($this->data['menu'] as $k => $v) {
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

			$a[] = ['name' => $v['name'] ? $this->lang(['$1:', $v['name']]) : '', 'class' => implode(' ', (array) $v['class']), 'value' => implode(' ', $aa)];

		}

		$r = '<div class="menu"><table><tbody>';
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