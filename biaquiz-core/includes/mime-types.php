<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Allow CSV and JSON uploads.
 *
 * @param array $mimes Existing allowed mime types.
 * @return array Modified mime types.
 */
function biaquiz_core_allowed_mimes($mimes) {
    $mimes['csv']  = 'text/csv';
    $mimes['json'] = 'application/json';
    return $mimes;
}
add_filter('upload_mimes', 'biaquiz_core_allowed_mimes');
