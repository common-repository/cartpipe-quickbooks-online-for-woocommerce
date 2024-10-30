jQuery( function ( $ ) {
	$('[data-dependency]').each(function(){
		var dependency 	= $(this).data('dependency');
		var value 		= $(this).data('value');
		var c_value 	= $('[name="' + dependency + '"]').val();
		
		if(value == c_value){
			$(this).show();
		}else{
			$(this).hide();
		}
		
	});
	$('select[name="qbo[order_type]"]').on('change', function() {
		$('[data-dependency]').each(function(){
			var dependency 	= $(this).data('dependency');
			
			if(dependency == 'qbo[order_type]'){//(this).attr('name')){
				 var value 		= $(this).data('value');
				 var c_value 	= $('[name="' + dependency + '"]').val();
				 
				 if(value == c_value){
					$(this).show('slow');
				}else{
					$(this).hide('slow');
				}
			}
		});
  		
	});
	$( '#qbo-order-data' )
	.on( 'click', 'a.transfer-to.button', function() {
		$( '#qbo-order-data' ).block({
			message: null,
			overlayCSS: {
				background: '#fff url(' + cp_order_meta_box.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
				opacity: 0.6
			}
		});

		var data = {
			action:    'cp_transfer_single_order',
			post_id:   cp_order_meta_box.post_id,
			security:  cp_order_meta_box.transfer_order_nonce,
		};

		$.post( cp_order_meta_box.ajax_url, data, function( response ) {
			$( '#qbo-order-data' ).unblock();
			window.location.reload();
		});

		
		return false;
	});
	$( '#qbo-order-data' )
	.on( 'click', 'a.transfer-resend.button', function() {
		$( '#qbo-order-data' ).block({
			message: null,
			overlayCSS: {
				background: '#fff url(' + cp_order_meta_box.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
				opacity: 0.6
			}
		});

		var data = {
			action:    'cp_resend_order_qbo',
			post_id:   cp_order_meta_box.post_id,
			security:  cp_order_meta_box.transfer_order_nonce,
		};

		$.post( cp_order_meta_box.ajax_url, data, function( response ) {
			//$( '#qbo-order-data' ).unblock();
			window.location.reload();
			
		});

		
		return false;
	});
	$('a.button.transfer').on('click', function(e) {
		var url 	= $(this).attr('href');
		var vars 	= [], hash;
		$this 		= $(this);
		$(this).closest('table').block({
			message: null,
			overlayCSS: {
				background: '#fff url(' + cp_order_meta_box.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
				opacity: 0.6
			}
		});
		if(url){
			var parent 	= $(this).parent().parent().parent(); 
			    var q 	= url.split('?')[1];
			    if(	q != undefined	){
			        q = q.split('&');
			        for(var i = 0; i < q.length; i++){
			            hash = q[i].split('=');
			            vars.push(hash[1]);
			            vars[hash[0]] = hash[1];
			        }
			}
			if(vars['sent']){
				var data = {
				action:	    vars['action'],
				message:   'Order #' + vars['order_id'] + ' has already been sent to QuickBooks.',
				security:	vars['_wpnonce'],
				};
				$.post( cp_order_meta_box.ajax_url, data, function( response ) {
					//console.log(vars['action']); 
					window.location.reload();
				});	
			}else{
				parent.toggleClass("queued");
				var data = {
					action:	    vars['action'],
					order_id:   vars['order_id'],
					security:	vars['_wpnonce'],
				};
				$.post( cp_order_meta_box.ajax_url, data, function( response ) {
					//console.log(response); 
					parent.toggleClass("queued");
					window.location.reload();
				});
			}
		}else{
			
		}
		return false;
	});
	$( '#qbo-order-data' )
	.on( 'change', '#qb_resend', function() {
		
		if ($(this).attr("checked")) {
			$('a.transfer-resend').removeClass('hide');
			$('a.transfer-resend').show('slow');
			
		}else{
			$('a.transfer-resend').hide('slow');
			
		}
		return false;
	});
	$('i.cp-logo').each(function(){
		$(this).insertBefore('#qbo-order-data h3.hndle span:first-child');
	});
	$('input.hidden').each(function(){
		$(this).parent().parent().addClass('hidden');
	});
	$('input[name="cp[free_trial]"]').on('change', function(){
		//if($(this).is(':checked')){
			$('table.form-table tr').each(function(){
				if($(this).hasClass('hidden')){
					$(this).removeClass('hidden');	
				}else{
					$(this).addClass('hidden');
				}
			});
		//}
		if($(this).is(':checked')){
			$('.cp-setup-actions.step input.button-primary').addClass('signup');
		}
	});
	//Validate Email Address
	
	$('.cp-setup').on('click', 'input.signup', function(){
		$('span.alert').each(function(){
			$(this).remove();
		});
		var email		=	$('input[name="cp[email_address]"]');
		var password	=	$('input[name="cp[password]"]');
		var re 			= 	/^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;
		var is_email	= 	re.test(email.val());
		
		if(is_email){
			re = /\S+/;
			var has_password	= re.test(password.val());
			if( has_password ){
				email.removeClass("invalid").addClass("valid");
				$('.cp-setup-actions.step input.signup').attr('disabled', true);
				$('.status-updates').css({
					'height': ( $('table.form-table').outerHeight() + $('.table-wrapper fieldset').outerHeight() + $('.table-wrapper fieldset').outerHeight() )   + 'px',
					'display':'block'
				});
				$('.status-updates .current-step .desc').text( 'Contacting Cartpipe' ).addClass('loading');
				var data = {
					action:    'cp_signup_account',
					email:   	$('input[name="cp[email_address]"]').val(),
					password:   $('input[name="cp[password]"]').val(),
					security:  	cp_setup.setup_nonce,
				};
				$.post( cp_setup.ajax_url, data, function( response ) {
					//Account Success
					var obj = $.parseJSON( response );
					
					if(obj.user){
						//Message
						$('.status-updates .current-step').each(function(){
							$('.status-updates ul.completed-steps').append('<li class="success"><i class="fa fa-check-square-o"></i>'+ $(this).text() +'</li>');
						});
						$('.status-updates .current-step .desc').text( obj.status ).addClass('loading');
						var data = {
							action:    'cp_signup_payment',
							security:  	cp_setup.setup_nonce,
						};
						$.post( cp_setup.ajax_url, data, function( response ) {
							var obj = $.parseJSON( response );
							
							if(obj.account){
								var data = {
									action:    'cp_signup_license',
									security:  	cp_setup.setup_nonce,
								};
								$('.status-updates .current-step').each(function(){
									$('.status-updates ul.completed-steps').append('<li class="success"><i class="fa fa-check-square-o"></i>'+ $(this).text() +'</li>');
								});
								$('.status-updates .current-step .desc').text( obj.status ).addClass('loading');
								$.post( cp_setup.ajax_url, data, function( response ) {
									
									var obj = $.parseJSON( response );
									
									if(obj.license){
										$('.status-updates .current-step').each(function(){
											$('.status-updates ul.completed-steps').append('<li class="success"><i class="fa fa-check-square-o"></i>'+ $(this).text() +'</li>');
										});
										$('input[name="qbo[consumer_key]"]').val( obj.consumer_key );
										$('input[name="qbo[consumer_secret]"]').val( obj.consumer_secret );
										$('input[name="qbo[license]"]').val( obj.license );
										//activate license
										var data = {
											action:    'cp_activate_free_trial',
											security:  	cp_setup.setup_nonce,
										};
										
										$('.status-updates .current-step .desc').text( 'Activating License' ).addClass('loading');
										$.post( cp_setup.ajax_url, data, function( response ) {
											
											$('.cp-setup-actions.step').prepend( '<h3 class="success">You\'re connected! Click the button to continue with the setup process. Check your email for copies of your credentials.</h3>' );
											$('.cp-setup-actions.step input.signup').attr('disabled', false);
											$('.status-updates').css({
												'height': 0,
												'display':'none'
											});
											$('table.form-table tr').each(function(){
												if($(this).hasClass('hidden')){
													$(this).removeClass('hidden');	
												}else{
													$(this).addClass('hidden');
												}
											});
											$('.table-wrapper fieldset').remove();
											$('.cp-setup-actions.step input.button-primary').removeClass('signup');
										});
									}
								});
							}
						});
					}
				});
			}else{
				$('<span class="alert">' + cp_setup.password_alert + '</div>').insertAfter( password );
				password.removeClass("valid").addClass("invalid");
			}
		}else{
			re = /\S+/;
			var has_password	= re.test(password.val());
			if(!has_password){
				$('<span class="alert">' + cp_setup.password_alert + '</div>').insertAfter( password );
				password.removeClass("valid").addClass("invalid");
			}
			$('<span class="alert">' + cp_setup.email_alert + '</div>').insertAfter( email );
			email.removeClass("valid").addClass("invalid");
		}
		return false;
	});
	
});