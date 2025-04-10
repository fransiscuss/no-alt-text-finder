<?php
/**
 * Plugin Name: No Alt Text Finder
 * Plugin URI: https://github.com/fransiscuss/no-alt-text-finder#
 * Description: Finds all images without alt text in WordPress and WooCommerce and exports them to a CSV file.
 * Version: 1.0.0
 * Author: Fransiscus Setiawan
 * Author URI: https://fransiscuss.com
 * Text Domain: no-alt-text-finder
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin class
 */
class No_Alt_Text_Finder {
    
    /**
     * Plugin version
     * @var string
     */
    private $version = '1.0.0';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Set up hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_natf_export_csv', array($this, 'ajax_export_csv'));
    }
    
    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_management_page(
            __('No Alt Text Finder', 'no-alt-text-finder'),
            __('No Alt Text Finder', 'no-alt-text-finder'),
            'manage_options',
            'no-alt-text-finder',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ('tools_page_no-alt-text-finder' !== $hook) {
            return;
        }
        
        // Enqueue admin CSS
        wp_enqueue_style(
            'natf-admin-style',
            plugin_dir_url(__FILE__) . 'assets/css/admin-style.css',
            array(),
            $this->version
        );
        
        // Enqueue admin JS
        wp_enqueue_script(
            'natf-admin-script',
            plugin_dir_url(__FILE__) . 'assets/js/admin-script.js',
            array('jquery'),
            $this->version,
            true
        );
        
        // Add localized script data
        wp_localize_script('natf-admin-script', 'natf_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('natf_nonce'),
            'exporting_text' => __('Processing images, please wait...', 'no-alt-text-finder'),
            'export_complete' => __('Export complete!', 'no-alt-text-finder'),
            'export_error' => __('An error occurred during export.', 'no-alt-text-finder')
        ));
        
        // Create directories if they don't exist
        $this->create_assets_directories();
    }
    
    /**
     * Create the necessary asset directories and files if they don't exist
     */
    private function create_assets_directories() {
        // Create main assets directory
        $assets_dir = plugin_dir_path(__FILE__) . 'assets';
        if (!file_exists($assets_dir)) {
            wp_mkdir_p($assets_dir);
        }
        
        // Create CSS directory
        $css_dir = $assets_dir . '/css';
        if (!file_exists($css_dir)) {
            wp_mkdir_p($css_dir);
            
            // Create CSS file
            $css_content = "/* No Alt Text Finder Admin Styles */
.natf-progress {
    display: none;
    margin-top: 20px;
    padding: 10px;
    background: #f8f8f8;
    border: 1px solid #ddd;
    border-radius: 3px;
}
.natf-progress-bar {
    height: 20px;
    background-color: #0073aa;
    width: 0%;
    border-radius: 3px;
    transition: width 0.3s;
}
.natf-export-options {
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 3px;
}
.natf-results {
    margin-top: 20px;
    display: none;
}
.natf-error {
    color: #d63638;
    font-weight: bold;
}";
            file_put_contents($css_dir . '/admin-style.css', $css_content);
        }
        
        // Create JS directory
        $js_dir = $assets_dir . '/js';
        if (!file_exists($js_dir)) {
            wp_mkdir_p($js_dir);
            
            // Create JS file
            $js_content = "/* No Alt Text Finder Admin Scripts */
jQuery(document).ready(function($) {
    // Handle form submission
    $('#natf-form').on('submit', function(e) {
        e.preventDefault();
        
        // Show progress bar
        $('.natf-progress').show();
        $('.natf-results').hide();
        
        // Get form data
        var formData = $(this).serialize();
        
        // Send AJAX request
        $.ajax({
            url: natf_data.ajax_url,
            type: 'POST',
            data: formData + '&action=natf_export_csv&nonce=' + natf_data.nonce,
            dataType: 'json',
            beforeSend: function() {
                $('#natf-submit').prop('disabled', true);
                $('.natf-progress-bar').css('width', '0%');
                $('.natf-status').text(natf_data.exporting_text);
            },
            success: function(response) {
                $('.natf-progress-bar').css('width', '100%');
                
                if (response.success) {
                    $('.natf-status').text(natf_data.export_complete);
                    $('.natf-results').html('<p>' + response.data.message + '</p>').show();
                    
                    if (response.data.download_url) {
                        window.location.href = response.data.download_url;
                    }
                } else {
                    $('.natf-status').html('<span class=\"natf-error\">' + response.data.message + '</span>');
                }
            },
            error: function() {
                $('.natf-status').html('<span class=\"natf-error\">' + natf_data.export_error + '</span>');
            },
            complete: function() {
                $('#natf-submit').prop('disabled', false);
            }
        });
    });
});";
            file_put_contents($js_dir . '/admin-script.js', $js_content);
        }
    }
    
    /**
     * Render the admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('No Alt Text Finder', 'no-alt-text-finder'); ?></h1>
            
            <div class="natf-export-options">
                <p><?php _e('Find all images without alt text and export them to a CSV file.', 'no-alt-text-finder'); ?></p>
                
                <form id="natf-form" method="post">
                    <?php wp_nonce_field('natf_nonce', 'natf_nonce'); ?>
                    <input type="hidden" name="action" value="natf_export_csv">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Include Media Library Images', 'no-alt-text-finder'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="include_media_library" value="1" checked>
                                    <?php _e('Check all images in the Media Library', 'no-alt-text-finder'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Include Post/Page Content', 'no-alt-text-finder'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="include_post_content" value="1" checked>
                                    <?php _e('Check images embedded in post and page content', 'no-alt-text-finder'); ?>
                                </label>
                            </td>
                        </tr>
                        <?php if (class_exists('WooCommerce')) : ?>
                        <tr>
                            <th scope="row"><?php _e('Include WooCommerce Images', 'no-alt-text-finder'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="include_woo_products" value="1" checked>
                                    <?php _e('Check WooCommerce product images', 'no-alt-text-finder'); ?>
                                </label>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th scope="row"><?php _e('Batch Size', 'no-alt-text-finder'); ?></th>
                            <td>
                                <select name="batch_size">
                                    <option value="50">50</option>
                                    <option value="100" selected>100</option>
                                    <option value="250">250</option>
                                    <option value="500">500</option>
                                </select>
                                <p class="description"><?php _e('Number of items to process per batch. Use a smaller number if you experience timeouts.', 'no-alt-text-finder'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Find Images Without Alt Text', 'no-alt-text-finder'), 'primary', 'natf-submit'); ?>
                </form>
            </div>
            
            <div class="natf-progress">
                <div class="natf-progress-bar"></div>
                <p class="natf-status"><?php _e('Processing...', 'no-alt-text-finder'); ?></p>
            </div>
            
            <div class="natf-results"></div>
        </div>
        <?php
    }
    
    /**
     * Handle AJAX export request
     */
    public function ajax_export_csv() {
        // Check nonce
        if (!isset($_POST['natf_nonce']) || !wp_verify_nonce($_POST['natf_nonce'], 'natf_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'no-alt-text-finder')
            ));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'no-alt-text-finder')
            ));
        }
        
        try {
            // Get options
            $include_media_library = isset($_POST['include_media_library']) && $_POST['include_media_library'] === '1';
            $include_post_content = isset($_POST['include_post_content']) && $_POST['include_post_content'] === '1';
            $include_woo_products = isset($_POST['include_woo_products']) && $_POST['include_woo_products'] === '1';
            $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 100;
            
            // Initialize results
            $results = array();
            
            // 1. Check Media Library
            if ($include_media_library) {
                $media_results = $this->find_media_library_images_without_alt($batch_size);
                $results = array_merge($results, $media_results);
            }
            
            // 2. Check post content for inline images
            if ($include_post_content) {
                $post_content_results = $this->find_post_content_images_without_alt($batch_size);
                $results = array_merge($results, $post_content_results);
            }
            
            // 3. Check WooCommerce product images
            if ($include_woo_products && class_exists('WooCommerce')) {
                $woo_results = $this->find_woocommerce_images_without_alt($batch_size);
                $results = array_merge($results, $woo_results);
            }
            
            // Generate and save the CSV file
            $upload_dir = wp_upload_dir();
            $csv_dir = $upload_dir['basedir'] . '/no-alt-text-finder';
            
            // Create directory if it doesn't exist
            if (!file_exists($csv_dir)) {
                wp_mkdir_p($csv_dir);
                
                // Create index.php file to prevent directory listing
                file_put_contents($csv_dir . '/index.php', '<?php // Silence is golden');
                
                // Create .htaccess file to protect directory
                file_put_contents($csv_dir . '/.htaccess', 'Deny from all');
            }
            
            // Create CSV file
            $filename = 'images-without-alt-text-' . date('Y-m-d-H-i-s') . '.csv';
            $csv_file = $csv_dir . '/' . $filename;
            $csv_url = $upload_dir['baseurl'] . '/no-alt-text-finder/' . $filename;
            
            $fp = fopen($csv_file, 'w');
            
            // Add CSV headers
            fputcsv($fp, array(
                __('Image ID', 'no-alt-text-finder'),
                __('Image URL', 'no-alt-text-finder'),
                __('Image Title', 'no-alt-text-finder'),
                __('Attached To', 'no-alt-text-finder'),
                __('Edit URL', 'no-alt-text-finder')
            ));
            
            // Add data rows
            foreach ($results as $result) {
                fputcsv($fp, $result);
            }
            
            fclose($fp);
            
            // Create a temporary file for download
            $temp_file = $upload_dir['basedir'] . '/no-alt-text-finder/temp-' . md5(time()) . '.csv';
            copy($csv_file, $temp_file);
            
            // Return success
            if (count($results) > 0) {
                wp_send_json_success(array(
                    'message' => sprintf(
                        __('Found %d images without alt text. <a href="%s" target="_blank">Download CSV</a>', 'no-alt-text-finder'),
                        count($results),
                        esc_url($csv_url)
                    ),
                    'count' => count($results),
                    'download_url' => $csv_url
                ));
            } else {
                wp_send_json_success(array(
                    'message' => __('Great news! No images without alt text were found.', 'no-alt-text-finder'),
                    'count' => 0
                ));
            }
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => sprintf(__('Error: %s', 'no-alt-text-finder'), $e->getMessage())
            ));
        }
    }
    
    /**
     * Find Media Library images without alt text
     *
     * @param int $batch_size
     * @return array
     */
    private function find_media_library_images_without_alt($batch_size = 100) {
        $results = array();
        
        try {
            $args = array(
                'post_type' => 'attachment',
                'post_mime_type' => 'image',
                'post_status' => 'inherit',
                'posts_per_page' => $batch_size,
                'fields' => 'ids',
            );
            
            $query = new WP_Query($args);
            
            if ($query->have_posts()) {
                foreach ($query->posts as $image_id) {
                    $alt_text = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                    
                    if (empty($alt_text)) {
                        $image_url = wp_get_attachment_url($image_id);
                        $image_title = get_the_title($image_id);
                        $parent_id = wp_get_post_parent_id($image_id);
                        $parent_title = ($parent_id) ? get_the_title($parent_id) : __('None', 'no-alt-text-finder');
                        $edit_url = get_edit_post_link($image_id, '');
                        
                        $results[] = array(
                            $image_id,
                            $image_url,
                            $image_title,
                            $parent_title,
                            $edit_url
                        );
                    }
                }
            }
            
            wp_reset_postdata();
        } catch (Exception $e) {
            error_log('No Alt Text Finder - Media Library Error: ' . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Find images in post content without alt text
     *
     * @param int $batch_size
     * @return array
     */
    private function find_post_content_images_without_alt($batch_size = 100) {
        $results = array();
        
        try {
            // Get all posts and pages
            $args = array(
                'post_type' => array('post', 'page'),
                'post_status' => 'publish',
                'posts_per_page' => $batch_size,
            );
            
            $query = new WP_Query($args);
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $post_id = get_the_ID();
                    $content = get_the_content();
                    
                    // Find all img tags in the content
                    preg_match_all('/<img[^>]+>/i', $content, $img_tags);
                    
                    foreach ($img_tags[0] as $img_tag) {
                        // Check if the img tag has an alt attribute
                        if (!preg_match('/alt=(["\'])(.*?)\1/i', $img_tag) || preg_match('/alt=["\']\s*["\']/', $img_tag)) {
                            // Extract the src attribute
                            preg_match('/src=(["\'])(.*?)\1/i', $img_tag, $src_match);
                            
                            if (isset($src_match[2])) {
                                $image_url = $src_match[2];
                                $post_title = get_the_title($post_id);
                                $edit_url = get_edit_post_link($post_id, '');
                                
                                // Try to get the image ID from URL
                                $image_id = attachment_url_to_postid($image_url);
                                
                                $results[] = array(
                                    $image_id ?: __('N/A (Inline)', 'no-alt-text-finder'),
                                    $image_url,
                                    __('Inline image in content', 'no-alt-text-finder'),
                                    $post_title,
                                    $edit_url
                                );
                            }
                        }
                    }
                }
            }
            
            wp_reset_postdata();
        } catch (Exception $e) {
            error_log('No Alt Text Finder - Post Content Error: ' . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Find WooCommerce product images without alt text
     *
     * @param int $batch_size
     * @return array
     */
    private function find_woocommerce_images_without_alt($batch_size = 100) {
        $results = array();
        
        try {
            if (!class_exists('WooCommerce')) {
                return $results;
            }
            
            // Get all products
            $args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => $batch_size,
            );
            
            $query = new WP_Query($args);
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $product_id = get_the_ID();
                    
                    // Use try-catch to handle potential WooCommerce API errors
                    try {
                        $product = wc_get_product($product_id);
                        
                        if (!$product) {
                            continue;
                        }
                        
                        // Check featured image
                        $featured_image_id = $product->get_image_id();
                        if ($featured_image_id) {
                            $alt_text = get_post_meta($featured_image_id, '_wp_attachment_image_alt', true);
                            
                            if (empty($alt_text)) {
                                $image_url = wp_get_attachment_url($featured_image_id);
                                $image_title = get_the_title($featured_image_id);
                                $product_title = $product->get_name();
                                $edit_url = get_edit_post_link($featured_image_id, '');
                                
                                $results[] = array(
                                    $featured_image_id,
                                    $image_url,
                                    $image_title . ' (' . __('Featured', 'no-alt-text-finder') . ')',
                                    $product_title,
                                    $edit_url
                                );
                            }
                        }
                        
                        // Check gallery images
                        $gallery_image_ids = $product->get_gallery_image_ids();
                        if (!empty($gallery_image_ids)) {
                            foreach ($gallery_image_ids as $gallery_image_id) {
                                $alt_text = get_post_meta($gallery_image_id, '_wp_attachment_image_alt', true);
                                
                                if (empty($alt_text)) {
                                    $image_url = wp_get_attachment_url($gallery_image_id);
                                    $image_title = get_the_title($gallery_image_id);
                                    $product_title = $product->get_name();
                                    $edit_url = get_edit_post_link($gallery_image_id, '');
                                    
                                    $results[] = array(
                                        $gallery_image_id,
                                        $image_url,
                                        $image_title . ' (' . __('Gallery', 'no-alt-text-finder') . ')',
                                        $product_title,
                                        $edit_url
                                    );
                                }
                            }
                        }
                    } catch (Exception $e) {
                        error_log('No Alt Text Finder - WooCommerce Product Error (ID ' . $product_id . '): ' . $e->getMessage());
                        continue;
                    }
                }
            }
            
            wp_reset_postdata();
        } catch (Exception $e) {
            error_log('No Alt Text Finder - WooCommerce Error: ' . $e->getMessage());
        }
        
        return $results;
    }
}

// Initialize the plugin
$no_alt_text_finder = new No_Alt_Text_Finder();
