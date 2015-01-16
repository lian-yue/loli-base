<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-10 07:48:14
/*	Updated: UTC 2015-01-16 17:48:54
/*
/* ************************************************************************** */
use Admin\Base, Loli\Controller\Run, Admin\Script, Admin\Style, Loli\Ajax, Loli\Lang, Loli\Date, Loli\Static_;
class_exists('Admin\Base') || exit;
class Admin extends Base{
	use Run;
	public function __construct() {
		$this->dir =  Admin\DIR . '/Theme';
		$this->Style = new Style;
		$this->Script = new Script;


		// 用户
		if (!$user = $this->Admin->User->current()) {
			$this->userID = $user->ID;
		}

		// 时间和语言
		if ($this->isNonce(false)) {
			Lang::set(Lang::$current, false, true);
			Date::setTimezone(Date::$timezone, false, true);
		}

		// 是否登录
		$this->Style->login &= $this->userID;
		$this->Script->login &= $this->userID;

		// 资源
		$this->Style->add('admin', $this->url('', ['resources' => 'Style', 'lang' => Lang::$current, 'timezone' => Date::$timezone, 'userID' => $this->userID, 'v' => Static_::$version]), ['priority' => 50, 'login' => false]);
		$this->Script->add('admin', $this->url('', ['resources' => 'Script', 'lang' => Lang::$current, 'timezone' => Date::$timezone, 'userID' => $this->userID, 'v' => Static_::$version]), ['priority' =>50, 'parent' => 'jquery', 'login' => false]);

		// 内嵌的资源
		$this->Script->add('juqery.start', function() { echo  'var VERSION = \'' . Static_::$version . '\';  jQuery(document).ready(function($){' ."\n"; }, ['priority' => 5, 'login' => false]);
		$this->Script->add('juqery.end', function(){ echo '})' ."\n"; }, ['priority' => 99, 'login' => false]);

		//主题内嵌的资源必须的
		$this->Style->add('default', $dir . '/style.css', ['call' => true, 'priority' => 6]);
		$this->Script->add('default', $dir . '/script.js', ['call' => true, 'priority' => 6]);

		// 回调函数
		do_array_call('Admin', [&$this]);
	}

	public function get() {
		$__F = parent::get();
		Ajax::$is && $this->isNonce() && $this->msg(true, false, $this->data);
		if ($__F) {
			require $__F;
		}
	}
}