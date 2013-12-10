(function($) {

	// WPW get vals
	$.fn.wpw_vals = function() {
		$this = this;

		// Build string
		var str = '';
		$.each($this, function(k, v) {
			str += $(v).val() + ',';
		});

		// remove last comma
		if(str != '') {
			str = str.substring(0, (str.length - 1));
		}

		return str;

	};

	// WPW admin jQuery extension
	$.fn.wpw_admin = function() {
		var $this = this;

		this.save_post = function() {

			$.post(
					ajaxurl,
					{
						action     : 'wpw_save_settings',
						ajax_nonce : $this.find('input.ajax_nonce').val(),
						post_type  : $this.find('input.post_type').val(),
						enabled    : $this.find('.wpw_enabled').is(':checked'),
						fields     : $this.find('.wpw_fields:checked').wpw_vals(),
						custom     : $this.find('.wpw_custom:checked').wpw_vals()
					},
					function(response) {
					}
			);
		};

		// init method
		this.init = function() {
			$this.find('input#submit').click(function() {
				$this.save_post();
			});
		};

		// init
		this.init();

	};


	// Bind elements on window load
	$(window).load(function() {
		$.each($('#wpw-wrap').find('dd'), function(k, v) {
			$(v).wpw_admin();
		});
	});


})(jQuery);


