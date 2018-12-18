(function () {
	function save ({title, data}) {
		jQuery.get(
			downbeat_save_data.ajaxurl,
			{
				action: 'downbeat_save',
				link: data,
				title,
			},
			response => console.log(response)
		)
	}

	document.addEventListener('downbeat:saveUserSession', ({detail}) =>
		save({
			title: detail.name,
			data: detail.session
		})
	)
})()
