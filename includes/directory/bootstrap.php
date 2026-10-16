<?php
/**
 * Bootstrap for CIMNE Directory integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/role-overrides.php';
require_once __DIR__ . '/normalizers.php';
require_once __DIR__ . '/directory.php';
require_once __DIR__ . '/filters.php';
require_once __DIR__ . '/profile.php';
require_once __DIR__ . '/assets.php';
