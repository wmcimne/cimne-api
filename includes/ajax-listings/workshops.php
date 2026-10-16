<?php

if (!defined('ABSPATH')) {
    exit;
}

/*==========================================================
# Workshops AJAX filtering
==========================================================*/

add_action('wp_ajax_cimne_filter_workshops_events', 'cimne_filter_workshops_events');
add_action('wp_ajax_nopriv_cimne_filter_workshops_events', 'cimne_filter_workshops_events');

function cimne_filter_workshops_events() {
    check_ajax_referer('cimne_workshops_nonce', 'nonce');

    $paged = isset($_POST['paged']) ? max(1, absint($_POST['paged'])) : 1;
    $workshop_id = cimne_get_translated_term_id_by_slug(cimne_get_canonical_workshop_slug());

    $tax_query = [];

    if ($workshop_id) {
        $tax_query[] = [
            'taxonomy'         => 'category',
            'field'            => 'term_id',
            'terms'            => [$workshop_id],
            'include_children' => true,
        ];
    } else {
        $tax_query[] = [
            'taxonomy'         => 'category',
            'field'            => 'slug',
            'terms'            => cimne_get_workshop_slugs(),
            'include_children' => true,
        ];
    }

    $args = [
        'post_type'              => 'post',
        'posts_per_page'         => 9,
        'paged'                  => $paged,
        'post_status'            => 'publish',
        'meta_key'               => CIMNE_EVENT_DATE_FIELD,
        'orderby'                => 'meta_value_num',
        'order'                  => 'DESC',
        'update_post_meta_cache' => true,
        'update_post_term_cache' => true,
        'tax_query'              => $tax_query,
        'meta_query'             => [
            [
                'key'     => CIMNE_EVENT_DATE_FIELD,
                'compare' => 'EXISTS',
            ],
        ],
    ];

    $workshops = new WP_Query($args);

    ob_start();

    if ($workshops->have_posts()) {
        while ($workshops->have_posts()) {
            $workshops->the_post();
            get_template_part('template-parts/workshop-event-card');
        }
    } else {
        echo '<p class="cimne-workshops-empty">No workshops found.</p>';
    }

    $html = ob_get_clean();

    $pagination = cimne_build_ajax_pagination(
        $paged,
        (int) $workshops->max_num_pages,
        'cimne-workshops-pagination-nav'
    );

    wp_reset_postdata();

    wp_send_json_success([
        'html'       => $html,
        'pagination' => $pagination,
    ]);
}
