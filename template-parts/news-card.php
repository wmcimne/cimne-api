<?php
$post_id = get_the_ID();
$news_type = '';
$categories = get_the_terms($post_id, 'category');

/**
 * News type: first category under the translated News root.
 */
if (!empty($categories) && !is_wp_error($categories)) {
    foreach ($categories as $category) {
        if (cimne_term_is_under_translated_root($category, cimne_get_canonical_news_root_slug())) {
            $news_root_id = cimne_get_translated_term_id_by_slug(cimne_get_canonical_news_root_slug());

            if ((int) $category->term_id !== (int) $news_root_id) {
                $news_type = $category->name;
                break;
            }
        }
    }
}

if (empty($news_type)) {
    $news_root = cimne_get_translated_term_by_slug(cimne_get_canonical_news_root_slug());
    $news_type = $news_root && !empty($news_root->name) ? $news_root->name : 'News';
}
?>

<article id="post-<?php echo esc_attr($post_id); ?>"
    <?php post_class('et_pb_post clearfix et_pb_blog_item_0_0 turquoise'); ?>
    style="display: block;">

    <?php if (has_post_thumbnail()) : ?>
        <a href="<?php the_permalink(); ?>" class="entry-featured-image-url" role="link">

            <?php if (!empty($news_type)) : ?>
                <div class="rectangle">
                    <span class="rectangle-text">
                        <?php echo esc_html($news_type); ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php the_post_thumbnail('large'); ?>

            <img
                src="/wp-content/uploads/2025/02/cross-bg-transparent.png"
                toggle-src="/wp-content/uploads/2025/03/dash-bg-transparent.png"
                alt="square-cross-form"
                class="square-form-img"
            >

        </a>
    <?php endif; ?>

    <h5 class="entry-title">
        <a href="<?php the_permalink(); ?>" role="link">
            <?php the_title(); ?>
        </a>
    </h5>

    <p class="post-meta">
        <span class="published">
            <?php echo esc_html(get_the_date('Y/m/d')); ?>
        </span>
    </p>

    <!--
    // Bloque para el caso que se quiera el contenido del post, actualmente se muestra un extracto con un máximo de 28 palabras.
    // <div class="post-content">
    //     <div class="date" date=""></div>

    //     <div class="post-content-inner et_multi_view_hidden">
    //         <p>
    //             <?php echo esc_html(wp_trim_words(get_the_excerpt(), 28)); ?>
    //         </p>
    //     </div>
    // </div>
    -->
</article>
