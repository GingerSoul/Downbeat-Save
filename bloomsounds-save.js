var bloomsounds_save = {
	'url' : function() {
		var my_app = window.app;
		var setting = my_app.exportConfiguration();
		var currentBPM = my_app.getBPM();
		// var urlConfig = document.location.origin + '?tempo='+currentBPM+'&c=' + encodeURIComponent(setting);
		var urlConfig = '?tempo='+currentBPM+'&c=' + encodeURIComponent(setting);
		return urlConfig;
	},
	'save' : function( url ) {
		var title = window.prompt( bloomsounds_save_data.title, bloomsounds_save_data.placeholder );
		jQuery.get(
			bloomsounds_save_data.ajaxurl,
			{
				'action'	: 'bloomsounds_save',
				'link'		: bloomsounds_save.url(),
				'title'		: title,
			},
			function (response) {
				jQuery(this).trigger('bloomsounds:save');
				var item = bloomsounds_save_data.item;
				// console.log(response);
				item = item.replace( '%title%', response.data.title );
				item = item.replace( '%link%', response.data.link );
				jQuery('ul.bloomsounds-save').prepend( item );
			}
		)
	}
}
