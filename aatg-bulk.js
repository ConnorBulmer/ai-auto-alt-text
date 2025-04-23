jQuery(document).ready(function($) {
    var total = null;
    var completed = 0;
    
    $('#aatg-bulk-start').on('click', function() {
        $('#aatg-bulk-status').hide();
        $('#aatg-bulk-progress-container').show();
        $('#aatg-bulk-progress').val(0);
        $('#aatg-bulk-progress-text').html('Initialising…');
        processBatch();
    });
    
    function processBatch() {
        $.ajax({
            url: aatg_bulk_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'aatg_bulk_update',
                nonce: aatg_bulk_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var processed = response.data.processed;
                    var remaining = response.data.remaining;
                    
                    // On first batch, set total = processed + remaining
                    if (total === null) {
                        total = processed + remaining;
                        $('#aatg-bulk-progress').attr('max', total);
                    }
                    
                    completed += processed;
                    
                    // Update progress bar & text
                    $('#aatg-bulk-progress').val(completed);
                    $('#aatg-bulk-progress-text').html(
                        completed + ' images optimised, ' + remaining + ' remaining.'
                    );
                    
                    if (remaining > 0) {
                        setTimeout(processBatch, 5000);
                    } else {
                        $('#aatg-bulk-progress-text').append('<br />Bulk update complete.');
                    }
                } else {
                    $('#aatg-bulk-progress-text').html('Error: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                $('#aatg-bulk-progress-text').html('AJAX error: ' + error);
            }
        });
    }
});