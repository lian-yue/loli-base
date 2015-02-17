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
/*	Updated: UTC 2015-02-16 07:51:31
/*
/* ************************************************************************** */
namespace Loli;
class Curl{

	// curl 文件保存途径
	protected $cookie = './';

	// 默认
	public $defaults = [];

	// info 信息
	public $_info = [];

	// curl
	private $_options = [];

	// 资源
	private $_chs = [];

	// 自动加载
	public function __construct(array $defaults = [], $cookie = false) {
		$this->cookie = $cookie == false ? (empty($_SERVER['LOLI']['CURL']['cookie']) ? './' : $_SERVER['LOLI']['CURL']['cookie']) : $cookie;
		$this->defaults = $defaults + [
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_DNS_CACHE_TIMEOUT => 300,
			CURLOPT_DNS_USE_GLOBAL_CACHE => true,
			CURLOPT_AUTOREFERER => true,
			CURLOPT_BINARYTRANSFER => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => 'gzip,deflate',
			CURLOPT_CONNECTTIMEOUT => 6,
			CURLOPT_TIMEOUT => 6,


			// 限制协议
			CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS | CURLPROTO_FTP | CURLPROTO_FTPS,
			CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS | CURLPROTO_FTP | CURLPROTO_FTPS,
		];
	}


	public function __invoke() {
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
	public function add($key, $options) {
		if (!empty($this->curl[$key])) {
			return false;
		}
		return $this->set($key, $options);
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
	public function set($key, $options) {
		$options = is_array($options) ? $options : [CURLOPT_URL => $options];
		if (empty($options[CURLOPT_URL])) {
			return false;
		}
		$this->_options[$key] = $options;
		$this->_info[$key] = [];
		return true;
	}



	/**
	 * 修改一个选项
	 * @param  [type] $key  key
	 * @param  [type] $curl [description]
	 * @return [type]       [description]
	 */
	public function edit($key, array $options) {
		if (empty($this->_options[$key])) {
			return false;
		}
		foreach ($options as $option => $value) {
			$this->_options[$key][$option] = $value;
		}
		if (!empty($this->_chs[$key])) {
			foreach ($options as $option => $value) {
				$v === null || curl_setopt($this->_chs[$key], $option, $value);
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
		if (empty($this->_options[$key])) {
			return false;
		}
		unset($this->_options[$key], $this->_info[$key], $this->_chs[$key]);
		return true;
	}


	public function clear() {
		$this->_info = $this->_options = $this->_chs = [];
		return true;
	}

	public function info($all = false) {
		return $all ? $this->_info : reset($this->_info);
	}

	public function error($key) {
		return isset($this->_info[$key]['error']) ? $this->_info[$key]['error'] : false;
	}

	public function errno($key) {
		return isset($this->_info[$key]['errno']) ? $this->_info[$key]['errno'] : false;
	}


	public function get($all = false) {
		if (!$this->_options) {
			return $all ? [] : false;
		}
		$this->_chs = [];
		foreach ($this->_options as $key => $options) {
			$this->_chs[$key] = curl_init();


			$options += $this->defaults;
			foreach ($options as $optoin => $value) {
 				if ($value === null) {
 					continue;
 				}
 				if ($optoin == CURLOPT_URL) {
 					if (substr($value, 0, 2) == '//') {
						$value = 'http:' . $value;
					}
 				} elseif ($optoin == CURLOPT_PROGRESSFUNCTION) {
 					if (!isset($options[CURLOPT_NOPROGRESS])) {
						curl_setopt($this->_chs[$key], CURLOPT_NOPROGRESS, false);
					}
				} elseif ($optoin == CURLOPT_COOKIEJAR || $optoin == CURLOPT_COOKIEFILE) {
					$value = $this->cookie($value);
				} elseif ($optoin == CURLOPT_FILE) {
					if (!is_resource($value)) {
						$downloads[$key]['file'] = $value;
						$downloads[$key]['temp'] = $value = tmpfile();
					}
					curl_setopt($this->_chs[$key], CURLOPT_RETURNTRANSFER, false);
				}
				curl_setopt($this->_chs[$key], $optoin, $value);
			}
			if (!$all) {
				break;
			}


			/*$this->_chs[$k] = curl_init()
			;
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
			}*/
		}



		$this->_info = [];

		// 单个获取的
		if (!$all) {
			foreach ($this->_chs as $key => &$ch) {
				// 结果
				$content = curl_exec($ch);

				// 信息
				$this->_info[$key] = curl_getinfo($ch);
				$this->_info[$key]['error'] = curl_error($ch);
				$this->_info[$key]['errno'] = curl_errno($ch);
				$this->_info[$key]['content'] = $content;

				// 关闭
				curl_close($ch);

				// 如果有下载就移动文件
				if (!empty($downloads[$key])) {
					$fp = fopen($downloads[$key]['file'], 'wb');
					fseek($downloads[$key]['temp'], 0);
					while (!feof($downloads[$key]['temp'])) {
					   fwrite($fp, fgets($downloads[$key]['temp']));
					}
					fclose($downloads[$key]['temp']);
				}

				$this->_options = $this->_chs = [];
				return $content;
			}
			return false;
		}








		// 是否执行中
		$running = false;

		// 创建一个列队
		$mh = curl_multi_init();

		// 加入列队
		foreach ($this->_chs as $key => $ch) {
			curl_multi_add_handle($mh, $ch);
		}

		// 等待执行完毕
		do {
			usleep(10000);
			curl_multi_exec($mh, $running);
		} while($running > 0);




		// 遍历返回值
		$results = [];
		foreach ($this->_chs as $key => $ch) {

			// 结果
			$results[$key] = curl_multi_getcontent($ch);

			// 信息
			$this->_info[$key] = curl_getinfo($ch);
			$this->_info[$key]['error'] = curl_error($ch);
			$this->_info[$key]['errno'] = curl_errno($ch);
			$this->_info[$key]['content'] = $results[$key];

			// 移出列队
			curl_multi_remove_handle($mh, $ch);

			// 如果有下载就移动文件
			if (!empty($downloads[$key])) {
				$fp = fopen($downloads[$key]['file'], 'wb');
				fseek($downloads[$key]['temp'], 0);
				while (!feof($downloads[$key]['temp'])) {
				   fwrite($fp, fgets($downloads[$key]['temp']));
				}
				fclose($downloads[$key]['temp']);
			}
		}

		// 关闭列队
		curl_multi_close($mh);

		$this->_options = $this->_chs = [];
		return $results;






		/*$this->_chs = [];
		foreach ($this->curl as $k => $v) {
			$this->_chs[$k] = curl_init();
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
				$this->info[$k]['content'] = $r;

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
			$this->info[$k]['content'] = $r[$k];

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
		return $r;*/
	}
}