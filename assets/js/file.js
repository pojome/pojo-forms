jQuery(document).ready(function($) {
	// Variable to store your files
	var files;

	// Add events
	var file = $('.pojo-form input[type=file]');
	file.on('change', function(event) {
		files = event.target.files;
		console.log('files:' + files );
	});

	$('.pojo-form').on('submit', function() {
	  	event.stopPropagation(); // Stop stuff happening
	    event.preventDefault(); // Totally stop stuff happening

	    // Create a formdata object and add the files
	    var data = new FormData();
	    $.each(files, function(key, value) {
	        data.append(key, value);
	    });

	    $.ajax({
	        url: pojo_forms.ajaxurl,
	        type: 'POST',
	        data: data,
	        cache: false,
	        dataType: 'json',
	        processData: false,
	        contentType: false, 
	        success: function(data, textStatus, jqXHR) {
	            if(typeof data.error !== 'undefined') {
	                console.log('ERRORS: ' + data.error);
	            } else {
	            	console.log('cool:' + data);
	            }
	        },
	        error: function(jqXHR, textStatus, errorThrown) {
	            // Handle errors here
	            console.log('ERRORS: ' + textStatus);
	            // STOP LOADING SPINNER
	        }
	    });		
	});	
});