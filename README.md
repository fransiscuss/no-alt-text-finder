# No Alt Text Finder

A WordPress plugin to find and export all images without alt text to a CSV file for SEO and accessibility improvements.

## Description

No Alt Text Finder scans your WordPress site for images that are missing alt text attributes and exports them to a CSV file. This helps you identify and fix accessibility issues on your site while improving SEO for your images.

The plugin can scan:
- WordPress Media Library images
- Inline images in post/page content
- WooCommerce product images (featured and gallery)

## Features

- Easy-to-use interface in the WordPress admin
- Export results to a CSV file with image details
- Batch processing to prevent timeouts on large sites
- Works with WooCommerce product images
- Detailed error handling and logging
- AJAX-based processing with progress indication

## Installation

### From GitHub

1. Download or clone this repository
2. Upload the entire folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress

### Manual Installation

1. Download the `no-alt-text-finder.php` file
2. Create a folder named `no-alt-text-finder` in your `/wp-content/plugins/` directory
3. Upload the PHP file to this folder
4. Activate the plugin through the 'Plugins' menu in WordPress

## Usage

1. After activation, go to **Tools → No Alt Text Finder** in your WordPress admin
2. Select which image types to check:
   - Media Library images
   - Images in post/page content
   - WooCommerce product images (if WooCommerce is installed)
3. Set the batch size (smaller for slower servers)
4. Click "Find Images Without Alt Text"
5. The plugin will process your images and generate a downloadable CSV

## CSV Output Format

The CSV file contains the following columns:
- **Image ID**: WordPress media ID (if available)
- **Image URL**: Direct link to the image
- **Image Title**: The title of the image in WordPress
- **Attached To**: Post, page or product the image is used in
- **Edit URL**: Link to edit the image in WordPress admin

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher

## Frequently Asked Questions

### Why is alt text important?

Alt text (alternative text) serves several important purposes:
- Helps screen readers describe images to visually impaired users
- Displays when images fail to load
- Helps search engines understand and index your images
- Improves your overall SEO ranking

### How does the plugin handle large sites?

The plugin uses batch processing to handle sites with many images. You can adjust the batch size to match your server's capabilities.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This plugin is licensed under the GPL v2 or later.