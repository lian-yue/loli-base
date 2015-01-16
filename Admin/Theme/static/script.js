$.alert = function(msg, call) {
	var v = null;
	if (typeof(d) == 'undefined') {
      	d = $("<div></div>");
		d.dialog({
		 	autoOpen: false,
		 	title: '<?php echo $this->admin->__('js.dialog_messages') ?>',
			closeText: '<?php echo $this->admin->__('js.dialog_close') ?>',
			resizable: false,
			draggable: false,
			modal: true,
			buttons: [
				{
					'class': 'button confirm',
					click: function(){
						v = true;
						$(this).dialog("close");
					},
					text: '<?php echo $this->admin->__('js.dialog_confirm') ?>'
				}
			],
			close: function(){
				call && call.apply(this,[v]);
			},
			dragStart : function(){
				//b._size();
				//$.log(this);
				$.log(this );
				$.log(arguments );
			}
	    });
	}
	v = null;
	d.html(msg);
	d.dialog("open");
	return true;
};



$.confirm = function(msg, call) {
	var v = null;
	if (typeof(d) == 'undefined') {
      	d = $("<div></div>");
		d.dialog({
		 	autoOpen: false,
		 	title: '<?php echo $this->admin->__('js.dialog_messages') ?>',
			closeText: '<?php echo $this->admin->__('js.dialog_close') ?>',
			resizable: false,
			draggable: false,
			modal: true,
			buttons: [
				{
					'class': 'button confirm',
					click: function(){
						v = true;
						$(this).dialog("close");
					},
					text:  '<?php echo $this->admin->__('js.dialog_confirm') ?>'
				},
				{
					'class': 'button cancel',
					click: function(){
						v = false;
						$(this).dialog("close");
					},
					text:  '<?php echo $this->admin->__('js.dialog_cancel') ?>'
				}
			],
			close: function(){
				call && call.apply(this,[v]);
			}
	    });
	}
	v = null;
	d.html(msg);
	d.dialog("open");
	return true;
};


// 登录选择语言
$('#login #lang').change(function(){
	var a = window.location.href;
	if ( a.match(/^([^?]+\?|.+\&)lang(\=[^&]+)?/) ) {
		a = a.replace(/^([^?]+\?|.+\&)lang(\=[^&]+)?/, '$1lang=' + $(this).val() );
	} else {
		a += ( a.indexOf('?') < 1 ? '?': '&' ) + 'lang=' + $(this).val();
	}
	window.location = a;
});



$("#nav li").each(function() {
	if ( !$(this).find('ul').size() ) {
		return;
	}
	span = $('<span class="switch"></span>').addClass($(this).find('ul').is(':hidden') ? 'close' : 'open');
	span.click(function() {
		var a = $(this).next(), b = a.next();
		if( b.is(":hidden") ) {
			$(this).addClass("open").removeClass('close');
			b.slideDown(300);
		} else {
			$(this).addClass("close").removeClass('open');
			b.slideUp(300);
		}
	});
	$(this).prepend(span);
});


// 全选
$("#lists .handle-all").click(function() {
	var checked = this.checked;
	$("#lists .handle-checkbox").each(function(){
		this.disabled || ( this.checked = checked );
	});
 });


// 是否全部选择
$("#lists .handle-checkbox").change(function() {
	$("#lists .handle-all").prop('checked', $("#lists .handle-checkbox:enabled:checked").size() == $(".wrap .handle-checkbox:enabled").size() );
});

