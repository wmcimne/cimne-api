<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CIMNE_EVENT_DATE_FIELD')) {
    define('CIMNE_EVENT_DATE_FIELD', 'date');
}

if (!defined('CIMNE_EVENT_START_DATE_FIELD')) {
    define('CIMNE_EVENT_START_DATE_FIELD', 'start_date');
}

/**
 * Parse ACF date fields stored as Ymd.
 */
function cimne_parse_event_date($date_string) {
    if (empty($date_string)) {
        return null;
    }

    $date = DateTime::createFromFormat('Ymd', $date_string);

    return $date ?: null;
}

/**
 * Current WPML language code, with locale fallback.
 */
function cimne_get_current_language() {
    if (has_filter('wpml_current_language')) {
        $language = apply_filters('wpml_current_language', null);

        if (!empty($language)) {
            return $language;
        }
    }

    return substr(get_locale(), 0, 2);
}

/**
 * Canonical slugs in the default/original language.
 * Keep these stable and let WPML resolve translated terms.
 */
function cimne_get_canonical_event_root_slug() {
    return 'events';
}

function cimne_get_canonical_news_root_slug() {
    return 'news';
}

function cimne_get_canonical_workshop_slug() {
    return 'workshops';
}

/**
 * Fallback slug lists.
 * These are only used when WPML term resolution is not available or the terms are not linked as translations.
 */
function cimne_get_event_root_slugs() {
    return ['events', 'esdeveniments', 'eventos'];
}

function cimne_get_news_root_slugs() {
    return ['news', 'noticies', 'noticias'];
}

function cimne_get_workshop_slugs() {
    return ['workshops', 'workshops-ca', 'workshops-es'];
}

/**
 * Get a term by its canonical slug.
 */
function cimne_get_term_by_canonical_slug($canonical_slug, $taxonomy = 'category') {
    $term = get_term_by('slug', $canonical_slug, $taxonomy);

    if (!$term || is_wp_error($term)) {
        return null;
    }

    return $term;
}

/**
 * Resolve a canonical term slug to the translated term ID for the current language.
 */
function cimne_get_translated_term_id_by_slug($canonical_slug, $taxonomy = 'category', $language = null) {
    $term = cimne_get_term_by_canonical_slug($canonical_slug, $taxonomy);

    if (!$term) {
        return 0;
    }

    if (!$language) {
        $language = cimne_get_current_language();
    }

    if (has_filter('wpml_object_id')) {
        $translated_id = apply_filters(
            'wpml_object_id',
            (int) $term->term_id,
            $taxonomy,
            false,
            $language
        );

        return $translated_id ? (int) $translated_id : 0;
    }

    return (int) $term->term_id;
}

/**
 * Resolve a canonical term slug to the translated term object for the current language.
 */
function cimne_get_translated_term_by_slug($canonical_slug, $taxonomy = 'category', $language = null) {
    $translated_id = cimne_get_translated_term_id_by_slug($canonical_slug, $taxonomy, $language);

    if (!$translated_id) {
        return null;
    }

    $term = get_term($translated_id, $taxonomy);

    if (!$term || is_wp_error($term)) {
        return null;
    }

    return $term;
}

/**
 * Resolve a canonical term slug to the translated slug for the current language.
 */
function cimne_get_translated_term_slug_by_canonical_slug($canonical_slug, $taxonomy = 'category', $fallback_slug = '') {
    $term = cimne_get_translated_term_by_slug($canonical_slug, $taxonomy);

    if ($term && !empty($term->slug)) {
        return $term->slug;
    }

    return $fallback_slug ?: $canonical_slug;
}

function cimne_get_current_event_root_slug() {
    return cimne_get_translated_term_slug_by_canonical_slug(
        cimne_get_canonical_event_root_slug(),
        'category',
        'events'
    );
}

function cimne_get_current_news_root_slug() {
    return cimne_get_translated_term_slug_by_canonical_slug(
        cimne_get_canonical_news_root_slug(),
        'category',
        'news'
    );
}

/**
 * Check whether the current category archive is the translated root term or one of its descendants.
 */
function cimne_is_current_category_under_translated_root($canonical_root_slug) {
    if (!is_category()) {
        return false;
    }

    $queried = get_queried_object();

    if (empty($queried->term_id)) {
        return false;
    }

    $translated_root_id = cimne_get_translated_term_id_by_slug($canonical_root_slug);

    if (!$translated_root_id) {
        return false;
    }

    if ((int) $queried->term_id === (int) $translated_root_id) {
        return true;
    }

    return cat_is_ancestor_of((int) $translated_root_id, (int) $queried->term_id);
}

/**
 * Fallback checker for terms that are not correctly linked as translations in WPML.
 */
function cimne_is_child_or_self_category_any($parent_slugs) {
    if (!is_category()) {
        return false;
    }

    $queried = get_queried_object();

    if (empty($queried->term_id) || empty($queried->slug)) {
        return false;
    }

    if (in_array($queried->slug, $parent_slugs, true)) {
        return true;
    }

    foreach ($parent_slugs as $parent_slug) {
        $parent = get_category_by_slug($parent_slug);

        if (
            $parent &&
            !is_wp_error($parent) &&
            cat_is_ancestor_of((int) $parent->term_id, (int) $queried->term_id)
        ) {
            return true;
        }
    }

    return false;
}

/**
 * Check whether a term is the translated root term or one of its descendants.
 */
function cimne_term_is_under_translated_root($term, $canonical_root_slug) {
    if (empty($term->term_id)) {
        return false;
    }

    $translated_root_id = cimne_get_translated_term_id_by_slug($canonical_root_slug);

    if (!$translated_root_id) {
        return false;
    }

    if ((int) $term->term_id === (int) $translated_root_id) {
        return true;
    }

    return cat_is_ancestor_of((int) $translated_root_id, (int) $term->term_id);
}
