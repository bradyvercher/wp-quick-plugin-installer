jQuery(function($) {
	$('#the-list').on('click', 'a.install-now', function(e) {
		var $self = $(this),
			$spinner = $self.next('.spinner'),
			request = {},
			queryVars;

		e.preventDefault();

		$spinner.css( 'display', 'inline-block' );

		queryVars = this.href.split('?')[1].split('&');
		$.each( queryVars, function( i, queryVar ) {
			var pair = queryVar.split('=');
			request[ pair[0] ] = pair[1];
		});

		request.action = 'qpi-' + request.action;

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: request,
			success : function( response ) {
				if ( response.success && 'data' in response && 'activateLink' in response.data ) {
					$self.after( response.data.activateLink ).remove();
				} else if ( response.success ) {
					$self.after( QPI.activated ).remove();
				} else {
					// @todo Better error reporting.
					$self.after( '<span style="color: #ee0000">' + QPI.error + '</span>' ).remove();
				}

				$spinner.hide();
			},
			error: function() {
				$self.after( '<span style="color: #ee0000">' + QPI.error + '</span>' ).remove();
			}
		});
	}).find('a.install-now').after('<span class="spinner" style="float: none; margin-top: 0; vertical-align: text-top"></span>');
});