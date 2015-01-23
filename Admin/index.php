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
/*	Updated: UTC 2015-01-22 15:15:36
/*
/* ************************************************************************** */
use Loli\Model;
require __DIR__ . '/include.php';

$admin = new Admin;
if (in_array($resources = r('resources'), ['Style', 'Script'])) {
	$time = 7200;
	@header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $time) . ' GMT');
	@header("Pragma: cache");
	@header('Cache-Control: max-age=' . $time);
	@header('Content-Type: '. ($resources == 'Style' ? 'text/css' : 'application/x-javascript'));
	$admin->$resources->run();
	exit;
}