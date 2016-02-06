<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-09-24 15:30:40
/*
/* ************************************************************************** */
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-04-09 12:09:10
/*	Updated: UTC 2015-04-09 12:49:12
/*
/* ************************************************************************** */
namespace Loli;
class CURL{

	// curl 文件保存途径
	protected $cookie = './';

	protected $tempdir = '';

	// cookie 是否储存在本地
	protected $cookieLocal = true;

	// 默认
	protected $defaults = [];

	// info 信息
	private $_info = [];

	// curl
	private $_options = [];

	// 资源
	private $_chs = [];

	// 自动加载
	public function __construct(array $defaults = [], $cookie = false, $tempdir = false) {
		$this->cookie = $cookie == false ? (empty($_SERVER['LOLI']['curl']['cookie']) ? './' : $_SERVER['LOLI']['curl']['cookie']) : $cookie;
		$this->tempdir = $tempdir;

		// cookie 是否是本地协议
		if (preg_match('/^([0-9a-z._-]+)\:\/\//i', $this->cookie, $matches) && strcasecmp($matches[1], 'file') !== 0) {
			$this->cookieLocal = false;
		}
		$this->defaults = $defaults + $this->defaults + [
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


	public function __invoke(...$args) {
		return $this->get(...$args);
	}
	/**
	*	curl 下载 cookie 文件保存位置
	*
	*	1 参数 keys 如果是数组的话就是多重目录
	*
	*	返回值 文件途径
	**/
	public function cookie($keys) {
		$keys = (array) $keys;
		$name = array_pop($keys);
		foreach ($keys as &$dir) {
			$dir = trim(preg_filter('/[^0-9a-z_-]/', '.', $dir), '.');
			$dir = $dir ? $dir : 'empty';
		}
		$keys = $keys ? '/' . implode('/', $keys) . '/' : '/';
		return $this->cookie . $keys . md5($name);
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
		$result = '@' . $file;
		if ($type) {
			$result .= ';type='. $type;
		}
		if ($filename) {
			$result .= ';filename='. $filename;
		}
		return $result;
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
			return $this;
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

		$this->_options[$key] = [];
		foreach ($options as $option => $value) {
			$option = is_int($option) ? $option : constant('CURLOPT_' . strtoupper($option));
			$this->_options[$key][$option] = $value;
		}
		if ((empty($this->_options[$key][CURLOPT_URL]) && empty($this->defaults[CURLOPT_URL])) || (isset($this->_options[$key][CURLOPT_URL]) && empty($this->_options[$key][CURLOPT_URL]))) {
			unset($this->_options[$key]);
			throw new Exception('URL can not be empty');
		}
		$this->_info[$key] = [];
		return $this;
	}



	/**
	 * 修改一个选项
	 * @param  [type] $key  key
	 * @param  [type] $curl [description]
	 * @return [type]       [description]
	 */
	public function edit($key, array $options) {
		if (empty($this->_options[$key])) {
			throw new Exception('Key does not exist');
		}
		foreach ($options as $option => $value) {
			$option = is_int($option) ? $option : constant('CURLOPT_' . strtoupper($option));
			$this->_options[$key][$option] = $value;
		}
		if (!empty($this->_chs[$key])) {
			foreach ($options as $option => $value) {
				$v === NULL || curl_setopt($this->_chs[$key], $option, $value);
			}
		}
		return $this;
	}

	/**
	*	移除 curl
	*
	*	1 参数 key
	*
	*	bool
	**/
	public function remove($key) {
		if (empty($this->_options[$key])) {
			throw new Exception('Key does not exist');
		}
		unset($this->_options[$key], $this->_info[$key], $this->_chs[$key]);
		return $this;
	}


	public function clear() {
		$this->_info = $this->_options = $this->_chs = [];
		return $this;
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
			throw new Exception('No URL may request');
		}
		$this->_chs = [];
		foreach ($this->_options as $key => $options) {
			$this->_chs[$key] = curl_init();

			$options += $this->defaults;

			$skips = [];
			foreach ($options as $optoin => $value) {
 				if ($value === NULL || in_array($optoin, $skips)) {
 					continue;
 				}
 				if ($optoin === CURLOPT_URL) {
 					if (substr($value, 0, 2) === '//') {
						$value = 'http:' . $value;
					}
 				} elseif ($optoin === CURLOPT_PROGRESSFUNCTION) {
 					if (!isset($options[CURLOPT_NOPROGRESS])) {
						curl_setopt($this->_chs[$key], CURLOPT_NOPROGRESS, false);
					}
				} elseif ($optoin === CURLOPT_COOKIEJAR || $optoin === CURLOPT_COOKIEFILE) {
					$value = $this->cookie($value);

					// cookie 不是本地
					if (!$this->cookieLocal) {
						$curlCookieFiles[$key][$optoin] = $value;
						if (empty($cookieFiles[$value])) {
							$cookieFiles[$value] = tempnam($this->tempdir, 'curl');
							if ($optoin === CURLOPT_COOKIEJAR) {
								$contents = file_get_contents($value);
								$contents && file_put_contents($cookieFiles[$value], $contents);
							}
							$value = $cookieFiles[$value];
						}
					}
				} elseif ($optoin === CURLOPT_FILE) {
					if (!is_resource($value)) {
						if (!$value) {
							continue;
						}
						$downloads[$key] = $value = fopen($value, 'wb');
					}
					$skips[] = CURLOPT_RETURNTRANSFER;
					curl_setopt($this->_chs[$key], CURLOPT_RETURNTRANSFER, false);
				}
				$skips[] = $optoin;
				curl_setopt($this->_chs[$key], $optoin, $value);
			}
			if (!$all) {
				break;
			}
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

				// 如果有下载就关闭指针
				if (isset($downloads[$key])) {
					fclose($downloads[$key]);
				}

				// 如果有远程 Cookie
				if (!empty($curlCookieFiles[$key])) {
					foreach ($curlCookieFiles[$key] as $optoin => $value) {
						if ($optoin === CURLOPT_COOKIEJAR) {
							$contents = file_get_contents($cookieFiles[$value]);
							$contents && file_put_contents($value, $contents);
						}
					}
					foreach ($cookieFiles as $value) {
						@unlink($value);
					}
				}
				$this->_options = $this->_chs = [];
				return $content;
			}
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

			// 移出列队
			curl_multi_remove_handle($mh, $ch);

			// 如果有下载就移动文件
			if (isset($downloads[$key])) {
				fclose($downloads[$key]);
			}

			// 如果有远程 Cookie
			if (!empty($curlCookieFiles[$key])) {
				foreach ($curlCookieFiles[$key] as $optoin => $value) {
					if ($optoin === CURLOPT_COOKIEJAR) {
						$contents = file_get_contents($cookieFiles[$value]);
						$contents && file_put_contents($value, $contents);
					}
				}
			}
		}

		// 删除缓存文件
		if (!empty($cookieFiles)) {
			foreach ($cookieFiles as $value) {
				@unlink($value);
			}
		}

		// 关闭列队
		curl_multi_close($mh);


		$this->_options = $this->_chs = [];
		return $results;
	}
}