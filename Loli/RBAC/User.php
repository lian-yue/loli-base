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
/*	Updated: UTC 2015-01-07 05:29:17
/*
/* ************************************************************************** */
namespace Loli\RBAC;
trait User{

	/**
	 * 权限控制
	 * @param  [type] $ID			用户id
	 * @param  [type] $keys			keys  ['Controller'的类名] +  Controller 的 路径
	 * @param  string $column		可选 匹配附加字段
	 * @param  string $value 		可选 匹配的值
	 * @param  string $compare		可选 匹配运算符
	 * @param  string $logical		AND 还是 OR 运算
	 * @return [type]				返回值匹配成功的数组
	 */
	public function permission($ID, $keys, $column = '', $value = '', $compare = '=', $logical = 'OR') {
		static $r;
		$ID = (int) $ID;
		$k = implode('/', $keys);
		if (!isset($r[$ID][$k])) {
			$permissions = $nodes = [];
			$parent = 0;
			while($keys) {
				if(!$node = $this->Node->key(array_shift($keys), $parent)) {
					return $r[$ID][$k] = [];
				}
				$parent = $node->ID;
				$nodes[] = $node;
			}
			foreach ($this->Join->role($ID) as $role) {
				$break = false;
				foreach ($nodes as $node) {
					if (!($permission = $this->Permission->get($node->ID, $role->ID)) || !$permission->status) {
						$break = true;
						break;
					}
				}
				if (!$break) {
					$permissions[$ID][] = $permission;
				}
			}
			$r[$ID][$k] = $permissions;
		}
		if (!$column) {
			return $r[$ID][$k];
		}

		$ret = []
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
						$is = $v <= $value;
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
						$is = $v < $value;
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
				if ($logical == 'OR') {
					continue;
				}
				$ret = [];
				break;
			}
			$ret[] = $permission;
		}
		return $ret;
	}
}