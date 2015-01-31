<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-03 10:27:12
/*	Updated: UTC 2015-01-26 11:42:21
/*
/* ************************************************************************** */
// 如果是网页 ICO 结束查询 或者 flash 请求
if (!empty($_SERVER['REQUEST_URI']) && in_array(strtolower($_SERVER['REQUEST_URI']), ['/favicon.ico', '/crossdomain.xml', '/robots.txt'])) {
	exit;
}
require __DIR__ . '/include.php';

echo dirname('./22');
die;

// 采用注册机制 绑定入口
// add('hostname'， ‘/路径’, '根方法') 
// add('hostname', '根方法') 
// add('hostname', '根方法') 