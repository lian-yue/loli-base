<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-16 11:03:14
/*	Updated: UTC 2015-01-16 13:25:29
/*
/* ************************************************************************** */
use Loli\Model, Loli\Static_;

// 载入文件
require __DIR__ . '/include.php';
class Install {
	use Model;

}


$install = new Install;








$create = $engine = [];


$create['Admin/Log'] = true;
$create['Admin/User'] = true;
$create['Admin/User/Log'] = true;
$create['Admin/User/Role'] = true;
$create['Admin/User/Role/Inherit'] = true;
$create['Admin/User/Role/Constraint'] = true;
$create['Admin/User/Node'] = true;
$create['Admin/User/Join'] = true;
$create['Admin/User/Permission'] = true;



$add = [
	'Admin/User' => [
		['ID' => 1, 'account' => 'admin', 'password' => '123456', 'loginIP' => '0.0.0.0'],
	],
	'Admin/User/Role' => [
		['ID' => 1, 'status' => true, 'name' => 'Administrator'],
	],
];

// 添加全部节点
function add_node($a, $install, $parent = 0) {
	$permissions = [];
	$sort = 0;
	foreach ($a->allNode() as $v) {
		$ID = $install->Admin->User->Node->add(['key' => $v['node'], 'parent' => $parent]);
		if (!$ID && ($node = $install->Admin->User->Node->row(['key' => $v['node'], 'parent' => $parent, 'sort' => $sort, 'name' => empty($v['name']) ? $v['node'] : $v['name']]))) {
			$ID = $node->ID;
		}
		if ($ID && !empty($node['class'])) {
			$class = get_class($a) .'\\'. $node['class'];
			add_node(new $class, $install, $ID);
		}
		if ($ID) {
			$args = [];
			if (!empty($v['form'])) {
				foreach($v['form'] as $kk => $vv) {
					$name = $vv['name'];
					$isArray = !empty($vv['type']) && ($vv['type'] == 'checkbox' || ($vv['type'] == 'select' && !empty($vv['multiple'])));
					$args[$name] = isset($vv['value']) ? ($isArray ? (array) $vv['value'] : $vv['value']) : ($isArray?[]: '');
				}
			}
			$permissions[] = ['roleID' => 1, 'nodeID' => $ID, 'status' => true, 'args' => $args];
		}
		++$sort;
	}
	$permissions && $install->Admin->User->Permission->sets($permissions);
}
add_node(new Admin);

/*
// 创建表
$install->Admin->Log->create();
$install->Admin->User->create();
$install->Admin->User->Log->create();
$install->Admin->User->Role->create();
$install->Admin->User->Role->Inherit->create();
$install->Admin->User->Role->Constraint->create();
$install->Admin->User->Node->create();
$install->Admin->User->Join->create();
$install->Admin->User->Permission->create();




// 创建用户
$install->Admin->User->add(['ID' => 1, 'account' => 'admin', 'password' => '123456', 'loginIP' => '0.0.0.0']);
$install->Admin->User->Role->add(['ID' => 1, 'account' => 'admin', 'password' => '123456', 'loginIP' => '0.0.0.0']);





$install->Cache->flush();
Static_::flush();







/*
$create = $engine = [];


$add = [
	'Admin/User' => [
		['ID' => 1, 'account' => 'admin', 'password' => '123456', 'loginIP' => '0.0.0.0'],
	],
	'Admin/User/Role' => [
		['ID' => 1, 'status' => true, 'name' => 'Administrator'],
	],
	'Admin/User/Join' => [
		['userID' => 1, 'roleID' => 1],
	],
];




for($i = 0; $i < 20; ++$i) {
	echo $model->string->rand( 64, '0123456789qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM`~!@#$%^&*()_+|-=[]{};:,./?' ) ."<br/>";
}


$model->cache->flush();
foreach ( $create as $k => $v ) {
	if ( is_array($v ) ) {
		$model->db->create( $model->query->create($v, $k, empty($engine[$k]) ? '': $engine[$k]), false);
	} else {
		$k = explode( '.', $k );
		$a = $model;
		while ( ( $key = array_shift( $k ) ) && $k ) {
			if ( !$a->_has( $key ) && !$a->__has( $key ) && !isset( $a->$key ) ) {
				break;
			}
			$a = $a->$key;
		}
		if ( !$k && ( $a->_has( $key ) || $a->__has( $key ) && !isset( $a->$key ) ) ) {
			$a->{$key}->create();
		}
	}
}



// 选项
foreach ( $option as $k => $v ) {
	$model->option->add($k, $v );
}






// 添加数据
foreach ( $add as $k => $v ) {
	$k = explode( '.', $k );
	$a = $model;
	while ( ( $key = array_shift( $k ) ) && $k ) {
		if ( !$a->_has( $key ) && !$a->__has( $key ) && !isset( $a->$key ) ) {
			break;
		}
		$a = $a->$key;
	}
	if ( !$k && ( $a->_has( $key ) || $a->__has( $key ) && !isset( $a->$key ) ) ) {
		foreach ( $v as $vv ) {
			$a->$key->add($vv);
		}
	}
}


// 清空缓存
$model->cache->flush();
$model->static->flush();*/