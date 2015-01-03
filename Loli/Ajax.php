<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-04-10 10:24:36
/*	Updated: UTC 2015-01-02 08:43:07
/*
/* ************************************************************************** */
namespace Model;

class Ajax extends Base{

	public $is = false;

	public $type = 'json';

	public $js = true;

	public $xmlhttprequest = false;

	public $accept = '';

	public function __construct() {
		$this->xmlhttprequest = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
		$this->accept = isset($_SERVER['HTTP_ACCEPT']) ? explode(',', $_SERVER['HTTP_ACCEPT'])[0] : '';
		if ($this->accept) {
			$this->accept = explode('/', $this->accept);
			$this->accept = strtolower(trim(end($this->accept)));
		}
		$this->is = $this->xmlhttprequest || in_array($this->accept, ['json', 'xml']) || !empty($_REQUEST['ajax']);
		$this->type = empty($_REQUEST['ajax']) ? (in_array($this->accept, ['json', 'xml'])? $this->accept : false) : (string) $_REQUEST['ajax'];
	}


	public function __invoke(){
		return call_user_func_array([$this, 'run'], func_get_args());
	}

	public function add($a) {
		if ($this->is) {
			return false;
		}
		return $this->set($a);
	}

	/**
	 * 写入 ajax
	 * @param 参数 string
	 */
	public function set($a) {
		$this->type = (string) $a;
		$this->is = true;
		return true;
	}

	/**
	 *  运行ajax
	 * @param  array $data 传入数组
	 * @return exit 结束掉
	 */
	public function run($data) {
		@header('Content-Ajax: true');
		$data = get_call('ajax.run', (array) $data, $this);
		$type = strtolower($this->type);
		if ($type == 'query') {
			$data = merge_string($data);
		} elseif($type == 'xml') {
			$call = function ($a) use(&$call) {
				$r = $attr = '';
				 foreach ($a as $k => $v) {
				 	if (!preg_match('/^[a-z][0-9a-z_]*$/i', $k)) {
				 		$attr = ' k="' . htmlspecialchars($k, ENT_QUOTES) . '"';
						$k  = 'item';
				 	}
			        $r .=  '<' . $k . $attr.'>' .((is_array($v) || is_object($v)) ? $call($v) :  htmlspecialchars($v, ENT_QUOTES)) . '</' . $k . '>' ."\n";
			    }
			    return $r;
			};
			@header('Content-Type: application/xml; charset=UTF-8');
			 $data = '<?xml version="1.0" encoding="UTF-8"?><root>'. $call($data) .'</root>';
		} elseif ($this->js && !in_array($type, ['true', 'false', 'null', 'json']) && !intval(substr($type, 0, 1)) && ($function = preg_replace('/[^0-9a-z_.-]/i', '', $this->type))) {
			@header('Content-Type: application/x-javascript; charset=UTF-8');
			$data = $function . '(' . json_encode($data) . ')';
		} else {
			if ('POST' != $_SERVER['REQUEST_METHOD'] || $this->xmlhttprequest || $this->accept == 'json') {
				@header('Content-Type: application/json; charset=UTF-8');
			}
			$data = json_encode($data);
		}
		exit($data);
	}
}