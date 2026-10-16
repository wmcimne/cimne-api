<?php
$post_id = get_the_ID();

$event_type = '';
$attendance = get_field('attendance', $post_id);
$categories = get_the_terms($post_id, 'category');

/**
 * Event type: first category under the translated Events root.
 */
if (!empty($categories) && !is_wp_error($categories)) {
    foreach ($categories as $category) {
        if (cimne_term_is_under_translated_root($category, cimne_get_canonical_event_root_slug())) {
            $event_root_id = cimne_get_translated_term_id_by_slug(cimne_get_canonical_event_root_slug());

            if ((int) $category->term_id !== (int) $event_root_id) {
                $event_type = $category->name;
                break;
            }
        }
    }
}

/**
 * Normalize attendance.
 */
if (is_array($attendance)) {
    $attendance = reset($attendance);
}

$attendance_class = !empty($attendance) ? sanitize_title($attendance) : '';

/**
 * Dates.
 * - date is the mandatory reference/end date.
 * - start_date is optional and only used for visible ranges.
 */
$date_raw       = get_post_meta($post_id, CIMNE_EVENT_DATE_FIELD, true);
$start_date_raw = get_post_meta($post_id, CIMNE_EVENT_START_DATE_FIELD, true);

$date       = cimne_parse_event_date($date_raw);
$start_date = cimne_parse_event_date($start_date_raw);

$date_visible = '';

if ($date) {
    $date_visible = $date->format('d M Y');

    if ($start_date) {
        $date_visible = $start_date->format('d M Y') . ' - ' . $date_visible;
    }
}
?>

<article id="post-<?php echo esc_attr($post_id); ?>"
    <?php post_class('et_pb_post clearfix et_pb_no_thumb et_pb_blog_item_0_0'); ?>
    style="display: block; position: relative; padding-top: 50px;">

    <h5 class="entry-title">
        <a href="<?php the_permalink(); ?>" role="link">
            <?php the_title(); ?>
        </a>
    </h5>

    <?php if (!empty($event_type)) : ?>
        <div class="type-of-event rectangle">
            <span class="rectangle-text">
                <?php echo esc_html($event_type); ?>
            </span>
        </div>
    <?php endif; ?>

    <div class="post-content">

        <?php if (!empty($date_visible)) : ?>
            <div class="date">
                <?php echo esc_html($date_visible); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($attendance)) : ?>
            <div class="attendance <?php echo esc_attr($attendance_class); ?>">
                <?php echo esc_html($attendance); ?>
            </div>
        <?php endif; ?>

        <!--Bloque para el caso que se quiera el contenido del post, actualmente se muestra un extracto con un máximo de 28 palabras.
        <div class="post-content-inner et_multi_view_hidden">
            <p>
                <?php echo esc_html(wp_trim_words(get_the_excerpt(), 28)); ?>
            </p>
        </div> -->

    </div>

</article>
