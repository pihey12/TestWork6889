jQuery(document).ready(function($) {
    $('#city-search-form').on('submit', function(e) {
        e.preventDefault();
        var searchTerm = $('#city-search').val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'search_cities',
                search: searchTerm
            },
            success: function(response) {
                $('#cities-table').html(response.data);
            }
        });
    });
});