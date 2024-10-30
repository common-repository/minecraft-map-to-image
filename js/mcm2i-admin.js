jQuery(document).ready(function() {

	mcm2iMapFileInput = document.getElementById('minecraft_map_file');
	mcm2iMapFile = document.getElementById('minecraft_map_file');
	mcm2iMapSerialData = '';
	mcm2iMapSerialized = false;
	mcm2iImageHolder = jQuery('#mcm2i-image-holder');

	mcm2iUpdatedMessage = jQuery('#mcm2i_updated');
	mcm2iErrorMessage 	= jQuery('#mcm2i_error');

	jQuery(mcm2iMapFileInput).change(function () {
	    convertMinecraftMap();
	});

	jQuery("#btn-save-to-library").click(function() {
		saveMinecraftMap();
	});

	jQuery('#mcm2i_save_form').on('submit', function(e) {
        e.preventDefault();  //prevent form from submitting
        saveMinecraftMap();
    });

    disableSave();

});

function hideMessages() {
	mcm2iUpdatedMessage.hide();
	mcm2iErrorMessage.hide();
}

function disableSave() {
	// jQuery("#mcm2i_save_form").hide()
	jQuery("#mcm2i_save_form input").prop('disabled', true);
	jQuery('#map_save_name').val("map_name");
}

function enableSave() {
	jQuery("#mcm2i_save_form input").prop('disabled', false);
}

function convertMinecraftMap() {
		mcm2iImageHolder.html('');
		jQuery('#mcm2i-metadata').html('');
		jQuery('#mcm2i-image-holder').html(jQuery('#mcm2i-ajax-saving').clone());
		jQuery('#mcm2i-image-holder img').show();



		hideMessages();
		disableSave()

   		formData = new FormData();
   		formData.append("map_file",  mcm2iMapFileInput.files[0]);
		formData.append('action', 'mcm2i_do_ajax');
		formData.append('fn', 'mcm2i_convert');

		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			cache: false,
			contentType: false,
			processData: false,
			data:formData,
			dataType:'JSON',
			error: function(errorThrown){
				jQuery('html, body').animate({scrollTop: '0px'}, 300);
				mcm2iErrorMessage.html('<p>There was an error converting the image.</p>');
				mcm2iErrorMessage.append(errorThrown.responseText);
				mcm2iErrorMessage.hide();
				mcm2iErrorMessage.fadeIn();

				console.log(errorThrown);
			},
			success:function(data, textStatus){
				if(data.status == 'succes'){

					mcm2iImageHolder.html('<img src="data:image/gif;base64,'+data.map_image+'" width="128px" height="128px" alt="Minecraft Map Image"/>');
					jQuery('#map_save_name').val(data.map_name);

					// add metadata
					jQuery('#mcm2i-metadata').html('<table>');
					jQuery('#mcm2i-metadata').append('<tr><td>File</td><td><code>'+data.map_name+'.dat</code></td>');
					jQuery('#mcm2i-metadata').append('<tr><td>Dimension</td><td><code>'+data.map_dimension+'</code></td>');
					jQuery('#mcm2i-metadata').append('<tr><td>Scale</td><td><code>'+data.map_scale+'</code></td>');
					jQuery('#mcm2i-metadata').append('<tr><td>xCenter</td><td><code>'+data.map_xcenter+'</code></td>');
					jQuery('#mcm2i-metadata').append('<tr><td>yCenter</td><td><code>'+data.map_ycenter+'</code></td>');
					jQuery('#mcm2i-metadata').append('</table>');

					enableSave();
				}
				else if(data.status == 'error') {
					jQuery('html, body').animate({scrollTop: '0px'}, 300);
					mcm2iErrorMessage.html('<p>There was an error converting the image.</p>');
					mcm2iErrorMessage.append(data.error_message);
					mcm2iErrorMessage.hide();
					mcm2iErrorMessage.fadeIn();

					jQuery('#mcm2i-image-holder').html('<p>No map loaded</p>');
    			}
			},
			complete:function(data){
				console.log(data);
			}
		});
}

function saveMinecraftMap() {
		jQuery("#btn-save-to-library").prop('disabled', true);
		jQuery('#mcm2i-ajax-saving').show();

		hideMessages();

   		formData = new FormData();
   		formData.append("map_file",  mcm2iMapFileInput.files[0]);
   		formData.append("map_save_name",  jQuery('#map_save_name').val());
		formData.append("map_save_dimensions",  jQuery('input[name=mcm2i_dimensions]:checked', '#mcm2i_save_form').val());
		
		formData.append('action', 'mcm2i_do_ajax');
		formData.append('fn', 'mcm2i_save_image');

		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			cache: false,
			contentType: false,
			processData: false,
			data:formData,
			dataType:'JSON',
			error: function(errorThrown){
				jQuery('html, body').animate({scrollTop: '0px'}, 300);
				mcm2iErrorMessage.html('<p>There was an error saving the image.</p>');
				mcm2iErrorMessage.append(errorThrown);
				mcm2iErrorMessage.hide();
				mcm2iErrorMessage.fadeIn();
				console.log(errorThrown);
			},
			success:function(data, textStatus){
				// console.log(data);
				jQuery('html, body').animate({scrollTop: '0px'}, 300);
				jQuery('#mcm2i_updated p').html('Succesfully saved to <a target="_new" href="' + data.url + '">'+ data.url +'</a>');
				jQuery('#mcm2i_updated p').append('<br /><br /><a href="'+ data.edit_url+ '">View in Media Library</a>');
				jQuery('#mcm2i_updated').hide();
				jQuery('#mcm2i_updated').fadeIn();
			},
			complete:function(data){
				jQuery("#btn-save-to-library").prop('disabled', false);
				jQuery('#mcm2i-ajax-saving').hide();
			}
		});
}