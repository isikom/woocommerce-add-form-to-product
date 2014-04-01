jQuery(document).ready(function($){
	$('#af2p_add_form_button').on('click', function(){
		var name = $('#af2p_add_form').val();
		if (name.length > 0){
			var title = $('#af2p_add_form  option:selected').text();
			console.log(name);
			console.log(title);
			
			$( "#af2p_add_form option:selected" ).remove();
			
			$('#forms_selected .solo').hide();
			$('<tr><td><a class="remove dashicons" data-item="'+name+'"></a><input type="hidden" name="af2p_forms[]" value="'+name+'"></td><td>'+title+'</td></tr>').appendTo($('#forms_selected tbody'));			
		}
		return false;
	});
	$('#forms_selected').on('click', 'a.remove', function(){
		//alert('remove fired');
		var numitems = $('#forms_selected a.remove').length;
		console.log('number of element ' + numitems);

		var name = $(this).attr('data-item');
        var title = $(this).closest('tr').find('td:nth-child(2)').html();

		var o = new Option(title, name);
		$("#af2p_add_form").append(o);

		$(this).closest('tr').remove();
		if (numitems == 1){
			$('#forms_selected .solo').show();
		}
		return false;
	});
	
	$("#expand-texts").on('click', function(){
		$('#texts-container').slideDown();
		$('#expand-texts').hide();
		$('#collapse-texts').show();
 	});

	$("#collapse-texts").on('click', function(){
		$('#texts-container').slideUp();
		$('#collapse-texts').hide();
		$('#expand-texts').show();
 	});
 	
	$("#expand-reports").on('click', function(){
		$('#reports-container').slideDown();
		$('#expand-reports').hide();
		$('#collapse-reports').show();
 	});

	$("#collapse-reports").on('click', function(){
		$('#reports-container').slideUp();
		$('#collapse-reports').hide();
		$('#expand-reports').show();
 	});
 	
});

/*
 * Attaches the image uploader to the input field
 */
jQuery(document).ready(function($){
 
    // Instantiates the variable that holds the media library frame.
    var preview_uploader;
		
    // Runs when the image button is clicked.
    //$('#meta-preview-button').click(function(e){
 	$('#meta-preview-button').click(function(e){			
        // Prevents the default action from occuring.
        e.preventDefault();
 
        // If the frame already exists, re-open it.
        if ( preview_uploader ) {
			preview_uploader.open();
            return;
        }
 
        // Sets up the media library frame
        //preview_uploader = wp.media.frames.preview_uploader = wp.media({
        preview_uploader = wp.media.frames.file_frame = wp.media({
            //title: meta_preview.title,
            //button: { text:  meta_preview.button },
            title: 'Choose a document',
            button: {text: 'set a document as preview'},
            multiple: false
            //library: { type: 'image' }
        });
 
 		console.log('i am here');
 		console.log(preview_uploader);
 		
        // Runs when an image is selected.
        preview_uploader.on('select', function(){
 			console.log('fire event select');
            // Grabs the attachment selection and creates a JSON representation of the model.
            attachment = preview_uploader.state().get('selection').first().toJSON();
            $('#meta-preview').val(attachment.url);
        });
 
        preview_uploader.open();
    });
});
