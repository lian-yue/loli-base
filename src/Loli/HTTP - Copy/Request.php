<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-06 14:16:56
/*	Updated: UTC 2015-03-27 06:42:06
/*
/* ************************************************************************** */
namespace Loli\HTTP;
class Request{
	const TOKEN_HEADER = 'X-Token';

	const TOKEN_COOKIE = 'token';

	const AJAX_HEADER = 'X-Ajax';

	const AJAX_PARAM = 'ajax';

	const PJAX_HEADER = 'X-Pjax';

	private $_schemes = ['http', 'https'];
	private $_methods = ['OPTIONS', 'HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
	private $_defaultHost = 'localhost';
	private $_postLength = 2097152;
	private $_content = 'php://input';
	private $_newToken = false;
	private $_token = NULL;
	private $_ajax = NULL;
	private $_pjax = NULL;


}




