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
/*	Updated: UTC 2015-02-03 15:58:39
/*
/* ************************************************************************** */
namespace Loli;
trait_exists('Loli\Model', true) || exit;
abstract class Controller{
	use Model;



	// 路径
	//public $path = '/';

	// 节点
	//public $node = [];

	// 验证地址
	//public $auth = '';

	// 返回的数据
	//public $data = [];

	// 全部节点
	//public $allNode = [
		/*'节点名称' => [

			// 这是 runNoce 支持的
			'pattern' => [
				'正则' => [
					'method' => '允许的http方法数组 默认 POST GET',
					'rewrite' => 'url重写',
					'matches' => 'url重写用正则匹配到的数据',
				],
			],
			'rewrite' => 'url重写 $_REQUEST 附加',



			// 这是 解析路由支持
			'class' => '回调类名',
			'method' =>'回调方法',
			'auth' =>  false = 不需要认证 true = 需要
			'skip' => 'bool === false = 不跳过目录',
			'type' => '0 = 导航节点(可包含)				1 = 读节点(不可包含)			2 = 请求动作节点(不可包含)',
		]*/
	//];

	// 时间格式
	//public $dateFormat = 'F j, Y';


	// 需要引用的数据
	//public $quotes = ['dir', 'url', 'path', 'node', 'data'];


	/*public function runNode($current, $after, &$rewrite) {
		foreach($this->allNode as $node => $value) {
			// 没有正则使用 节点名
			if (!isset($value['pattern'])) {
				$value['pattern'] = ['^' . preg_quote(strtolower(empty($value['method']) ? $node : $node . '/?', '')) . '$' => []];
			}

			// 没有匹配跳过
			if (!$value['pattern']) {
				continue;
			}

			foreach($value['pattern'] as $pattern => $vv) {
				if (empty($value['method']) && !in_array($_SERVER['REQUEST_METHOD'], empty($vv['method']) ? ['POST', 'GET'] : (array) $vv['method'])) {
					continue;
				}

				if (!preg_match('/'. strtr($pattern, ['/' => '\\/']) .'/', empty($value['method']) && empty($vv['type']) ? $current : $current . ($after !== false ? '' : '/'. $after), $matches)) {
					continue;
				}

				if (!empty($value['rewrite'])) {
					$rewrite = $value['rewrite'] + $rewrite;
				}

				if (!empty($vv['rewrite'])) {
					$rewrite = $vv['rewrite'] + $rewrite;
				}

				// 结果 url 重写
				foreach($matches as $kkk => $vvv) {
					if (isset($vv['matches'][$kkk])) {
						$rewrite[$vv['matches'][$kkk]] = $vvv;
					}
				}
				return $node;
			}
		}
		return false;
	}

	public function getNode($node) {
		return isset($this->allNode[$node]) ? $this->allNode[$node] : false;
	}

	public function allNode() {
		return $this->allNode;
	}




	abstract public function auth($node, $column = '', $value = '', $compare = '=');

	public function init() {

	}

	// 载入文件
	public function load($_file, $_once = true) {
		foreach ((array)$_file as $v) {
			if ($_is = is_file($_f = $this->dir .'/' . $v)) {
				break;
			}
		}
		if (empty($is)) {
			return false;
		}
		foreach ($this->data as $k => $v){
			if (!$k || !is_string($k) || $k{0} == '_' || $k == 'this' || $k == 'GLOBALS') {
				unset($this->data[$k]);
			}
		}
		unset($k, $v);
		extract($this->data, EXTR_OVERWRITE);
		if ($_once) {
			require_once $_f;
		} else {
			require $_f;
		}
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
			$path = substr($v = $this->path, -1, 1) == '/' ? $v . $path : dirname($v) .'/'. $path;
			$path = '/' . ltrim($path, '/');
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

	public function isNonce() {
		return r('_nonce') === call_user_func_array([$this, 'getNonce'], func_get_args());
	}

	public function exitNonce() {
		if (!call_user_func_array([$this, 'isNonce'], func_get_args())) {
			Message::set(400);
			Message::run();
		}
	}

	public function lang() {
		return call_user_func_array(['Lang', 'get'], func_get_args());
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
				$aa[$kk] = '<li class="' . implode(' ', $vv['class']) . '"><a href="'. $this->url($this->path, $vv['query']) . '">'. $vv['name'] . (isset($vv['count']) ? '<span class="count">('.$vv['count'].')</span>' : '') . '</a></li>';
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
	}*/
}