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

});
