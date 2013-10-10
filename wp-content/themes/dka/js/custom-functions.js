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
			//this.addMediaElement();

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
				touch: true,
				smoothHeight: true
			});
		},

		/**
		 * Open window in popup instead of new
		 * @return {void} 
		 */
		socialSharePopup: function() {
			var objectGUID = $(".single-material[id]").each(function() {
				var $this = $(this);
				$.post(dka.ajax_url, {
					action: "wpdka_social_counts",
					object_guid: $this.attr('id')
				}, function(response) {
					$(".social-share[href*=facebook]", $this).attr('title', $(".social-share[href*=facebook]", $this).attr('title') + " ("+response.facebook_total_count+")");
					$(".social-share[href*=twitter]", $this).attr('title', $(".social-share[href*=twitter]", $this).attr('title') + " ("+response.twitter_total_count+")");
					$(".social-share[href*=google]", $this).attr('title', $(".social-share[href*=google]", $this).attr('title') + " ("+response.google_plus_total_count+")");
				}, 'json');
			});

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
		},

		/**
		 * Add MediaElement.js support on video and audio
		 * @return {void}
		 */
		/*
		addMediaElement: function() {
			$("video, audio").each(function() {
				var options = {
					iPadUseNativeControls: true,
					iPhoneUseNativeControls: true, 
				    AndroidUseNativeControls: true,
				};
				var streamer = $("source[data-streamer]", this).data('streamer');
				if(streamer) {
					options["flashStreamer"] = streamer;
				}
				$(this).mediaelementplayer(options);
			});
		}
		*/

	}

	//Initiate class on page load
	$(document).ready(function(){ api.init(); });

})(jQuery);
