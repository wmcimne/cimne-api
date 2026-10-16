jQuery(function ($) {
    let currentPage = 1;

    const $container = $('#workshop-blog');
    const $pagination = $('#workshop-pagination');

    if (!$container.length) {
        return;
    }

    function loadWorkshops(page = 1) {
        currentPage = page;

        $.ajax({
            url: CimneWorkshops.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'cimne_filter_workshops_events',
                nonce: CimneWorkshops.nonce,
                paged: currentPage
            },
            beforeSend: function () {
                $container.addClass('is-loading');
                $pagination.addClass('is-loading');
            },
            success: function (response) {
                if (response.success) {
                    $container.html(response.data.html || '');
                    $pagination.html(response.data.pagination || '');
                }
            },
            complete: function () {
                $container.removeClass('is-loading');
                $pagination.removeClass('is-loading');
            }
        });
    }

    $(document).on('click', '.cimne-workshops-pagination a[data-page]', function (e) {
        e.preventDefault();

        const page = parseInt($(this).attr('data-page'), 10);

        if (page && page !== currentPage) {
            loadWorkshops(page);
        }
    });

    loadWorkshops(1);
});