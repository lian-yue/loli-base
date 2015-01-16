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
/*	Updated: UTC 2014-11-05 12:55:10
/*
/* ************************************************************************** */  ?>
<!DOCTYPE html>
<html dir="ltr" lang="<?php echo $this->lang->current; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?php echo $this->title(); ?></title>
<?php $this->do_header(); ?>
</head>
<body>
<div id="login">
		<?php

		$form['h1'] = ['title' => '<h1>' . $this->__('login') . '</h1>'];
		$form['lang'] = [ 'title' => $this->__('lang'), 'value' => ['type' => 'select', 'name' => 'lang', 'id' => 'lang', 'value' => $this->lang->current, 'option' => $this->lang->all]];
		$form = get_call('admin.login', $form, $this);
		$form['submit'] = ['title' => '', 'value' => ['type' => 'submit', 'name' => 'submit', 'class' => 'ajax', 'value' => $this->__('login.submit' )]];
		$this->form( $form );
		?>
</div>
<!--<?php echo load_memory(3) , '---' , load_db(), '---', load_time(3) ?>-->
</body>
</html>