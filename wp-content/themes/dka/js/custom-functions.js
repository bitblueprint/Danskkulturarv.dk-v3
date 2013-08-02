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
			this.addFlexSliders();
			this.socialSharePopup();

		},

		/**
		 * Update labels according to their checkbox state
		 * Tell ToggleAll button
		 * Force form submit on every change
		 * @return {void} 
		 */
		addCheckboxListener: function() { 
			$("input.chaos-filter").change(function() {
				var checkbox = $(this);
				var label = checkbox.parent();

				label.toggleClass("active",checkbox.is(":checked"));

				api.updateToggleAllState(label.parent());
				//api.forceSubmitForm();
			}).change(); //Fire on load to get current states
			$("input.chaos-filter").change(function() {
				api.forceSubmitForm();
			});
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
		},

		/**
		 * Force click on form submit
		 * @return {void} 
		 */
		forceSubmitForm: function() {
			$("#searchsubmit").click();
		},

		/**
		 * Adding FlexSlider functionality
		 * @return {void} 
		 */
		addFlexSliders: function() {
			$('.flexslider').flexslider({
				animation: "slide",
				touch: true
			});
		},

		/**
		 * Open window in popup instead of new
		 * @return {void} 
		 */
		socialSharePopup: function() {
			$(".social-share").click( function(e) {
				var width = 600;
				var height = 400;
				var left = (screen.width/2)-(width/2);
				var top = (screen.height/2)-(height/2);
				window.open(
					$(this).attr('href'),
					'',
					'menubar=no, toolbar=no, resizable=yes, scrollbars=yes, height='+height+', width='+width+', top='+top+', left='+left+''
				);

				e.preventDefault();
				return false;
			});
		}

	}

	//Initiate class on page load
	$(document).ready(function(){ api.init(); });

})(jQuery);
