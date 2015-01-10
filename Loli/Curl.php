<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-04-09 12:09:10
/*	Updated: UTC 2015-01-09 14:58:46
/*
/* ************************************************************************** */
namespace Loli;
class Curl{

	// curl 文件保存途径
	public $cookie = './';

	// 默认
	public $defaults = [];

	// info 信息
	public $info = [];

	// curl
	public $curl = [];

	// 资源
	public $resources = [];

	// 自动加载
	public function __construct($a = [], $cookie = false) {
		$this->cookie = $cookie == false ? (empty($_SERVER['LOLI']['CURL']['cookie']) ? './' : $_SERVER['LOLI']['CURL']['cookie']) : $cookie;
		$this->defaults = $a + [
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_DNS_CACHE_TIMEOUT => 300,
			CURLOPT_DNS_USE_GLOBAL_CACHE => true,
			CURLOPT_AUTOREFERER => true,
			CURLOPT_BINARYTRANSFER => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => 'gzip,deflate',
			CURLOPT_CONNECTTIMEOUT => 6,
			CURLOPT_TIMEOUT => 6,
		];
	}


	public function __invoke(){
		return call_user_func_array([$this, 'get'], func_get_args());
	}
	/**
	*	curl 下载 cookie 文件保存位置
	*
	*	1 参数 key
	*
	*	返回值 文件途径
	**/
	public function cookie($key) {
		return $this->cookie .'/' . md5($key);
	}
	/**
	*	curl 上传
	*
	*	1 参数 文件地址
	*	2 参数 文件名称
	*	3 参数 文件类型
	*
	*	返回值 文件途径
	**/
	public function file($file, $type = false, $filename = false) {
		if (function_exists('curl_file_create')) {
			return curl_file_create($file, $type, $filename);
		}
		$r = '@' . $file;
		if ($type) {
			$r .= ';type='. $type;
		}
		if ($filename) {
			$r .= ';filename='. $filename;
		}
		return $r;
	}

	/**
	*	添加 curl
	*
	*	1 参数 key
	*	2 参数 url
	*	3 参数 附加 curl 属性
	*
	*	bool
	**/
	public function add($key, $url, $curl = []) {
		if (!empty($this->curl[$key])) {
			return false;
		}
		return $this->set($key, $url, $curl);
	}


	/**
	*	写入 curl
	*
	*	1 参数 key
	*	2 参数 url
	*	3 参数 附加 curl 属性
	*
	*	bool
	**/
	public function set($key, $url, $curl = []) {
		$this->curl[$key] = ['url' => $url, 'curl' => $curl];
		$this->info[$key] = [];
		return true;
	}

	public function opt($key, $curl) {
		if (empty($this->curl[$key])) {
			return false;
		}
		foreach ($curl as $k => $v) {
			$this->curl[$key]['curl'][$k] = $v;
		}
		if (!empty($this->resources[$key])) {
			foreach ($curl as $k => $v) {
				$v === null || curl_setopt($this->resources[$key], $k, $v);
			}
		}
		return true;
	}

	/**
	*	移除 curl
	*
	*	1 参数 key
	*	2 参数 url
	*	3 参数 附加 curl 属性
	*
	*	bool
	**/
	public function remove($key) {
		if (empty($this->curl[$key])) {
			return false;
		}
		unset($this->curl[$key], $this->info[$key], $this->resources[$key]);
		return true;
	}


	public function flush() {
		$this->info = [];
		$this->curl = [];
		$this->resources = [];
		return true;
	}


	public function get($all = false) {
		if (!$this->curl) {
			return false;
		}
		$this->resources = [];
		foreach ($this->curl as $k => $v) {
			$this->resources[$k] = curl_init();
			$curl = [];
			$curl[CURLOPT_URL] = $v['url'];
			$curl += $this->defaults;
			foreach ($v['curl'] as $kk => $vv) {
				if ($kk != CURLOPT_URL) {
					unset($curl[$kk]);
					$curl[$kk] = $vv;
				}
			}
 			foreach ($curl as $kk => $vv) {
 				if ($vv === null) {
 					continue;
 				}
				if ($kk == CURLOPT_PROGRESSFUNCTION && !isset($curl[CURLOPT_NOPROGRESS])) {
					curl_setopt($this->resources[$k], CURLOPT_NOPROGRESS, false);
				} elseif ($kk == CURLOPT_COOKIEJAR ) {
					$vv = $this->cookie($vv);
				} elseif ($kk == CURLOPT_COOKIEFILE || $kk == CURLOPT_COOKIEFILE ) {
					$vv = $this->cookie($vv);
				} elseif ($kk == CURLOPT_FILE) {
					if (!is_resource($vv)) {
						$downloads[$k]['file'] = $vv;
						$downloads[$k]['temp'] = $vv = tmpfile();
					}
					curl_setopt($this->resources[$k], CURLOPT_RETURNTRANSFER, false);
				}
				curl_setopt($this->resources[$k], $kk, $vv);
			}

			if (!$all) {
				break;
			}
		}
		$this->curl = [];
		$this->info = [];

		// 单个获取的
		if (!$all) {
			foreach ($this->resources as $k => &$_v) {
				// 结果
				$r = curl_exec($_v);

				// 信息
				$this->info[$k] = curl_getinfo($_v);
				$this->info[$k]['error'] = curl_error($_v);
				$this->info[$k]['errno'] = curl_errno($_v);
				// 关闭
				curl_close($_v);

				// 如果有下载就移动文件
				if (!empty($downloads[$k])) {

					$fopen = fopen($downloads[$k]['file'], 'wb');
					fseek($downloads[$k]['temp'], 0);
					while (!feof($downloads[$k]['temp'])) {
					   fwrite($fopen, fgets($downloads[$k]['temp']));
					}
					fclose($downloads[$k]['temp']);
				}
				return $r;
			}
			return false;
		}

		// 异步多个


		// 是否执行中
		$running = false;

		// 创建一个列队
		$mh = curl_multi_init();

		// 创建并句柄
		foreach ($this->resources as $k => $v) {
			curl_multi_add_handle($mh, $v);
		}

		// 等待执行完毕
		do {
			usleep(10000);
			curl_multi_exec($mh, $running);
		} while($running > 0);


		// 遍历返回值
		$r = [];
		foreach ($this->resources as $k => $v) {

			// 结果
			$r[$k] = curl_multi_getcontent($v);

			// 信息
			$this->info[$k] = curl_getinfo($v);
			$this->info[$k]['error'] = curl_error($v);
			$this->info[$k]['errno'] = curl_errno($v);

			// 移出列队
			curl_multi_remove_handle($mh, $v);

			// 如果有下载就移动文件
			if (!empty($downloads[$k])) {
				$fp = fopen($downloads[$k]['file'], 'wb');
				fseek($downloads[$k]['temp'], 0);
				while (!feof($downloads[$k]['temp'])) {
				   fwrite($fp, fgets($downloads[$k]['temp']));
				}
				fclose($downloads[$k]['temp']);
			}
		}

		// 关闭列队
		curl_multi_close($mh);
		return $r;
	}
}