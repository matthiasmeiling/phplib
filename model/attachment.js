function attachment () {
	var dialog = $('#attachment_dialog');
	if (dialog.dialog('instance')) {
		return null;
	}

	// construct dialog
	dialog.dialog ({
			show: false,
			autoOpen: true,
			closeText: unescape("Schlie%DFen"),
			buttons: [{
					text: unescape("Schlie%DFen"), 
					click: function() { 
							$(this).dialog( "close" );
						} 
				} 
			],
			close: function (event, ui) {
				dialog.dialog ('destroy');
				dialog.empty ();
			}
		});
	
	var upload; // = $('<button type="file" name = "files" class="upload">Upload</button>');
	upload = $('<input class="hidden" type="file" name="files" />');
	var button = $('<button class="upload">Upload</div>');
	var progress_bar = $('<div class="bar">');
	dialog.append (upload);
	dialog.append (button);
	
	/* click the hidden form */
	button.bind('click', function () {
		upload.click ();
	});

	var fileupload = upload.fileupload({
		singleFileUploads: true,
        dataType: 'json',
		url: BASE_PATH + '/extern/fileupload/server/',
        done: function (e, data) {
            $.each(data.result.files, function (index, file) {
                $('<p/>').text(file.name).appendTo(document.body);
				console.log(file);
            });
        },
		progressall: function (e, data) {
			var progress = parseInt(data.loaded / data.total * 100, 10);
			progress_bar.css('width', progress + '%');
			console.log(progress);
		},
		start: function (e, data) {
			console.log (button);
			button.empty ();
			button.append (progress_bar);
		}
    });
}

$(document).ready (function () {
	var dialog = $('<div id="attachment_dialog"></div>');
	$('body').append (dialog);

	/*$('input.attachment').bind ('click', function () {
		attachment ();
	});*/
	$('input.attachment').each (function (i, e) {
		e = $(e);
		var value = e.val ();
		var potrait;
		if (value == "") {
			potrait = $('<img src="http://aufpapier.de/blog/wp-content/uploads/2014/03/IMG_1392-e1395176498530-300x225.jpg"/>');
		} else {
			/* TODO implement me! */
			alert ('implement me!');
		}

		potrait.addClass ("potrait");

		e.after (potrait);
		// bind new image to attachment-dialog
		potrait.bind ('click', function () {
			attachment ();
		});
	});
});
