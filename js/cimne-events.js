jQuery(function ($) {
    // console.log('Loading events...');
    let currentStatus = 'upcoming';
    let currentPage = 1;

    const $results = $('#events-results');
    const $pagination = $('#events-pagination');

    if (!$results.length) {
        return;
    }
    //console.log('CimneEvents:', CimneEvents);
    
    function loadEvents(page = 1) {
        currentPage = page;

        $.ajax({
            url: CimneEvents.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'cimne_filter_events',
                nonce: CimneEvents.nonce,
                current_category: CimneEvents.current_category,
                status: currentStatus,
                paged: currentPage,
                search: $('#search-box').val(),
                event_type: $('#publication').val(),
                start_date: $('#start-date').val(),
                end_date: $('#end-date').val()
            },
            beforeSend: function () {
                $results.addClass('is-loading');
                $pagination.addClass('is-loading');
            },
            success: function (response) {
                if (response.success) {
                    $results.html(response.data.html);
                    $pagination.html(response.data.pagination || '');
                }
            },
            complete: function () {
                $results.removeClass('is-loading');
                $pagination.removeClass('is-loading');
            }
        });
    }

    $('#upcoming-events-item').on('click', function () {
        currentStatus = 'upcoming';
        currentPage = 1;

        $('.events-nav-item').removeClass('nav-item-active');
        $(this).addClass('nav-item-active');

        loadEvents(1);
    });

    $('#past-events-item').on('click', function () {
        currentStatus = 'past';
        currentPage = 1;

        $('.events-nav-item').removeClass('nav-item-active');
        $(this).addClass('nav-item-active');

        loadEvents(1);
    });

    $('#apply-filter, .filter-data').on('click', function (e) {
        e.preventDefault();
        currentPage = 1;
        loadEvents(1);
    });

    $('#refresh-filter, .refresh-filter').on('click', function (e) {
        e.preventDefault();

        $('#search-box').val('');
        $('#publication').val('');
        $('#start-date').val('').attr('type', 'text');
        $('#end-date').val('').attr('type', 'text');

        currentPage = 1;
        loadEvents(1);
    });

    $('#search-box').on('keypress', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            currentPage = 1;
            loadEvents(1);
        }
    });

    $(document).on('click', '.cimne-events-pagination a[data-page]', function (e) {
        e.preventDefault();

        const page = parseInt($(this).attr('data-page'), 10);

        if (page && page !== currentPage) {
            loadEvents(page);
        }
    });

    loadEvents(1);
});

jQuery(function ($) {

    // console.log('Home events JS loaded');
    // console.log('CimneEvents:', CimneEvents);
    // console.log('Right:', $('#home-upcoming-events-right').length);
    // console.log('Left:', $('#home-upcoming-events-left').length);
    // console.log('Loading home upcoming events...');
    
    const $right = $('#home-upcoming-events-right');
    const $left = $('#home-upcoming-events-left');

    if (!$right.length && !$left.length) {
        return;
    }

    $.ajax({
        url: CimneEvents.ajax_url,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'cimne_filter_events',
            nonce: CimneEvents.nonce,
            mode: 'home_upcoming'
        },
        beforeSend: function () {
            // console.log('Loading home upcoming events...');
        },
        success: function (response) {
            // console.log('Home events response:', response);
            if (!response.success) {
                return;
            }

            if (response.data.right) {
                $right.html(response.data.right).show();
            } else {
                $right.hide();
            }

            if (response.data.left) {
                $left.html(response.data.left).show();
            } else {
                $left.hide();
            }
        },
        error: function (xhr) {
            // console.error('Home events error:', xhr.status, xhr.responseText);
        }
    });
});