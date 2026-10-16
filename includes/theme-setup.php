<?php
/**
 * remove Widget Block Editor Support
 *
 * @package CIMNE_Child
 */

function cimne_theme_support() {
    remove_theme_support( 'widgets-block-editor');
}
add_action( 'after_setup_theme', 'cimne_theme_support');
