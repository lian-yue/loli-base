<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-10 07:27:20
/*	Updated: UTC 2015-01-16 17:48:35
/*
/* ************************************************************************** */
namespace Admin;
use Loli\Controller\Base as Base_, Loli\String, Loli\Token;
class_exists('Loli\Controller\Base') || exit;
class Base extends Base_{
	public $Style;
	public $Script;
	public $quotes = ['url', 'nodes', 'data', 'Style', 'Script', 'userID'];
	public $userID = 0;
	public function permission($nodes, $column = '', $value = '', $compare = '=') {
		return $this->userID && $this->Admin->User->permission($this->userID, $nodes, $column, $value, $compare);
	}

	public function path() {
		static $path;
		if (empty($path)) {
			return '/' . ltrim(preg_replace('/[\/\\\\]+/', '/', r('$path')), '/');
		}
		return $path;
	}
	public function url($path = '', $query = [], $ssl = null) {
		if (is_array($path)) {
			$path = '/' . implode('/', $path);
		}
		if ($path && $path{0} != '/') {
			$v = rtrim($v = $this->path(), '/') == $v || !$v ? $v : dirname($v);
			$path = '/'. $path . '/' . $v;
		}
		$query['$path'] = $path;
		$query = merge_string($query);
		$url = $this->url . ($query ? '?' . $query : '');
		if ($ssl !== null) {
			$parse = parse_url($url);
			$parse['scheme'] = $ssl ? 'https' : 'http';
			$url = merge_url($parse);
		}
		return $url;
	}
	public function getNonce($nodes = []) {
		return  String::key(Token::get() . ($nodes === false ? '' : $this->userID . '/' . implode('/', $nodes ? $nodes : $this->nodes) . current_ip()));
	}
}