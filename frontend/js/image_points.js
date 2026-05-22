/**
 * Image Points frontend tooltip handler.
 *
 * Reads HTML content from data-html attributes and displays
 * tooltips using a custom lightweight implementation that
 * doesn't depend on PowerTip's fadeIn (which can be blocked
 * by theme CSS or optimization plugins).
 */
(function ($) {

	var $tooltip = null;
	var activeTarget = null;

	/**
	 * Create or return the shared tooltip container.
	 */
	function getTooltip() {
		if (!$tooltip || !$tooltip.length) {
			$tooltip = $('#imagePointsTip');
			if (!$tooltip.length) {
				$tooltip = $('<div id="imagePointsTip"></div>').appendTo('body');
			}
		}
		return $tooltip;
	}

	/**
	 * Position tooltip relative to target element.
	 */
	function positionTooltip($target, placement) {
		var $tip = getTooltip();

		// Use parent .drag_element as anchor (img has negative absolute offset)
		var $anchor = $target.closest('.drag_element');
		if (!$anchor.length) {
			$anchor = $target.closest('.point_style');
		}
		if (!$anchor.length) {
			$anchor = $target;
		}

		var offset = $anchor.offset();
		var targetWidth = $anchor.outerWidth();
		var targetHeight = $anchor.outerHeight();

		// If anchor is too small (1px dot), use the pin image dimensions
		if (targetWidth <= 2 || targetHeight <= 2) {
			var $pin = $anchor.find('.pins_image');
			if ($pin.length) {
				targetWidth = $pin.outerWidth() || 24;
				targetHeight = $pin.outerHeight() || 24;
			}
		}

		// Temporarily show to measure tooltip dimensions
		$tip.css({ visibility: 'hidden', display: 'block' });
		var tipWidth = $tip.outerWidth();
		var tipHeight = $tip.outerHeight();

		var scrollTop = $(window).scrollTop();
		var scrollLeft = $(window).scrollLeft();
		var winWidth = $(window).width();
		var winHeight = $(window).height();

		var top, left;
		var arrowDir = placement || 'n';

		// .drag_element position IS the pin center (pin images use negative offsets)
		// So center tooltip directly on offset.left, offset.top
		var centerX = offset.left;
		var centerY = offset.top;

		// Calculate pin half-height to clear the visible pin image
		// Read pin image negative offset to find its visible top edge
		var pinTopOffset = 0;
		var $pinImg = $anchor.find('.pins_image');
		if ($pinImg.length) {
			pinTopOffset = Math.abs(parseInt($pinImg.css('top'), 10) || 12);
		}

		// Check user logged in (has id="wpadminbar")
		var isLoggedin = $('#wpadminbar').length;
		var offsetAdminBar = 0;
		if (isLoggedin > 0) {
			offsetAdminBar = 32;
		}

		switch (arrowDir) {
			case 'n':
				top = centerY - pinTopOffset - tipHeight - 12 - offsetAdminBar;
				left = centerX - (tipWidth / 2);
				break;
			case 's':
				top = centerY + pinTopOffset + tipHeight + 12 - offsetAdminBar;
				left = centerX - (tipWidth / 2);
				break;
			case 'e':
				top = centerY - (tipHeight / 2) - 12 - offsetAdminBar;
				left = centerX + 10;
				break;
			case 'w':
				top = centerY - (tipHeight / 2) - 12 - offsetAdminBar;
				left = centerX - tipWidth - 10;
				break;
			default:
				top = centerY - tipHeight - 10;
				left = centerX - (tipWidth / 2);
		}

		// Smart placement: keep tooltip within viewport
		if (top < scrollTop) {
			top = centerY + pinTopOffset + 10;
			arrowDir = 's';
		}
		if (top + tipHeight > scrollTop + winHeight) {
			top = centerY - pinTopOffset - tipHeight - 10;
			arrowDir = 'n';
		}
		if (left < scrollLeft) {
			left = scrollLeft + 5;
		}
		if (left + tipWidth > scrollLeft + winWidth) {
			left = scrollLeft + winWidth - tipWidth - 5;
		}

		$tip.removeClass('ipt-n ipt-s ipt-e ipt-w').addClass('ipt-' + arrowDir);
		$tip.css({
			top: Math.round(top) + 'px',
			left: Math.round(left) + 'px',
			visibility: 'visible',
			display: 'block'
		});
	}

	/**
	 * Show tooltip for a target element.
	 */
	function showTooltip($target, html, placement) {
		var $tip = getTooltip();

		// Close any existing tooltip first
		if (activeTarget && activeTarget[0] !== $target[0]) {
			hideTooltip();
		}

		$tip.html(html);
		activeTarget = $target;
		positionTooltip($target, placement);
		$tip.addClass('ipt-active');
	}

	/**
	 * Hide the active tooltip.
	 */
	function hideTooltip() {
		if ($tooltip && $tooltip.length) {
			$tooltip.removeClass('ipt-active').css('display', 'none').html('');
		}
		activeTarget = null;
	}

	/**
	 * Initialize image point tooltips.
	 */
	function imagePointsInit() {
		$('.image_points_tooltip_html[data-html]').each(function () {
			var $source = $(this);
			var tooltipHtml = $source.attr('data-html');
			var $targets = $source.find('.image_points_hastooltip');
			var thisPlace = $source.data('placement') || 'n';

			if (!tooltipHtml) {
				return;
			}

			if (!$targets.length) {
				$targets = $source;
			}

			$targets.each(function () {
				var $target = $(this);

				if ($target.data('imagePointsTooltipReady')) {
					return;
				}

				// Remove title attr to prevent native browser tooltip
				if ($target.attr('title')) {
					$target.removeAttr('title');
				}

				$target.on('click.imagePoints', function (event) {
					event.preventDefault();
					event.stopPropagation();

					// Toggle: if clicking same target, hide
					if (activeTarget && activeTarget[0] === $target[0]) {
						hideTooltip();
					} else {
						showTooltip($target, tooltipHtml, thisPlace);
					}
				});

				$target.data('imagePointsTooltipReady', true);
			});
		});
	}

	// Close tooltip on click outside
	$(document).on('click.imagePoints', function (event) {
		if (!$tooltip || !$tooltip.length) return;
		if (!activeTarget) return;

		var $t = $(event.target);
		// Don't close if clicking inside tooltip or on a pin
		if ($t.closest('#imagePointsTip').length || $t.closest('.image_points_hastooltip').length) {
			return;
		}
		hideTooltip();
	});

	// Close on Escape key
	$(document).on('keydown.imagePoints', function (event) {
		if (event.keyCode === 27) {
			hideTooltip();
		}
	});

	$(document).ready(function () {
		imagePointsInit();
	});

	// Hide tooltip on resize/rotate to prevent stale position
	var resizeTimer;
	$(window).on('resize orientationchange', function () {
		clearTimeout(resizeTimer);
		resizeTimer = setTimeout(function () {
			hideTooltip();
		}, 150);
	});

	// Re-init for lazy-loaded content
	var firstLoad = true;
	function scroll_element() {
		var $top = $(window).scrollTop();
		if ($top >= 100 && firstLoad) {
			imagePointsInit();
			firstLoad = false;
		}
	}

	$(window).on('scroll', scroll_element);
	$(window).on('touchmove', scroll_element);

})(jQuery);
