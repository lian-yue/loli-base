<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-22 03:13:08
/*
/* ************************************************************************** */
namespace Loli\Model;
class File{


	protected $stream;

	protected $filesize;

	protected $offset;

	protected $length;

	// 直接发送文件地址 用 header  用了不限速  X-Accel-Redirect, X-LIGHTTPD-send-file, X-Sendfile
	protected $header = false;

	// 每次缓冲区大小
	protected $buffer = 2097152;

	// 限制速度
	protected $speed = 0;

	// 是否检测链接已断开
	protected $status = true;

	// 绝对刷送
	protected $flag = true;


	// 发送文件或资源 文件  大小
	public function __construct($stream, $filesize, $header = NULL) {
		if (!empty($_SERVER['LOLI']['file'])) {
			foreach ($_SERVER['LOLI']['file'] as $key => $value) {
				if ($value !== NULL && in_array($key, ['header', 'buffer', 'speed', 'flag', 'status'])) {
					$this->$key = $value;
				}
			}
		}

		if ($header !== NULL) {
			$this->header = $header;
		}


		$response = Route::response();
		$request = Route::request();


		// 开启允许分段下载
		$response->setHeader('Accept-Ranges', 'bytes');

		// 用 header 发送文件的
		if ($this->header && is_string($this->stream)) {
			$response->setStatus(200);
			if (is_string($this->header)) {
				$name = $this->header;
			} elseif (isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'nginx') !== false) {
				$name = 'X-Accel-Redirect';
			} elseif (isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'lighttpd') !== false && $_SERVER['SERVER_SOFTWARE'] < 'lighttpd/1.5') {
				$name = 'X-LIGHTTPD-send-file';
			} else {
				$name = 'X-Sendfile';
			}
			$response->setHeader($name, $this->stream);
			return;
		}


		// 需要分段的
		if (($status = $response->getCacheStatus()) === 206) {

			if (count($range = $request->getRanges()) !== 1) {
				// 不支持多段
				$status = 416;
			} else {
				// 206 检查文件大小等
				extract(end($range));

				// 末尾偏移的
				$offset = $offset < 0 ? $filesize + $offset : $offset;
				if ($offset < 0 || $offset > $filesize) {
					// 偏移量过大
					$status = 416;
				} else {
					// 发送长度
					$length = $length === false ? $filesize - $offset : $length;
					if (($offset + $length) > $filesize) {
						// 长度过大
						$status = 416;
					}
				}
				$this->offset = $offset;
				$this->length = $length;
			}
		} elseif ($status === 200) {
			$this->offset = 0;
			$this->length = $filesize;
		}

		// 写入状态码
		$response->setStatus($status);

		if ($status < 400) {
			// 发送文件长度头
			$response->setHeader('Content-Length', $this->length);
			$status === 206 && $response->setHeader('Content-Range', 'bytes ' . $this->offset. '-'. ($this->offset + $this->length - 1) . '/' . $filesize);
		} else {
			// 写入错误码
			throw new Exception("Error Processing Request", 1);
		}
	}



	// 发送文件
	public function __invoke() {
		// 已经用 header 发送了
		if ($this->header && is_string($this->stream)) {
			return;
		}

		// 发送 0 长度的
		if (!$this->length) {
			is_resource($this->stream) && @fclose($this->stream);
			return;
		}


		// 关闭缓冲区
		while($this->flag && ob_get_level()) {
			ob_end_clean();
		}

		// 绝对刷送
		ob_implicit_flush($this->flag);


		// 关闭浏览器后动作
		ignore_user_abort(!$this->status);


		// 发送全部的
		if (!$this->offset && $this->length === $this->filesize) {
			// 发送文件
			if (function_exists('http_send_file') && is_string($this->stream)) {
				$this->speed && http_speed(0.1, intval($this->speed / 10) + 1);
				http_send_file($this->stream);
				return;
			}

			// 发送资源
			if (function_exists('http_sendstream') && is_resource($this->stream)) {
				$this->speed && http_speed(0.1, intval($this->speed / 10) + 1);
				http_sendstream($this->stream);
				return;
			}

			// 发送文件 不限速的
			if (is_string($this->stream) && !$this->speed) {
				readfile($this->stream);
				return;
			}
		}


		// 打开
		$stream = is_resource($this->stream) ? $this->stream : fopen($this->stream, 'rb');

		// 每次缓冲区大小
		$buffer = $this->speed ? min(intval($this->speed / 20), $this->buffer) : $this->buffer;

		// 设置偏移
		fseek($stream, $this->offset);

		// 循环发送
		$sendLength = 0;
		while (!feof($stream) && $sendLength < $this->length) {
			$length = ($sendLength + $buffer) > $this->length ? $this->length - $sendLength : $buffer;
			echo @fread($stream, $length);
			if ($this->status && connection_status() !== CONNECTION_NORMAL) {
				break;
			}
			$sendLength += $length;
			$this->speed && usleep(50000);
		}

		// 关闭
		@fclose($stream);
	}





	public function status($status) {
		$this->status = $status;
		return $this;
	}

	public function flag($flag) {
		$this->flag = $flag;
		return $this;
	}

	public function speed($speed) {
		$this->speed = $speed;
		return $this;
	}

	public function buffer($buffer) {
		$this->buffer = $buffer;
		return $this;
	}
}