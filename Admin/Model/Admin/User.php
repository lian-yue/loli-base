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
/*	Updated: UTC 2015-01-22 15:00:39
/*
/* ************************************************************************** */
namespace Model\Admin;
use Loli\RBAC\Base, Loli\Date, Loli\Lang, Loli\Code, Loli\Query;
class_exists('Loli\Query') || exit;

class User extends Query{
	use Base;

	// 登录附加
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
		'eee' => '',
	];

	public $as = ['eee' => ['function' => 'count']];


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


	public $create = [
		'ID' => ['type' => 'int', 'unsigned' => true, 'increment' => true, 'primary' => 0],
		'account' => ['type' => 'text', 'length' => 64, 'unique' => ['account' => 1]],
		'password' => ['type' => 'text', 'length' => 70],
		'lang' => ['type' => 'text', 'length' => 10],
		'timezone' => ['type' => 'text', 'length' => 64],
		'loginIP' => ['type' => 'text', 'length' => 40],
		'loginDate' => ['type' => 'int', 'unsigned' => true, 'key' => ['loginDate' => 0]],
	];

	public $joins = [
		'relationship' => ['this' => 'Relationship']
	];

	public function __construct() {
		$this->_reg('Log');
		$this->_reg('Relationship');
		$this->_reg('Node');
		$this->_reg('Role');
		$this->_reg('Permission');
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
		$key = 'admin_' . Code::key('admin', 10);
		if (empty($_COOKIE[$key]) || !is_string($_COOKIE[$key]) || count($a = explode('|', $_COOKIE[$key])) != 3) {
			return false;
		}
		list ($ID, $key, $time) = $a;

		if (!($ID = absint($ID)) || !($user = $this->get($ID))) {
			return false;
		}

		if ($key !== Code::key(__CLASS__ . $user->ID . $user->password . $user->loginDate , 80)) {
			return false;
		}

		// 太久没访问了
		if (!($time = Code::de($time, __CLASS__)) || !($time = absint($time)) || $time > time() || ($time + $this->request) < time()) {
			return false;
		}

		// 登录时间超时
		if (($user->loginDate + $this->timeout) < time()) {
			return false;
		}

		// time 超过 10 分之一 然后自动写入 新的
		if (($time + ($this->request / 10)) < time()) {
			$time = time();
			$_COOKIE[$key] = implode('|', [$ID, $key, Code::en($time, __CLASS__)]);
			@setcookie($key, $_COOKIE[$key], $time + $this->timeout, \admin\PATH, \admin\HOST, (bool) \admin\SSL, true);
		}


		// 如果换了 ip 就写入登录日志 记录最近 10 个 IP 防止多网的
		if (!is_array($in = $this->Cache->get('l'. $user->ID, __CLASS__))) {
			$in = [];
			foreach ($this->Log->results(['userID' => $user->ID, 'type' => 'login', '$order' => 'DESC', '$orderby' => 'dateline']) as $log) {
				$in[] = $log->IP;
			}
		}
		if (!in_array(current_ip(), $in)) {
			$this->Log->add(['userID' => $user->ID, 'type' => 'login', 'IP' => current_ip(), 'value' => 'Automatic']);
			$this->Cache->delete('l' . $user->ID, __CLASS__);
		}


		// 语言
		if (Lang::$name && Cookie::get(Lang::$name) && Lang::$current != $user->lang) {
			$this->update(['lang' => Lang::$current], $user->ID);
		} elseif (!r(Lang::$name)) {
			$user->lang == Lang::$current || Lang::set($user->lang);
		}


		// 时间
		if (Date::$name && Cookie::get(Date::$name) && Date::$timezone != $user->timezone) {
			$this->update(['timezone' => Date::$timezone], $user->ID);
		} elseif (!r(Date::$name)) {
			$user->timezone == Date::$timezone || Date::setTimezone($user->timezone);
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

		$key = 'admin_' . Code::key('admin', 10);

		$_COOKIE[$key] = [$user->ID];
		$_COOKIE[$key] = Code::key(__CLASS__ . $user->ID . $user->password . $user->loginDate , 80);
		$_COOKIE[$key] = Code::en($time, __CLASS__);
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
		$rand = $rand ? substr($rand, 0, 6) : mb_rand(6, '0123456789qwertyuiopasdfghjklzxcvbnm');
		$rand = zeroise($rand, 6);
		$key = $this->password . $rand;
		$password = md5($password);
		$password = md5($password. $key). md5($key. $password);
		return $rand . $password;
	}
}