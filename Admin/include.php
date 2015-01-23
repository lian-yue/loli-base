<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-01-19 12:02:27
/*	Updated: UTC 2015-01-22 08:35:26
/*
/* ************************************************************************** */
use Loli\Model;
require dirname(__DIR__) . '/include.php';
require __DIR__ . '/config.php';

// 自动载入
$_SERVER['LOLI']['LIBRARY']['Admin'] = __DIR__ . '/Library/Admin';
$_SERVER['LOLI']['LIBRARY']['Admin/'] = __DIR__ . '/Library/Admin';
$_SERVER['LOLI']['LIBRARY']['Model/Admin'] = __DIR__ . '/Model/Admin';
$_SERVER['LOLI']['LIBRARY']['Model/Admin/'] = __DIR__ . '/Model/Admin/';


Model::__reg('Admin');

