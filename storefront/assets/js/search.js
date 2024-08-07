jQuery(document).ready(function($) {
    $('#search-form').submit(function(event) {
        event.preventDefault();
        var searchQuery = $('#search-input').val();
        $.ajax({
            type: 'POST',
            url: '/wp-admin/admin-ajax.php',
            data: {
                'action': 'ajax_search_posts',
                'search_query': searchQuery
            },
            success: function(data) {
                // очищаем таблицу
                $('#search-results_table_tbody').html('');
                // вставляем в таблицу результаты обработчика ajax_search_posts
                $.each(data, function(index, result) {
                    $('#search-results_table_tbody').append('<tr><td>' + result.country + '</td><td>' + result.title + '</td><td>' + result.weather + '</td></tr>');
                });
            }
        });
    });
});