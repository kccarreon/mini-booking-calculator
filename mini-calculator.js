jQuery(document).ready(function($) {
    $('#booking-quote-form').submit(function(e) {
        e.preventDefault();

        // Collect form data
        var formData = {
            name: $('#name').val(),
            address: $('#address').val(),
            distance: $('#distance').val(),
            rooms: $('#rooms').val()
        };

        // Make AJAX request
        $.ajax({
            url: bookingQuoteParams.ajax_url,
            type: 'POST',
            data: {
                action: 'booking_quote',
                name: formData.name,
                address: formData.address,
                distance: formData.distance,
                rooms: formData.rooms
            },
            success: function(response) {
                $('#booking-quote-result').html(response);
            },
            error: function() {
                $('#booking-quote-result').html('Sorry, there was an error processing your request.');
            }
        });
    });
});
