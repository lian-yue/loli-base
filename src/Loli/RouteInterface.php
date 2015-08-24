<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-08-22 11:05:37
/*
/* ************************************************************************** */
namespace Loli;
class_exists('Loli\Route') || exit;
interface RouteInterface{
    public function route(Route &$route);
}