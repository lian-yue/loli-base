<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-02-15 14:00:37
/*	Updated: UTC 2015-01-21 15:04:21
/*
/* ************************************************************************** */
namespace Loli;

class Debug {
	// display 需要输出并且结束的错误
	public $display = null;

	// arg debug输出是否输出 arg内容
	public $args = false;


	// log 错误日志
	public $log = null;

	// dir 首页目录
	public $dir = '';

	public function __construct($args = []) {
		foreach($args as $k => $v) {
			if (in_array($k, ['display', 'args', 'log', 'dir'])) {
				$this->$k = $v;
			}
		}
		// debug 的
		set_error_handler([$this, 'errorHandler']);
		set_exception_handler([$this, 'exceptionHandler']);

		// 需要显示的错误级别
		$this->display === null || error_reporting($this->display);
		$this->display === null || @ini_set('display_errors', (int) (bool) $this->display);


		// 错误日志
		if ($this->log !== null) {
			@ini_set('log_errors', (bool) $this->log);
			$this->log && @ini_set('error_log', sprintf(is_string($this->log) ? $this->log : DIRNAME . '/Debug/%s.log', gmdate('Y-m-d H-i')));
		}
	}

	public function errorHandler($errno, $error, $errFile, $errLine, $errContext) {
		// 被 @ 取消的
		if (error_reporting() === 0) {
			return false;
		}

		ini_get('log_errors') && $this->_log($errno, $error, debug_backtrace());

		// 不显示的
		if (!(error_reporting() & $errno)) {
			return false;
		}
		ini_get('display_errors') &&  $this->_display($errno, $error, debug_backtrace());
	}

	public function exceptionHandler($a) {
		$trace = array_merge([['file' => $a->getFile(), 'line' => $a->getLine()]], $a->getTrace());
		ini_get('log_errors') && $this->_log($a->getCode(), $a->getMessage(), $trace);
		ini_get('display_errors') && $this->_display($a->getCode(), $a->getMessage(), $trace);
	}

	private function _log($errno, $error, $trace) {
		$a[] = $error;
		$i = 0;
		foreach ($trace as $k => $v) {
			if (empty($v['file'])) {
				continue;
			}
			if (count($a) == 1) {
				//$a[0] .= '';
				$a[] = 'Stack trace:';
			}
			$func = $k ?  (empty($v['class']) ? $v['function'] : $v['class'] . $v['type'] . $v['function']) : '';
			$args = [];
			foreach (!empty($v['args']) && (empty($v['_SERVER']) || $v['_SERVER'] !== $_SERVER) ? $v['args'] : [] as $vv) {
				$type = gettype($vv);
				if ($type == 'string') {
					$vv = $this->dir && in_array($func, ['', 'require', 'require_once', 'include', 'include_once']) ? ltrim(strtr(preg_replace('/^'. preg_quote($this->dir, '/') .'/', '', $vv), '\\', '/'), '/') : $vv;
					$args[] = '\''. addslashes($vv) .'\'';
				} elseif ($type == 'boolean'){
					$args[] = $vv ? 'true' : 'false';
				} elseif ($type == 'array' || $type == 'object') {
					$args[] = @var_export($vv, true);
				} elseif ($type == 'resource') {
					$args[] = 'Resource .' . get_resource_type($vv);
				} elseif ($type == 'NULL') {
					$args[] = $type;
				} else {
					$args[] = $vv;
				}
			}
			$a[] = '#' . $i . ' ' . $v['file'] . '(' . $v['line'] . '):  ' . ($func ? $func .'('. implode(', ', $args ) .')' :  '');
			++$i;
		}
		$a = implode("\n", $a) ."\n";
		ini_get('error_log') && error_log($a);
	}

	private function _display($errno, $error, $trace) {
		@ob_clean();

		// ajax 的
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			@header('Content-Type: application/json; charset=UTF-8');

			$a = [];
			foreach ($trace as $k => $v) {
				if (empty($v['file'])) {
					continue;
				}
				$func = $k ? (empty($v['class']) ? $v['function'] : $v['class'] . $v['type'] . $v['function']) : '';
				$args = [];
				foreach (!empty($v['args']) && (empty($v['_SERVER']) || $v['_SERVER'] !== $_SERVER) ? $v['args'] : [] as $vv) {
					$type = gettype($vv);
					if ($this->args) {
						if ($type == 'string') {
							$args[] = $this->dir && in_array($func, ['', 'require', 'require_once', 'include', 'include_once']) ? ltrim(strtr(preg_replace('/^'. preg_quote($this->dir, '/') .'/', '', $vv), '\\', '/'), '/') : $vv;
						} elseif ($type == 'resource') {
							$args[] = 'Resource .' . get_resource_type($vv);
						} else {
							$args[] = $vv;
						}
					} else {
						$args[] = ucfirst($type);
					}
				}

				$a[] = ['file' => $this->dir ? ltrim(strtr(preg_replace('/^'. preg_quote($this->dir, '/') .'/', '', $v['file']), '\\', '/'), '/') : $v['file'], 'line' => $v['line'], 'func' => $func, 'args' => $args];
			}
			echo json_encode(['errno' => $errno, 'error' => $error, 'trace' => $a]);
			exit;
		}

