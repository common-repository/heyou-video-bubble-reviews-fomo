(function( $ ) {
	'use strict';

	$( document ).ready(function() {

		setTimeout(function() {
			$('body #heyou-active-videos').DataTable( {
				"order": [[ 2, "desc" ]]
			});
		}, 100 );

		function debounce(func, timeout = 300) {
			let timer;
			return (...args) => {
			  clearTimeout(timer);
			  timer = setTimeout(() => { func.apply(this, args); }, timeout);
			};
		}

		$('body #heyou-stats-filter').on('change', function() {
			if($(this).val() == 'custom-date') {
				$("body .heyou-sortby .custom-date-wrap").show();
			} else {
				$("body .heyou-sortby .custom-date-wrap").hide();
			}

			if($(this).val() == 'custom-period') {
				$("body .heyou-sortby .custom-period-wrap").show();
			} else {
				$("body .heyou-sortby .custom-period-wrap").hide();
			}
		});
		

	});

})( jQuery );
