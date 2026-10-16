<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', 'cimne_ajax_listings_enqueue_scripts');

function cimne_ajax_listings_enqueue_scripts() {
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';

    $is_events_category = cimne_is_current_category_under_translated_root('events');

    /*
    * Las noticias también se muestran agrupadas por categorías que no son
    * descendientes de la categoría raíz "news".
    *
    * Por este motivo, cualquier archivo de categoría que no pertenezca al
    * árbol de eventos utiliza los recursos del listado de noticias.
    */
    $is_news_category =
        is_category() &&
        !$is_events_category;

    $is_workshops_page =
        strpos($request_uri, '/community/training/short-training/') !== false ||
        strpos($request_uri, '/ca/comunitat/formacio/formacio-de-curta-durada/') !== false ||
        strpos($request_uri, '/es/comunidad/formacion/formacion-de-corta-duracion/') !== false;

    if ($is_events_category || is_front_page()) {
        wp_enqueue_style(
            'cimne-events',
            get_stylesheet_directory_uri() . '/css/cimne-events.css',
            [],
            cimne_asset_version( '/css/cimne-events.css' )
        );

        wp_enqueue_script(
            'cimne-events',
            get_stylesheet_directory_uri() . '/js/cimne-events.js',
            ['jquery'],
            cimne_asset_version( '/js/cimne-events.js' ),
            true
        );

        $current_category = get_queried_object();

        wp_localize_script('cimne-events', 'CimneEvents', [
            'ajax_url'         => admin_url('admin-ajax.php'),
            'nonce'            => wp_create_nonce('cimne_events_nonce'),
            'current_category' => is_category() && !empty($current_category->slug)
                ? $current_category->slug
                : cimne_get_translated_term_slug_by_canonical_slug('events'),
        ]);
    }

    if ($is_news_category) {
        wp_enqueue_style(
            'cimne-news',
            get_stylesheet_directory_uri() . '/css/cimne-news.css',
            [],
            cimne_asset_version( '/css/cimne-news.css' )
        );

        wp_enqueue_script(
            'cimne-news',
            get_stylesheet_directory_uri() . '/js/cimne-news.js',
            ['jquery'],
            cimne_asset_version( '/js/cimne-news.js' ),
            true
        );

        $current_category = get_queried_object();

        wp_localize_script('cimne-news', 'CimneNews', [
            'ajax_url'         => admin_url('admin-ajax.php'),
            'nonce'            => wp_create_nonce('cimne_news_nonce'),
            'current_category' => is_category() && !empty($current_category->slug)
                ? $current_category->slug
                : cimne_get_translated_term_slug_by_canonical_slug('news'),
        ]);
    }

    if ($is_workshops_page) {
        wp_enqueue_style(
            'cimne-workshops',
            get_stylesheet_directory_uri() . '/css/cimne-workshops.css',
            [],
            cimne_asset_version( '/css/cimne-workshops.css' )
        );

        wp_enqueue_script(
            'cimne-workshops',
            get_stylesheet_directory_uri() . '/js/cimne-workshops.js',
            ['jquery'],
            cimne_asset_version( '/js/cimne-workshops.js' ),
            true
        );

        wp_localize_script('cimne-workshops', 'CimneWorkshops', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('cimne_workshops_nonce'),
        ]);
    }
}