<?php


/*==========================================================
#  AJAX pagination
==========================================================*/

function cimne_build_ajax_pagination($paged, $max_pages, $nav_class) {
    if ($max_pages <= 1) {
        return '';
    }

    $items_per_group = 6;

    $current_group = (int) ceil($paged / $items_per_group);
    $start_page    = (($current_group - 1) * $items_per_group) + 1;
    $end_page      = min($start_page + $items_per_group - 1, $max_pages);

    $prev_group_page = max(1, $start_page - $items_per_group);
    $next_group_page = min($max_pages, $end_page + 1);

    $pagination = '<nav class="cimne-ajax-pagination-nav ' . esc_attr($nav_class) . '">';

    if ($start_page > 1) {
        $pagination .= '<a href="#" class="page-numbers pagination-arrow pagination-prev" data-page="' . esc_attr($prev_group_page) . '" aria-label="Previous pages"></a>';
    }

    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i === $paged) {
            $pagination .= '<span class="page-numbers current">' . esc_html($i) . '</span>';
        } else {
            $pagination .= '<a href="#" class="page-numbers" data-page="' . esc_attr($i) . '">' . esc_html($i) . '</a>';
        }
    }

    if ($end_page < $max_pages) {
        $pagination .= '<a href="#" class="page-numbers pagination-arrow pagination-next" data-page="' . esc_attr($next_group_page) . '" aria-label="Next pages"></a>';
    }

    $pagination .= '</nav>';

    return $pagination;
}
