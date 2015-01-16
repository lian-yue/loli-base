<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-16 08:28:16
/*	Updated: UTC 2015-01-16 13:27:07
/*
/* ************************************************************************** */
use Loli\Model;

require dirname(__DIR__) . '/include.php';
require __DIR__ . '/config.php';

// 自动载入
$_SERVER['LOLI']['LIBRARY']['Admin'] = __DIR__ . '/Library/Admin.php';
$_SERVER['LOLI']['LIBRARY']['Admin/'] = __DIR__ . '/Library/Admin';


Model::__reg('Admin', ['file' => __DIR__ . '/Model/Admin.php']);


$admin = new Admin;
if (in_array($resources = r('resources'), ['Style', 'Script'])) {
	$time = 7200;
	@header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $time) . ' GMT');
	@header("Pragma: cache");
	@header('Cache-Control: max-age=' . $time);
	@header( 'Content-Type: '. ($resources == 'Style' ? 'text/css' : 'application/x-javascript'));
	$admin->$resources->run();
	die;
}