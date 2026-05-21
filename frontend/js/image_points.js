(function($){

	function imagePointsInit(){
		$('.image_points_hastooltip').each(function(){
			$(this).data('powertip', function() {
				var htmlThis = $(this).parents('.image_points_tooltip_html').attr('data-html');
				return htmlThis;
			});
			var thisPlace = $(this).parents('.image_points_tooltip_html').data('placement');
			$(this).powerTip({
				placement: thisPlace,
				smartPlacement: true,
				mouseOnToPopup: true,
			}).on({
				powerTipClose: function() {
					$('#powerTip').html('');
				}
			});
		});
	}

	$('body').on('click','.close_image_points',function () {
		$.powerTip.hide();
	});

	$(document).ready(function(){
		imagePointsInit();
    });

	/* Fix #6: Hide tooltip on resize/rotate to prevent stale position */
	var resizeTimer;
	$(window).on('resize orientationchange', function(){
		clearTimeout(resizeTimer);
		resizeTimer = setTimeout(function(){
			if (typeof $.powerTip !== 'undefined') {
				$.powerTip.hide();
			}
		}, 150);
	});

	let firstLoad = true;
	function scroll_element(){
		let $top = $(window).scrollTop();
		if( $top >= 100 && firstLoad){
			imagePointsInit();
			firstLoad = false;
		}
	}

	$(window).scroll(function(){
		scroll_element();
	});

	$(window).bind('touchmove', function(e) {
		scroll_element();
	});

})(jQuery);