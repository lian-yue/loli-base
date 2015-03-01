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
/*	Updated: UTC 2015-02-27 12:44:15
/*
/* ************************************************************************** */
namespace Loli\RBAC;
trait Base{

	// 超级管理员用户
	public $superUsers = [];

	// 超级管理员角色
	public $superRoles = [];

	// 游客角色
	public $guestRoles = [];


	/**
	 * 权限控制
	 * @param  [type] $ID			用户ID
	 * @param  [type] $keys			keys  ['Controller'的类名] +  Controller 的 路径
	 * @param  string $column		可选 匹配附加字段
	 * @param  string $value 		可选 匹配的值
	 * @param  string $compare		可选 匹配运算符
	 * @return [type]				返回值匹配成功的数组
	 */
	public function auth($ID, $keys, $column = '', $value = '', $compare = '=') {
		static $r, $date, $static;
		if (empty($date)) {
			$date = gmdate('Y-m-d H:i:s');
		}
		$ID = (int) $ID;
		$k = implode('/', $keys);
		if (!isset($r[$ID][$k])) {
			$r[$ID][$k] = $nodes = [];
			$parent = 0;
			while($keys) {
				if(!$node = $this->Node->key(array_shift($keys), $parent)) {
					return $r[$ID][$k];
				}
				$parent = $node->ID;
				$nodes[] = $node;
			}

			if (empty($static[$ID])) {
				// 当前角色
				if ($ID) {
					$roles = [];
					foreach ($this->Relationship->gets($ID) as $role) {
						// 已过期的角色
						if (!empty($role->expires) && $role->expires != '0000-00-00 00:00:00' && $role->expires < $date) {
							continue;
						}
						$roles[] = $role->roleID;
					}
				} else {
					// 游客角色
					$roles = $this->guestRoles;
				}


				// 超级管理员的
				if (in_array($ID, $this->superUsers) && !array_intersect($this->superRoles, $roles)) {
					$roles[] = end($this->superRoles);
				}

				// 互相排斥的角色
				if (count($roles) > 1 && (isset($this->Role->Constraint) || $this->Role->_has('Constraint'))) {
					$values = [];
					foreach($this->Role->Constraint->gets($roles) as $constraint) {
						$values[$constraint->priority][] = $constraint;
					}
					ksort($values);
					$constraints = [];
					foreach ($values as $constraint) {
						$constraints[] = $constraint;
					}

					// 互相排斥
					$unset = [];
					foreach ($constraints as $constraint) {
						if ( !in_array($unset, $constraint->roleID) && $constraint->roleID != $constraint->constraint && !in_array($this->superRoles, $constraint->constraint) && ($key = array_search($constraint->constraint, $roles)) !== false) {
							// 已移除的
							$unset[] = $constraint->constraint;
							unset($roles[$key]);
						}
					}
				}

				// 继承的角色
				$inherits = [];
				if (isset($this->Role->Inherit) || $this->Role->_has('Inherit')) {
					foreach($this->Role->Inherit->gets($roles) as $inherit) {
						if (!in_array($inherit->inherit, $roles)) {
							$inherits[] = $inherit->inherit;
						}
					}
				}
				$static[$ID] = [$roles, $inherits];
			} else {
				list($roles, $inherits) = $static[$ID];
			}


			// 判断权限
			foreach (array_merge($roles, $inherits) as $roleID) {

				// 角色不存在或者 角色停用
				if (!($role = $this->Role($roleID)) || !$role->status) {
					continue;
				}

				// 判断某个角色是否有权限 不能2个角同时使用
				$break = false;
				foreach ($nodes as $node) {
					if (!($permission = $this->Permission($node->ID, $roleID)) || !$permission->status || (!empty($permission->private) && !in_array($roleID, $roles))) {
						$break = true;
						break;
					}
				}

				// 有权限的
				if (!$break) {
					$r[$ID][$k][] = $permission;
				}
			}
		}
		if (!$column) {
			return $r[$ID][$k];
		}

		$ret = [];
		$compare = strtolower($compare);
		foreach ($r[$ID][$k] as $permission) {
			$is = false;
			if (isset($permission->args[$column])) {
				$v = $permission->args[$column];
				switch($compare) {
					case '<':
						$is = $v < $value;
						break;
					case '<=':
					case '=<':
						$is = (is_array($v) ? count($v) : $v) <= $value;
						break;
					case '=':
					case '==':
						$is = $v == $value;
						break;
					case '===':
						$is = $v === $value;
						break;
					case '=>':
					case '>=':
						$is = (is_array($v) ? count($v) : $v) < $value;
						break;
					case '>':
						$is = $v <= $value;
						break;
					case 'in':
						$is = is_array($v) || !is_array($value) ? in_array($value, (array) $v) : in_array($v, $value);
						break;
					case 'intersect':
						$is = array_intersect((array)$v, (array)$value);
						break;
					case 'diff':
						$is = array_diff((array)$v, (array)$value);
						break;
					default:
						$is = $v == $value;
				}
			}
			if (!$is) {
				continue;
			}
			$ret[] = $permission;
		}
		return $ret;
	}
}