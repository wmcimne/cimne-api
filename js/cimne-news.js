jQuery(function ($) {
    let currentPage = 1;

    const $results = $('#news-results');
    const $pagination = $('#news-pagination');

    if (!$results.length) {
        return;
    }

    function loadNews(page = 1) {
        currentPage = page;

        $.ajax({
            url: CimneNews.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'cimne_filter_news',
                nonce: CimneNews.nonce,
                current_category: CimneNews.current_category,
                paged: currentPage,
                search: $('#search-box').val(),
                news_type: $('#publication').val(),
                start_date: $('#start-date').val(),
                end_date: $('#end-date').val()
            },
            beforeSend: function () {
                $results.addClass('is-loading');
                $pagination.addClass('is-loading');
            },
            success: function (response) {
                console.log('NEWS AJAX response:', response);
                if (response.success) {
                    $results.html(response.data.html);
                    $pagination.html(response.data.pagination || '');
                }
            },
            error: function (xhr) {
                console.error('NEWS AJAX error:', xhr.status, xhr.responseText);
            },
            complete: function () {
                $results.removeClass('is-loading');
                $pagination.removeClass('is-loading');
            }
        });
    }

    $('#apply-filter, .filter-data').on('click', function (e) {
        e.preventDefault();
        loadNews(1);
    });

    $('#refresh-filter, .refresh-filter').on('click', function (e) {
        e.preventDefault();

        $('#search-box').val('');
        $('#publication').val('');
        $('#start-date').val('').attr('type', 'text');
        $('#end-date').val('').attr('type', 'text');

        loadNews(1);
    });

    $('#search-box').on('keypress', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            loadNews(1);
        }
    });

    $(document).on('click', '.cimne-news-pagination a[data-page]', function (e) {
        e.preventDefault();

        const page = parseInt($(this).attr('data-page'), 10);

        if (page && page !== currentPage) {
            loadNews(page);
        }
    });

    loadNews(1);
});