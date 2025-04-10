<?php
/**
 * CSV Download Handler for No Alt Text Finder
 */

// Verify this is being accessed within WordPress
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Security: Verify nonce
if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'natf_download_nonce')) {
    wp_die('Security check failed.');
}

// Security: Verify user permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this file.');
}

// Get file path
$filename = isset($_GET['file']) ? sanitize_file_name($_GET['file']) : '';
if (empty($filename)) {
    wp_die('Invalid file name.');
}

// Validate the file is from our plugin
if (strpos($filename, 'images-without-alt-text-') !== 0 || pathinfo($filename, PATHINFO_EXTENSION) !== 'csv') {
    wp_die('Invalid file type.');
}

// Get the upload directory
$upload_dir = wp_upload_dir();
$file_path = $upload_dir['basedir'] . '/no-alt-text-finder/' . $filename;

// Make sure the file exists
if (!file_exists($file_path)) {
    wp_die('File not found.');
}

// Set headers for download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($file_path));
header('Pragma: no-cache');
header('Expires: 0');

// Read the file and output it to the browser
readfile($file_path);
exit;