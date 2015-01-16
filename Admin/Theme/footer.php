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
/* ************************************************************************** */
 ?>
		</div>
	</div>
	<div class="clear"></div>
</div>
<div id="footer">
<p id="footer-left" class="left"><?php echo $this->__( ['theme.by', 'version' => \loli\VERSION, 'name' => \loli\NAME, 'date'=>\loli\DATE, 'url' => \loli\URL, 'by' => \loli\BY]); ?></p>
<p id="footer-right" class="right"><?php echo $this->__( ['theme.info', 'memory' => load_memory(3) , 'db' => load_db(), 'time' => load_time(3)]); ?></p>
<div class="clear"></div>
</div>
</div>
<?php $this->do_footer(); ?>

</body>
</html>