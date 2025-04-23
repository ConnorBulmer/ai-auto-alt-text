jQuery(document).on('click', '.aatg-generate-alt', function() {
    var button = jQuery(this);
    var attachmentId = button.data('attachment-id');
    button.prop('disabled', true).text('Generating...');
    
    jQuery.ajax({
        url: aatg_ajax.ajax_url,
        method: 'POST',
        data: {
            action: 'aatg_generate_alt_text_ajax',
            attachment_id: attachmentId,
            nonce: aatg_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                var newAlt = response.data.alt_text;
                // Show a notice in the result span with the generated alt text.
                button.siblings('.aatg-result').html(
                    'Alt text generated: <em>' + newAlt + '</em>. Please refresh the page to update the input field.'
                );
                button.text('Generate Alt Text');
            } else {
                button.text('Error');
                console.log(response.data);
            }
            button.prop('disabled', false);
        },
        error: function(xhr, status, error) {
            button.text('Error');
            button.prop('disabled', false);
            console.log(error);
        }
    });
});
