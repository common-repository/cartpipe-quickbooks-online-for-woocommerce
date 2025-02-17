jQuery( function ( $ ) {
	$( 'a.button.refresh' )
	.on( 'click', function() {
		
		 $('.wrap.qbo').block({
			message: null,
			overlayCSS: {
				background: '#fff url(' + cp_options.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
				opacity: 0.6
			}
		});
		
		var data = {
			action:    'cp_refresh_' + $(this).data('type'),
			security:  cp_options.refresh_nonce,
		};
// 
		$.post( cp_options.ajax_url, data, function( response ) {
				$( '.wrap.qbo' ).unblock();	  
				//console.log(response);
				location.reload();
		});

		return false;

	});
	$( 'a.button.import' )
	.on( 'click', function() {
		
		 $('.wrap.qbo').block({
			message: null,
			overlayCSS: {
				background: '#fff url(' + cp_options.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
				opacity: 0.6
			}
		});
		
		var data = {
			action:    'cp_import',
			security:  cp_options.refresh_nonce,
		};
// 
		$.post( cp_options.ajax_url, data, function( response ) {
				$( '.wrap.qbo' ).unblock();
				location.reload();
		});

		return false;

	});
	$( 'a.button.export' )
	.on( 'click', function() {
		
		 $('.wrap.qbo').block({
			message: null,
			overlayCSS: {
				background: '#fff url(' + cp_options.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
				opacity: 0.6
			}
		});
		
		var data = {
			action:    'cp_export',
			security:  cp_options.refresh_nonce,
		};
// 
		$.post( cp_options.ajax_url, data, function( response ) {
				$( '.wrap.qbo' ).unblock();
				location.reload();
		});

		return false;

	});
	$( 'a.button.generate' )
	.on( 'click', function() {
		
		 $('.wrap.qbo').block({
			message: null,
			overlayCSS: {
				background: '#fff url(' + cp_options.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
				opacity: 0.6
			}
		});
		
		var data = {
			action:    'cp_generate_pdf',
			security:  cp_options.refresh_nonce,
		};
// 
		$.post( cp_options.ajax_url, data, function( response ) {
				$( '.wrap.qbo' ).unblock();
				location.reload();
		});

		return false;

	});
	
	$( 'a.button.sync' )
	.on( 'click', function() {
		
		var data = {
			action:    'cp_sync_start',
			security:  cp_options.refresh_nonce,
		};
		$.post( cp_options.ajax_url, data, function( response ) {
			
			console.log(response);
		});
		
		return false;
	});
	$( 'a.button.deactivate' )
	.on( 'click', function() {
		$( '.wrap.qbo' ).block({
			message: null,
			overlayCSS: {
				background: '#fff url(' + cp_options.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
				opacity: 0.6
			}
		});
		var data = {
			action:    'cp_deactivate_license',
			security:  cp_options.refresh_nonce,
		};
		$.post( cp_options.ajax_url, data, function( response ) {
			console.log(response);
			$( '.wrap.qbo' ).unblock();	  
			window.location.reload();
			
		});
		
		return false;
	});
	$( 'a.button.activate' )
	.on( 'click', function() {
		$( '.wrap.qbo' ).block({
			message: null,
			overlayCSS: {
				background: '#fff url(' + cp_options.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
				opacity: 0.6
			}
		});
		var data = {
			action:    'cp_activate_license',
			security:  cp_options.refresh_nonce,
		};
		$.post( cp_options.ajax_url, data, function( response ) {
			//console.log(response);
			$( '.wrap.qbo' ).unblock();	  
			window.location.reload();
			
		});
		
		return false;
	});
	$( 'i.fa-plus-circle' ).on( 'click', function(){
		$(this).next( 'span.create-account-wrapper').toggle('slow');
		return false;
	});
	$('a.create-account').on( 'click', function(){
		$( '.wrap.qbo' ).block({
			message: null,
			overlayCSS: {
				background: '#fff url(' + cp_options.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
				opacity: 0.6
			}
		});
		var data = {
			action:    'cp_create_account',
			security:  cp_options.refresh_nonce,
			type:		$(this).prev('input.auto-create-field').data('account'),
			name:		$(this).prev('input.auto-create-field').val(),
			
		};
		$.post( cp_options.ajax_url, data, function( response ) {
			$( '.wrap.qbo' ).unblock();	  
			window.location.reload();
			//console.log(response);
		});
		return false;
	});
	$( 'a.cp-recheck-license' )
	.on( 'click', function() {
		$( '.wrap.qbo' ).block({
			message: null,
			overlayCSS: {
				background: '#fff url(' + cp_options.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
				opacity: 0.6
			}
		});
		var data = {
			action:    'cp_recheck_license',
			security:  cp_options.refresh_nonce,
		};
		$.post( cp_options.ajax_url, data, function( response ) {
			$( '.wrap.qbo' ).unblock();	  
			//window.location.reload();
			console.log(response);
		});
		
		return false;
	});
	$('a.message-trigger').on('click', function(){
		$('.product-import-result').toggle('slow');
	});
	$('.cp-dismiss a').on('click', function(){
		$this = $(this).parent().parent(); 
		var data = {
			action:    'cp_hide_messages',
			security:  cp_options.refresh_nonce,
		};
		$.post( cp_options.ajax_url, data, function( response ) {
		
			$this.hide();
		});
	});
});