//用鼠标选
$(function($){
	var event, marquee = false, checkbox = $("#lists .handle-checkbox:input:checkbox:enabled"), handle = $("#lists .handle-checkbox"), mousemove = function(ee) {
		marquee.css({ left : Math.min( ee.pageX, event.pageX), top : Math.min( ee.pageY, event.pageY), width: Math.abs(ee.pageX - event.pageX), height: Math.abs(ee.pageY - event.pageY) });
		var r = marquee.offset();
		r.right = r.left + marquee.outerWidth();
		r.bottom = r.top + marquee.outerHeight();

		checkbox.each(function() {
			if ( !this.disabled ) {
				var s = $(this).offset();
				s.right = s.left + $(this).outerWidth();
				s.bottom = s.top + $(this).outerHeight();
				var checked = this.checked;
				if ( Math.min(s.bottom, r.bottom) > Math.max(s.top, r.top)+3 &&  Math.min(s.right, r.right) > Math.max(s.left, r.left)+3) {
					this.checked = event.shiftKey ? true : !this.__checked;
					if ( event.shiftKey && this.__checked ) {
						this.__checked = false;
					}
				} else {
					this.checked = event.shiftKey && !this.__checked ? false : this.__checked;
				}
				checked == this.checked || handle.change();
			}
		});
	}, mouseup = function(ee) {
		$('body').css({'cursor':'auto', '-moz-user-select':'auto'});
		$(document).unbind('mousemove', mousemove).unbind('mouseup', mouseup);
		document.onselectstart = null;
		marquee.remove();
		marquee = false;
	};

	$('#wrapper').mousedown(function(e) {
		if ( marquee || ( !e.shiftKey && !e.ctrlKey && !e.metaKey ) || $.inArray( e.target ? e.target.tagName : e.srcElement.tagName , ['TEXTAREA', 'INPUT', 'BUTTON', 'SELECT', 'A'] ) != -1 || !$('#lists .handle-all').size() ) {
			return;
		}
		marquee = true;
		// 有选取的也过滤掉
		if ( window.getSelection ) {
			if ( window.getSelection() != '' ) {
				marquee = false;
				return;
			}
		} else if ( document.getSelection ) {
			if (document.getSelection() != '' ) {
				marquee = false;
				return;
			}
		} else if ( document.selection ) {
			if ( document.selection.createRange().text != '' ) {
				marquee = false;
				return;
			}
		}
		event = e;
		$.log(1);
		marquee  = $( '<div id="handle-float" style="outline: none; position: absolute;z-index: 10;" ></div>');
		checkbox.each(function(){
			this.__checked = this.checked;
		});
		$('body').prepend(marquee).css({'cursor':'default', '-moz-user-select':'none'});
		marquee.css({ left : e.pageX, top : e.pageY,height : 0, width : 0 });
		document.onselectstart = function () { return false; };
		$(document).mousemove(mousemove).mouseup(mouseup);

	});
});



// 批量操作提示
lists_form = false;
$("#lists .lists-form").submit(function() {
	var c = this;
	if ($("#form-handle").val() == -1) {
		return false;
	}
	if (lists_form) {
		return true;
	}
	$.confirm('<?php echo $this->admin->__('js.dialog_confirm_content') ?>'.replace(/\$1/g, $("#form-handle").find("option:selected").text()), function(v){
		lists_form = v;
		if (lists_form == true) {
			$(c).submit();
		}
	});
	return false;
});

// 无法点击的
$( '.wrap .no-click' ).click(function() {
	return false;
});


// 需要提示的连接
$(".wrap a.confirm").click(function() {
	var _this = $(this);
	$.confirm('<?php echo $this->admin->__('js.dialog_confirm_content') ?>'.replace(/\$1/g, _this.text()), function(v){
		if ( v == true ) {
			window.location.href = _this.attr('href');
		}
	});
	return false;
});

// ajax 提交表单
$('#form').formajax(function( d ){
	if (d.err) {
		return $.alert(d.err);
	} else {
		window.location = d.to;
	}
});


// 搜索lists
if ( $('#form-handle').size() ) {
	$('#search').css( {left : $('#form-handle').outerWidth(true) + $('#form-handle-submit').outerWidth(true), bottom: $('#form-handle').outerHeight() } );
}

$('#form-search-select').change(function(){
	$('#form-search-value').attr('name', this.value);
});
$('#form-search-select').change();