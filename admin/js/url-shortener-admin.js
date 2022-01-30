/**
 * URL Shortener Plugin
 */
(function( $ ) {
	'use strict';
	$(document).ready(function() {
		if ( typeof postboxes !== 'undefined' ) {
			var newCat, noSyncChecks = false, syncChecks, catAddAfter;

			$('#link_name').focus();
			// postboxes
			postboxes.add_postbox_toggles('short_link');

			// category tabs
			$('#category-tabs a').click(function(){
				var t = $(this).attr('href');
				$(this).parent().addClass('tabs').siblings('li').removeClass('tabs');
				$('.tabs-panel').hide();
				$(t).show();
				if ( '#categories-all' == t )
					deleteUserSetting('cats');
				else
					setUserSetting('cats','pop');
				return false;
			});
			if ( getUserSetting('cats') )
				$('#category-tabs a[href="#categories-pop"]').click();

			// Ajax Cat
			newCat = $('#newcat').one( 'focus', function() { $(this).val( '' ).removeClass( 'form-input-tip' ); } );
			$('#short-link-category-add-submit').click( function() { newCat.focus(); } );
			syncChecks = function() {
				if ( noSyncChecks )
					return;
				noSyncChecks = true;
				var th = $(this), c = th.is(':checked'), id = th.val().toString();
				$('#in-short-link-category-' + id + ', #in-popular-short_link_category-' + id).prop( 'checked', c );
				noSyncChecks = false;
			};

			catAddAfter = function( r, s ) {
				$(s.what + ' response_data', r).each( function() {
					var t = $($(this).text());
					t.find( 'label' ).each( function() {
						var th = $(this), val = th.find('input').val(), id = th.find('input')[0].id, name = $.trim( th.text() ), o;
						$('#' + id).change( syncChecks );
						o = $( '<option value="' +  parseInt( val, 10 ) + '"></option>' ).text( name );
					} );
				} );
			};

			$('#categorychecklist').wpList( {
				alt: '',
				what: 'short-link-category',
				response: 'category-ajax-response',
				addAfter: catAddAfter
			} );

			$('a[href="#categories-all"]').click(function(){deleteUserSetting('cats');});
			$('a[href="#categories-pop"]').click(function(){setUserSetting('cats','pop');});
			if ( 'pop' == getUserSetting('cats') )
				$('a[href="#categories-pop"]').click();

			$('#category-add-toggle').click( function() {
				$(this).parents('div:first').toggleClass( 'wp-hidden-children' );
				$('#category-tabs a[href="#categories-all"]').click();
				$('#newcategory').focus();
				return false;
			} );

			$('.categorychecklist :checkbox').change( syncChecks ).filter( ':checked' ).change();
		}

		if ( $('.shortlink-url-field').length ) {
			$('.shortlink-url-field-switch').click(function(event) {
		        event.preventDefault();
		        var $input = $(this).siblings('input');
		        var state = $input.data('state') ? $input.data('state') : 'url';
		        var $title = $(this).closest('div').siblings('.copy-action-title')
		        if ( state == 'url' ) {
		            $input.val($input.data('shortcode'));
		            $input.attr('title', $input.data('shortcodetitle'));
		            $title.text($input.data('shortcodetitle'));
		            $input.data('state', 'shortcode')
		        } else if ( state == 'shortcode' ) {
		            $input.val($input.data('html'));
		            $input.attr('title', $input.data('htmltitle'));
		            $title.text($input.data('htmltitle'));
		            $input.data('state', 'html')
		        } else if ( state == 'html' ) {
		            $input.val($input.data('url'));
		            $input.attr('title', $input.data('urltitle'));
		            $title.text($input.data('urltitle'));
		            $input.data('state', 'url')
		        }
		    });

		    new Clipboard('.shortlink-url-field-copy'); 
		    $('.shortlink-url-field-copy').click(function(event) {
		        var $this = $(this);
		        if ( ! $this.data('origtext') ) {
		            $this.data('origtext', $this.html());
		        }
		        $this.html('<span class="dashicons dashicons-yes"></span>');
		        setTimeout( function() { $this.html($this.data('origtext')); }, 1000 );
		        event.preventDefault();
		    });
		}
	});

})( jQuery );
