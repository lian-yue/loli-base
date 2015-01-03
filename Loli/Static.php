<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-01-15 13:01:52
/*	Updated: UTC 2014-12-31 07:14:18
/*
/* ************************************************************************** */
namespace Model;
use Loli\Model;
class Static_ extends Model{

	// 全部注册信息
	private $_all = [];

	private $_link;

	private $_linkArgs;

	// 需要运行 php 的 后缀
	public $php = ['css', 'js', 'htm', 'html', 'php', 'xml', 'txt'];


	// 根目URL
	public $url = '/';

	// 版本号
	public $version = 0;



	public function __construct() {
		if (!empty($_SERVER['LOLI']['STATIC'])) {
			$this->_linkArgs = $_SERVER['LOLI']['STATIC'];
			foreach ( $_SERVER['LOLI']['STATIC'] as $k => $v) {
				if (in_array($k, ['url', 'version'])) {
					$this->$k = $v;
				}
			}
		}
	}


	public function __invoke(){
		return call_user_func_array([$this, 'url'], func_get_args());
	}

	/**
	*	写入缓存 资源数据
	*
	*	1 参数 原始目录 or 文件
	*	2 参数 缓存目录 or 文件 路径
	*
	*	返回值 bool
	**/
	public function add($source, $dest) {
		if (!$source | !$dest || !empty($this->_all[$source])) {
			return false;
		}
		$this->_all[$source] = ['dest' => $dest];
		return true;
	}

	public function url($a, $f = true) {
		if (strpos($a, '$version') === false && $f && preg_match('/\.[a-z]+$/i', $a)) {
			$a .= (strpos($a, '?') === false ? '?' : '&') . 'v=$version';
		}
		return $this->url . strtr($a, ['$lang' => $this->Lang->current, '$version' => $this->version]);
	}

	/**
	*	移除资源
	*
	*	1 参数 key
	*
	*	返回值 bool
	**/
	public function remove($key) {
		if (empty($this->_all[$key])) {
			return false;
		}
		unset($this->_all[$key]);
		return true;
	}

	/**
	*	执行缓存资源
	*
	*	无参数
	*
	*	返回值 bool
	**/
	public function flush() {
		$current = $this->Lang->current;
		foreach ($this->_all as $k => $v) {
			foreach(is_file($k) ? [$v] : $this->_dir($k, $v['dest']) as $source => $vv) {
				foreach ($this->Lang->all as $kkk => $vvv) {
					$dest = strtr($vv['dest'], ['$lang' => $kkk, '$version' => $this->version]);
					$this->_generate($source, $dest);
					if ($dest == $vv['dest']) {
						break;
					}
				}
			}
		}
		$this->Lang->set($current);
	}


	/**
	*	生成某个缓存文件
	*
	*	1 参数 原始文件路径
	*	2 参数 缓存文件路径
	*
	*	返回值 bool
	**/
	private function _generate($source, $dest) {
		if (!is_file($source)) {
			return false;
		}
		if (!$this->_link) {
			$type = empty($this->_linkArgs['type']) ? 'Local' : $this->_linkArgs['type'];
			$class = '\Loli\File\\' . $type;
			$this->_link = new $class($this->_linkArgs);
		}
		if ($php = preg_match('/\.('. implode('|', $this->php) .')$/i', $dest)) {
			ob_start();
			require $source;
			$contents = ob_get_contents();
			ob_end_clean();
			$this->_link->cput($dest, $contents);
		} else {
			$this->_link->put($dest, $source);
		}
		return true;
	}



	/**
	*	列出 dir 里面所有文件
	*
	*	1 参数 目录
	*
	*	返回值 array
	**/
	private function _dir($source, $dest) {
		$r = [];
		if (is_dir($source)) {
			$handle = opendir($source);
			// 循环
			while ($path = readdir($handle)) {
				if ($path == '.' || $path == '..') {
					continue;
				}
				if (is_dir($source . '/'. $path)) {
					$r = array_merge($r, $this->_dir($source . '/'. $path, $dest . '/'. $path));
					continue;
				}
				$r[$source .'/'. $path] = ['dest' => $dest .'/'. $path];

			}
			closedir ($handle);
		}
		return $r;

	}

}

return new Static_;
