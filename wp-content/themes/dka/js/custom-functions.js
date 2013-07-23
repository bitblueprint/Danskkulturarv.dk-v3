/* =============================================================
 * Custom functions goes here
 * ============================================================ */

jQuery(document).ready(function($) {
	
	// Avanced search toggles
	function updateToggleAllState($container) {
		var checkedBoxes = $("input[type=checkbox]:checked", $container);
		var allButton = $(".filter-btn.filter-btn-all", $container);
		if(checkedBoxes.length == 0 && !allButton.hasClass("active")) {
			$(".filter-btn.filter-btn-all", $container).addClass("active");
		} else if(checkedBoxes.length > 0 && allButton.hasClass("active")) {
			$(".filter-btn.filter-btn-all", $container).removeClass("active");
		}
	}

	// Show all buttons
	$(".filter-btn.filter-btn-all").click(function() {
		// Change the state and fire the change event.
		$("input[type=checkbox]", $(this).parent()).attr("checked", false).change();
	});

	// Update the buttons according to their checkbox state.
	$("input[type=checkbox]").change(function() {
		var id = $(this).attr("id");
		var checked = $(this).is(":checked");
		$(this).parent().each(function() {
			if(checked) {
				$(this).addClass("active");
			} else {
				$(this).removeClass("active");
			}
		});
		updateToggleAllState($(this).parent().parent());
	});
	
}); /* end of as page load scripts */