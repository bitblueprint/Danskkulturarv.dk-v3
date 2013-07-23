/* =============================================================
 * Custom functions goes here
 * ============================================================ */

jQuery(document).ready(function($) {

	$(".btn-group button").click(function () {
	    $("#buttonvalue").val($(this).text());
	});
	
}); /* end of as page load scripts */