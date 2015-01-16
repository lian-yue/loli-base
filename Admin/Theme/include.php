<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-04-12 17:59:42
/*	Updated: UTC 2015-01-16 16:07:10
/*
/* ************************************************************************** */
use Loli\Static_;


// 添加 js css
$this->Script->add('jquery', Static_::url('/scripts/jquery.js'), ['priority' => 5, 'login' => false]);
$this->Script->add('jquery.ui', Static_::url('/scripts/jquery.ui.js'), ['priority' => 6, 'parent' => 'jquery', 'login' => false]);
$this->Script->add('jquery.plugin', Static_::url('/scripts/jquery.plugin.js'), ['priority' => 6, 'parent' => 'jquery', 'login' => false]);



$this->Style->add('theme', __DIR__ . '/static/styles.css', ['call' => true, 'priority' => 6, 'login' => false]);
$this->Script->add('theme', __DIR__ . '/static/scripts.js', ['call' => true, 'priority' => 6, 'login' => false]);

