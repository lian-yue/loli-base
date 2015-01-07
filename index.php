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
/*	Updated: UTC 2015-01-07 15:01:43
/*
/* ************************************************************************** */
// 如果是网页 ICO 结束查询 或者 flash 请求
if (!empty($_SERVER['REQUEST_URI']) && in_array(strtolower($_SERVER['REQUEST_URI']), ['/favicon.ico', '/crossdomain.xml', '/robots.txt'])) {
	exit;
}

require __DIR__ . '/include.php';





$image =  new \Loli\Image\Imagick(__DIR__ . '/1.png');
$image->text('QWERTYU', 'F:\www\Loli\Loli\Captcha\Fonts\Default.ttf');
$image->show();