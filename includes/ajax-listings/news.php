<?php

if (!defined('ABSPATH')) {
    exit;
}

/*==========================================================
# News AJAX filtering
==========================================================*/

add_action('wp_ajax_cimne_filter_news', 'cimne_filter_news');
add_action('wp_ajax_nopriv_cimne_filter_news', 'cimne_filter_news');

function cimne_filter_news() {
    check_ajax_referer('cimne_news_nonce', 'nonce');

    $search           = sanitize_text_field($_POST['search'] ?? '');
    $news_type        = sanitize_text_field($_POST['news_type'] ?? '');
    $start_date       = sanitize_text_field($_POST['start_date'] ?? '');
    $end_date         = sanitize_text_field($_POST['end_date'] ?? '');
    $paged            = isset($_POST['paged']) ? max(1, absint($_POST['paged'])) : 1;
    $current_category = sanitize_key($_POST['current_category'] ?? cimne_get_current_news_root_slug());

    $date_query = [];

    if (!empty($start_date)) {
        $date_query['after'] = $start_date;
    }

    if (!empty($end_date)) {
        $date_query['before'] = $end_date;
    }

    if (!empty($date_query)) {
        $date_query['inclusive'] = true;
    }

    $tax_query = [
        'relation' => 'AND',
    ];

    if (!empty($current_category)) {
        $tax_query[] = [
            'taxonomy' => 'category',
            'field'    => 'slug',
            'terms'    => [$current_category],
        ];
    }

    if (!empty($news_type)) {
        $tax_query[] = [
            'taxonomy' => 'category',
            'field'    => 'slug',
            'terms'    => [sanitize_key($news_type)],
        ];
    }

    $args = [
        'post_type'              => 'post',
        'posts_per_page'         => 12,
        'paged'                  => $paged,
        'post_status'            => 'publish',
        's'                      => $search,
        'orderby'                => 'date',
        'order'                  => 'DESC',
        'update_post_meta_cache' => true,
        'update_post_term_cache' => true,
        'tax_query'              => $tax_query,
    ];

    if (!empty($date_query)) {
        $args['date_query'] = [$date_query];
    }

    $news = new WP_Query($args);

    ob_start();

    if ($news->have_posts()) {
        while ($news->have_posts()) {
            $news->the_post();
            get_template_part('template-parts/news-card');
        }
    } else {
        echo '<p class="cimne-news-empty">No news found.</p>';
    }

    $html = ob_get_clean();

    $pagination = cimne_build_ajax_pagination(
        $paged,
        (int) $news->max_num_pages,
        'cimne-news-pagination-nav'
    );

    wp_reset_postdata();

    wp_send_json_success([
        'html'       => $html,
        'pagination' => $pagination,
    ]);
}
