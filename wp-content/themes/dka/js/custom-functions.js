/* =============================================================
 * Custom functions goes here
 * ============================================================ */

jQuery(document).ready(function($) {
	
	// Avanced search toggles

	// Show all buttons
	$(".filter-btn.filter-btn-all").click(function() {
		$(this).siblings(".filter-btn.filter-btn-single").removeClass("active");
	});

	// Set filter buttons
	$(".filter-btn.filter-btn-single").click(function() {
		if($(this).hasClass("active") && $(this).siblings(".active").length == 0) {
			$(this).siblings(".filter-btn.filter-btn-all").addClass("active");
		} else {
			$(this).siblings(".filter-btn.filter-btn-all").removeClass("active");
		}
	});
	
}); /* end of as page load scripts */