		@header('Content-Type:text/html; charset=UTF-8');
		$a[] = '<!DOCTYPE html>';
		$a[] = '<html xmlns="http://www.w3.org/1999/xhtml">';
		$a[] = '<head>';
		$a[] = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
		$a[] = '<meta name="robots" content="noindex,nofollow" />';
		$a[] = '<meta name="viewport" content="width=device-width">';
		$a[] = '<style type="text/css">';
		$a[] = 'html{background: #f9f9f9;}';
		$a[] = 'body,input,button,select,textarea{font: 14px/normal Tahoma,Helvetica,arial,sans-serif;color:#222;}';
		$a[] = 'body,div,ul,ol,li,dl,dd,p,h1,h2,h3,h4,h5,h6,form,fieldset,button,input,a,hr{margin:0;padding:0;}';
		$a[] = '.debug{line-height: 2em;background: #fff;font-family: sans-serif;margin: 2%;border: 1px solid #dfdfdf;}';
		$a[] = 'h1{font-size: 16px;padding: .8em 1em;}';
		$a[] = 'table{ width: 100%;border-collapse: collapse;border-spacing: 0;empty-cells: show;}';
		$a[] = 'thead{background: #f9f9f9;}';
		$a[] = 'td{border-top: 1px solid #dfdfdf;border-left: 1px solid #dfdfdf;padding: .1em 1em;}';
		$a[] = 'tbody td{color: #444;font-size: 12px;}';
		$a[] = 'td.order{border-left: 0;}';
		$a[] = 'td.func{word-break:break-all;}';
		$a[] = '</style>';
		$a[] = '<title>PHP Error</title>';
		$a[] = '</head>';
		$a[] = '<body>';
		$a[] = '<div class="debug">';
		$a[] = '<h1>(' . $errno . ') ' . $error .'</h1>';
		$a[] = '<table>';
		$a[] = '<thead><tr>';
		$a[] = '<td class="order">Order</td>';
		$a[] = '<td class="file">File</td>';
		$a[] = '<td class="line">Line</td>';
		$a[] = '<td class="func">Func</td>';
		$a[] = '</tr></thead>';
		$a[] = '<tbody>';
		$i = 0;
		foreach ($trace as $k => $v) {
			if (empty($v['file'])) {
				continue;
			}

			$func = $k ?  (empty($v['class']) ? $v['function'] : $v['class'] . $v['type'] . $v['function']) : '';
			$args = [];
			foreach (!empty($v['args']) && (empty($v['_SERVER']) || $v['_SERVER'] !== $_SERVER) ? $v['args'] : [] as $vv) {
				$type = gettype($vv);
				if ($this->args) {
					if ($type == 'string') {
						$vv = $this->dir && in_array($func, ['', 'require', 'require_once', 'include', 'include_once']) ? ltrim(strtr(preg_replace('/^'. preg_quote($this->dir, '/') .'/', '', $vv), '\\', '/'), '/') : $vv;
						$args[] = '\''. addslashes($vv) .'\'';
					} elseif ($type == 'boolean'){
						$args[] = $vv ? 'true' : 'false';
					} elseif ($type == 'array' || $type == 'object') {
						$args[] = @var_export($vv, true);
					} elseif ($type == 'resource') {
						$args[] = 'Resource .' . get_resource_type($vv);
					} elseif ($type == 'NULL') {
						$args[] = $type;
					} else {
						$args[] = $vv;
					}
				} else {
					$args[] = ucfirst($type);
				}
			}
			$a[] = '<tr ciass="order_'.$i.'">';
			$a[] = '<td class="order">' . $i . '</td>';
			$a[] = '<td class="file">' . ($this->dir ? ltrim(strtr(preg_replace('/^'. preg_quote($this->dir, '/') .'/', '', $v['file']), '\\', '/'), '/') : $v['file']) . '</td>';
			$a[] = '<td class="line">' . $v['line'] . '</td>';
			$a[] = '<td class="func">' . ($func ? $func .'('. htmlspecialchars(implode(', ', $args ), ENT_QUOTES) .');' : '') . '</td>';
			$a[] = '</tr>';
			++$i;
		}
		$a[] = '</tbody>';
		$a[] = '</table>';
		$a[] = '</div>';
		$a[] = '</body>';
		$a[] = '</html>';
		echo implode("\n", $a);
		exit;
	}
}