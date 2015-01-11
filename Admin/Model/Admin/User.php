<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-05 16:44:04
/*	Updated: UTC 2015-01-11 15:00:32
/*
/* ************************************************************************** */
namespace Model\Admin;
use Loli\RBAC\Base, Loli\Date, Loli\Lang, Loli\String;
class User extends Query{
	use Base;

	// 登录附加
	public $login = __CLASS__;

	public $timeout = 2592000;

	public $request = 604800;

	public $current;




	// 密码附加
	public $password = 'admin';

	public $table = 'admin_user';

	// 默认限制
	public $limit = 10;

	// 索引参数
	public $args = [
		'ID' => '',
		'account' => '',
		'loginIP' => '>=',
		'loginDate' => '',
	];


	// 默认值
	public $defaults = [
		'account' => '',
		'password' => '',
		'lang' => '',
		'timezone' => '',
		'loginIP' => '',
		'loginDate' => 0,
	];


	// 自增字段
	public $insert_id = 'ID';

	// 主要字段
	public $primary = ['ID'];

	// 约束值
	public $unique = [['account']];

	// 允许添加
	public $add = true;

	// 允许 更新  字段
	public $update = true;

	// 允许 删除
	public $delete = true;

	public function __construct() {
		$this->login .= current_ip();

		$this->_reg('Log', ['file' => __CLASS__ .'/User/Log.php']);
		$this->_reg('Join', ['file' => __CLASS__ .'/User/Join.php']);
		$this->_reg('Node', ['file' => __CLASS__ .'/User/Node.php']);
		$this->_reg('Role', ['file' => __CLASS__ .'/User/Role.php']);
		$this->_reg('Permission', ['file' => __CLASS__ .'/User/Permission.php']);
	}


	public function w($w, $old, $args) {
		// 无效账号
		if (isset($w['account']) && !$this->isAccount($w['account'])) {
			return false;
		}
		// 无效密码
		if (isset($w['password']) && $w['password'] && !$this->isPassword($w['password'])) {
			return false;
		}

		// 有密码
		if (!empty($w['password'])) {
			$w['password'] = $this->enPassword($w['password']);
		}

		// 时区
		if (isset($w['timezone'])) {
			$w['timezone'] = in_array(Date::$allTimezone, $w['timezone'], true) ? $w['timezone'] : Date::$timezone;
		}

		// 语言
		if (isset($w['lang'])) {
			$w['lang'] = empty(Lang::$all[$w['lang']]) ? Lang::$current : $w['lang'];
		}
		return $w;
	}



	/**
	*	当前 管理员信息
	*
	*	无参数
	*
	*	返回值 false 对象
	**/
	public function current() {
		if ($this->current !== null) {
			return $this->current;
		}
		$this->current = false;
		$key = 'admin_' . String::key('admin', 10);
		if (empty($_COOKIE[$key]) || !is_string($_COOKIE[$key]) || count($a = explode('|', $_COOKIE[$key])) != 3) {
			return false;
		}
		list ($ID, $key, $time) = $a;

		if (!($ID = absint($ID)) || !($user = $this->get($ID))) {
			return false;
		}

		if ($key !== String::key($this->login . $user->ID . $user->password . $user->loginDate , 80)) {
			return false;
		}

		// 太久没访问了
		if (!($time = String::decode($time, $this->login)) || !($time = absint($time)) || $time > time() || ($time + $this->request) < time()) {
			return false;
		}

		// 登录时间超时
		if (($user->loginDate + $this->timeout) < time()) {
			return false;
		}

		// time 超过 10 分之一 然后自动写入 新的
		if (($time + ($this->request / 10)) < time()) {
			$time = time();
			$_COOKIE[$key] = implode('|', [$ID, $key, String::encode($time, $this->login)]);
			@setcookie($key, $_COOKIE[$key], $time + $this->timeout, \admin\PATH, \admin\HOST, (bool) \admin\SSL, true);
		}
		return $this->current = $user;
	}


	public function login($ID) {
		if (!$user = $this->get((int)$ID)) {
			return false;
		}
		$this->current = null;

		$time = time();

		$this->log->add(['userID' => $user->ID, 'type' => 'login']);

		$this->update(['loginDate' => $time], $user->ID);

		$user->loginDate = $time;

		$key = 'admin_' . String::key('admin', 10);

		$_COOKIE[$key] = [$user->ID];
		$_COOKIE[$key] = String::key($this->login . $user->ID . $user->password . $user->loginDate , 80);
		$_COOKIE[$key] = String::encode($time, $this->login);
		$_COOKIE[$key] = implode('|', $_COOKIE[$key]);
		@setcookie($key, $_COOKIE[$key], $time + $this->timeout, \Admin\PATH, \Admin\HOST, (bool) \Admin\SSL, true);
		return true;
	}




	public function isAccount($account) {
		if (empty($account) || !is_string($account) || trim($account) != $account) {
			return false;
		}
		if (preg_match('/^\d+$/', $account) || !preg_match('/^[0-9a-z._ -]{3,32}$/i', $account)) {
			return false;
		}
		return true;
	}

	public function isPassword($password) {
		if (empty($password) || !is_string($password)) {
			return false;
		}
		if (strlen($password) < 6) {
			return false;
		}
		if (trim($password) != $password) {
			return false;
		}
		return true;
	}


	public function comparePassword($password, $code) {
		return strlen($code) == 70 && $this->encryptPassword($password, substr($code, 0, 6)) === $code;
	}

	public function encryptPassword($password, $rand = false) {
		$password = trim($password);
		$rand = $rand ? substr($rand, 0, 6) : String::rand(6, '0123456789qwertyuiopasdfghjklzxcvbnm');
		$rand = zeroise($rand, 6);
		$key = $this->password . $rand;
		$password = md5($password);
		$password = md5($password. $key). md5($key. $password);
		return $rand . $password;
	}
}