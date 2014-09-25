jQuery(function($){

	$(document).on('click', '.commentator-add-comment', function(e){
		var $t = $(this),
			$form = $t.parents('.commentator-form');
		if($t.find('i').hasClass('commentator-icon-spin')){
			return false;
		}
		$t.find('i').addClass('commentator-icon-spin');
		$('.commentator-alert').remove();
		$.post(ajaxurl, $form.serialize()+"&comment="+$form.find('.commentator-textarea').text()+"&action=commentator_add-comment", function(data, status, xhr){
			
			var ct = xhr.getResponseHeader("content-type") || "";
			if (ct.indexOf('html') > -1) {
				$.addAlert(data, "error");
			}
			if (ct.indexOf('json') > -1) {
				if(data.message){
					$.addAlert(data.message, "message");
				}
				else{
					$form.find('.commentator-textarea').empty();
					$('#commentator-sort').find('[data-commentator-sort="desc"]').click();
				}
			}
			
			$t.find('i').removeClass('commentator-icon-spin');
		});
		e.preventDefault();
	});

	$(document).on('click', '.commentator-login', function(e){
		var $t = $(this),
			$form = $t.parents('.commentator-login-form');
		$('.commentator-alert').remove();
		$.post(ajaxurl, $form.serialize()+"&action=commentator_login", function(data){
			if(data.message){
				$.addAlert(data.message, "message");
			}
			if(data.errors){
				$.each(data.errors, function(i, v){
					$.addAlert(v, "error");
				});
			}
			else{
				location.reload();
			}
		});
		e.preventDefault();
	});

	$(document).on('click', '.commentator-register', function(e){
		var $t = $(this),
			$form = $t.parents('.commentator-register-form');
		$('.commentator-alert').remove();
		$.post(ajaxurl, $form.serialize()+"&action=commentator_register", function(data){
			if(data.message){
				$.addAlert(data.message, "message");
			}
			if(data.errors){
				$.each(data.errors, function(i, v){
					$.addAlert(v, "error");
				});
			}
			else{
				$form.parent().empty().html("<p>"+data.message+"</p>");
			}
		});
		e.preventDefault();
	});

	$(document).on('click', '.commentator-flag', function(e){
		var $t = $(this),
			$comment = $t.parents('.comment').first();
		var id = $comment.attr('id').replace('comment-', '');
		$('.commentator-alert').remove();
		$.post(ajaxurl, "comment_ID="+id+"&action=commentator_flag", function(data){
			if(data.message){
				$.addAlert(data.message, "message");
			}
			if(data.errors){
				$.each(data.errors, function(i, v){
					$.addAlert(v, "error");
				});
			}
			else{
				$('#commentator-sort').find('.commentator-active').find('a').click();
			}
		});
		e.preventDefault();
	});

	$(document).on('click', '.commentator-reply', function(e){
		var $t = $(this),
			$form = $('#commentator-form').find('.commentator-form'),
			$comment = $t.parents('.comment').first();
		var id = $comment.attr('id').replace('comment-', '');
		$('.commentator-alert').remove();
		if($('#commentator-cloned-form').length){
			$('#commentator-cloned-form').remove();
		}
		$form.clone(true).attr('id', 'commentator-cloned-form').insertAfter($comment.find('.commentator-comment-content').first()).find('#comment_parent').val(id);
		$('#commentator-cloned-form').prepend('<a class="commentator-close-reply-form"><i class="commentator-icon-remove-circle"></i></a>');
		e.preventDefault();
	});


	$(document).on('click', '.commentator-close-reply-form', function(e){
		if($('#commentator-cloned-form').length){
			$('#commentator-cloned-form').remove();
		}
		e.preventDefault();
	});


	$(document).on('click', '.commentator-thread-likes-toggle', function(e){
		var $t = $(this),
			$parent = $t.parent('.commentator-thread-likes'),
			$form = $('#commentator-form').find('.commentator-form'),
			data = "comment_post_ID="+$('#commentator-form').find('#comment_post_ID').val()+"&action=commentator_vote-thread";
		$('.commentator-alert').remove();
		$.post(ajaxurl, data, function(data){
			if(data.message){
				$.addAlert(data.message, "message");
			}
			if(data.errors){
				$.each(data.errors, function(i, v){
					$.addAlert(v, "error");
				});
			}
			else{
				if(data.hasVoted)
					$parent.addClass('commentator-active');
				else
					$parent.removeClass('commentator-active');
				$t.find('.commentator-counter').text(data.count);
			}
		});
		e.preventDefault();
	});

	$(document).on('click', '.commentator-vote-up', function(e){
		var $t = $(this),
			$form = $('#commentator-form').find('.commentator-form'),
			data = "comment_ID="+$t.data('comment-id')+"&multiplicator=1&action=commentator_vote-comment";
		$('.commentator-alert').remove();
		$t.find('i').addClass('commentator-icon-spin');
		$.post(ajaxurl, data, function(data){
			if(data.message){
				$.addAlert(data.message, "message");
			}
			if(data.errors){
				$.each(data.errors, function(i, v){
					$.addAlert(v, "error");
				});
			}
			else{
				if(data.hasVoted){
					$t.addClass('commentator-active');
				}
				else{
					$t.removeClass('commentator-active');
				}
				$t.find('span').text(data.count);

				if(data.hasVotedOpposite){
					$t.parent().find('.commentator-vote-down').addClass('commentator-active');
				}
				else{
					$t.parent().find('.commentator-vote-down').removeClass('commentator-active');
				}
				$t.parent().find('.commentator-vote-down').find('span').text(data.countOpposite);
			}
			$t.find('i').removeClass('commentator-icon-spin');
		});
		e.preventDefault();
	});

	$(document).on('click', '.commentator-vote-down', function(e){
		var $t = $(this),
			$form = $('#commentator-form').find('.commentator-form'),
			data = "comment_ID="+$t.data('comment-id')+"&multiplicator=-1&action=commentator_vote-comment";
		$('.commentator-alert').remove();
		$t.find('i').addClass('commentator-icon-spin');
		$.post(ajaxurl, data, function(data){
			if(data.message){
				$.addAlert(data.message, "message");
			}
			if(data.errors){
				$.each(data.errors, function(i, v){
					$.addAlert(v, "error");
				});
			}
			else{
				if(data.hasVoted){
					$t.addClass('commentator-active');
				}
				else{
					$t.removeClass('commentator-active');
				}
				$t.find('span').text(data.count);

				if(data.hasVotedOpposite){
					$t.parent().find('.commentator-vote-up').addClass('commentator-active');
				}
				else{
					$t.parent().find('.commentator-vote-up').removeClass('commentator-active');
				}
				$t.parent().find('.commentator-vote-up').find('span').text(data.countOpposite);
			}
			$t.find('i').removeClass('commentator-icon-spin');
		});
		e.preventDefault();
	});

	$(document).on('click', '.commentator-sort', function(e){
		var $t = $(this),
			$current =  $('#comments').find('.page-numbers.current').first(),
			page = $current.attr('href') ? $current.attr('href').substring(1) : $current.text(),
			data = "comment_post_ID="+$('#commentator-form').find('#comment_post_ID').val()+"&sort="+$t.data('commentator-sort')+"&comment_page="+page+"&action=commentator_sort-comments";
		$t.parent().parent().find('.commentator-active').removeClass('commentator-active');
		$t.parent().addClass('commentator-active');
		$('#commentator-comments-list').empty().addClass('commentator-icon-spin');
		$.post(ajaxurl, data, function(data){
			$('#commentator-comments-list').html(data).removeClass('commentator-icon-spin');
			$('#commentator-pagination').html($('#commentator-new-pagination-container').html());
			$('#commentator-new-pagination-container').remove();
		});
		e.preventDefault();
	});

	$(document).on('click', '.commentator-logout', function(e){
		var $t = $(this);
		$('.commentator-alert').remove();
		$.post(ajaxurl, "action=commentator_logout", function(data){
			if(data.message){
				$.addAlert(data.message, "message");
			}
			if(data.errors){
				$.each(data.errors, function(i, v){
					$.addAlert(v, "error");
				});
			}
			else{
				location.reload();
			}
		});
		e.preventDefault();
	});

	$(document).on('click', '.commentator-close', function(e){
		var $t = $(this),
			$alert = $t.parent();
		$alert.remove();
		e.preventDefault();
	});

	$(document).on('click', '.commentator-toggle-visibility', function(e){
		var $t = $(this),
			$comment = $($t.attr('href'));
		$comment.toggleClass('commentator-collapsed');
		e.preventDefault();
	});

	$(document).on('click', function(e){
		$('.commentator-dropdown').removeClass('commentator-open');
	});

	$(document).on('click', '.commentator-dropdown-toggle', function(e){
		var $t = $(this);
		$('.commentator-dropdown').not($t.parent()).removeClass('commentator-open');
		$t.parent().toggleClass('commentator-open');
		return false;
	});

	$(document).on('click', '.commentator-dropdown-menu', function(e){
		e.stopPropagation();
	});

	$(document).on('click', '.commentator-social-login-button', function(e){
		window.open(
			ajaxurl+"?action=commentator_social_signin&provider="+$(this).data('provider'),
			"_blank",
			"toolbar=no, scrollbars=no, menubar=no, status=no, titlebar=no, width=500, height=350"
		);
		e.preventDefault();
	});

	$('body').append('<div id="commentator-alert-container"></div>');

	$('#comments').on('click', '.page-numbers', function(e){
		console.log("kdjdkjd");
		var $t = $(this);
		$t.parent().find('.current').removeClass('current');
		$t.addClass('current');
		$('#commentator-sort').find('.commentator-active').find('.commentator-sort').click();
		e.preventDefault();
	});

	$.addAlert = function(content, alertClass){
		$(	'<div class="commentator-alert commentator-'+alertClass+'"><a class="commentator-close" href="#">×</a>'+
    			'<span>'+content+'</span>'+
    		'</div>').prependTo('#commentator-alert-container');
	};


});