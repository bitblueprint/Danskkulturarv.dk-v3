/* =============================================================
 * Custom functions goes here
 * ============================================================ */

(function($) {

	/**
	 * Main class for custom functions
	 * @type {Object}
	 */
	var api = {

		/**
		 * Initiator
		 * @return {void} 
		 */
		init: function() {

			this.addCheckboxListener();
			this.addToggleAllListener();
			
		},

		/**
		 * Update labels according to their checkbox state
		 * Tell ToggleAll button
		 * @return {void} 
		 */
		addCheckboxListener: function() { 
			$("input[type=checkbox]").change(function() {
				var checkbox = $(this);
				var label = checkbox.parent();

				label.toggleClass("active",checkbox.is(":checked"));

				api.updateToggleAllState(label.parent());
			}).change(); //Fire on load to get current states
		},
		
		/**
		 * Update ToggleAll according to the number of checkboxes checked
		 * @param  {[type]} $container 
		 * @return {void}            
		 */
		updateToggleAllState: function($container) {
			var checkedBoxes = $("input[type=checkbox]:checked", $container);
			var allButton = $(".filter-btn.filter-btn-all", $container);

			allButton.toggleClass("active",checkedBoxes.length == 0);
		},
		/**
		 * Reset checkboxes on ToggleAll
		 * @return {void}
		 */
		addToggleAllListener: function() {
			// Show all buttons
			$(".filter-btn.filter-btn-all").click(function() {
				// Change the state and fire the change event.
				$("input[type=checkbox]", $(this).parent()).attr("checked", false).change();
			});
		}

	}

	//Initiate class on page load
	$(document).ready(function(){ api.init(); });

})(jQuery);
