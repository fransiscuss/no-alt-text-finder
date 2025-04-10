/* No Alt Text Finder Admin Scripts */
jQuery(document).ready(function($) {
    // Handle form submission
    $('#natf-form').on('submit', function(e) {
        e.preventDefault();
        
        // Show progress bar
        $('.natf-progress').show();
        $('.natf-results').hide();
        
        // Get form data
        var formData = $(this).serialize();
        
        // Send AJAX request
        $.ajax({
            url: natf_data.ajax_url,
            type: 'POST',
            data: formData + '&action=natf_export_csv&nonce=' + natf_data.nonce,
            dataType: 'json',
            beforeSend: function() {
                $('#natf-submit').prop('disabled', true);
                $('.natf-progress-bar').css('width', '0%');
                $('.natf-status').text(natf_data.exporting_text);
            },
            success: function(response) {
                $('.natf-progress-bar').css('width', '100%');
                
                if (response.success) {
                    $('.natf-status').text(natf_data.export_complete);
                    $('.natf-results').html('<p>' + response.data.message + '</p>').show();
                    
                    if (response.data.download_url) {
                        window.location.href = response.data.download_url;
                    }
                } else {
                    $('.natf-status').html('<span class=\"natf-error\">' + response.data.message + '</span>');
                }
            },
            error: function() {
                $('.natf-status').html('<span class=\"natf-error\">' + natf_data.export_error + '</span>');
            },
            complete: function() {
                $('#natf-submit').prop('disabled', false);
            }
        });
    });
});