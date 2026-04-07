<?php
/**
 * Plugin Name: _PCPI PDF Templates
 * Description: Registers custom Gravity PDF templates for PCPI workflows.
 * Version: 1.0.6
 * Author: Gregg Franklin, Marc Benzakein
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'PCPI_PDF_TEMPLATES_VERSION', '1.0.6' );
define( 'PCPI_PDF_TEMPLATES_PATH', plugin_dir_path( __FILE__ ) );
define( 'PCPI_PDF_TEMPLATES_URL',  plugin_dir_url( __FILE__ ) );

/**
 * Scan all subdirectories under templates/ for <name>/<name>.php files.
 */
add_filter( 'gfpdf_unfiltered_template_list', function( $raw_templates ) {

    $our_templates = glob( PCPI_PDF_TEMPLATES_PATH . 'templates/*/*.php', GLOB_NOSORT );

    // Keep only files where the filename matches the folder name
    // e.g. templates/pcpi-ch/pcpi-ch.php — prevents picking up helper files
    $our_templates = array_filter( $our_templates, function( $file ) {
        $folder = basename( dirname( $file ) );
        $name   = basename( $file, '.php' );
        return $folder === $name;
    } );

    if ( ! empty( $our_templates ) ) {
        $raw_templates[] = array_values( $our_templates );
    }

    return $raw_templates;

} );

/**
 * Register each template subfolder as an image path so previews work.
 * Gravity PDF's get_template_image() loops $image_paths as [ url => path ]
 * and looks for <template-name>.png inside each.
 */
add_filter( 'gfpdf_template_image_paths', function( $image_paths ) {

    $dirs = glob( PCPI_PDF_TEMPLATES_PATH . 'templates/*', GLOB_ONLYDIR );

    foreach ( $dirs as $dir ) {
        $folder = basename( $dir );
        $image_paths[ PCPI_PDF_TEMPLATES_URL . 'templates/' . $folder . '/' ]
            = trailingslashit( $dir );
    }

    return $image_paths;

} );

/**
 * When Gravity PDF tries to load a template for PDF generation, it looks in
 * hardcoded locations then calls this filter before throwing an exception.
 * We resolve any template ID that lives in our subdirectory structure.
 */
add_filter( 'gfpdf_fallback_template_path_by_id', function( $false, $template_id ) {

    $path = PCPI_PDF_TEMPLATES_PATH . 'templates/' . $template_id . '/' . $template_id . '.php';

    if ( file_exists( $path ) ) {
        return realpath( $path );
    }

    return $false;

}, 10, 2 );

/**
 * Bust the template list transient on activation.
 */
register_activation_hook( __FILE__, function() {
    delete_transient( 'gfpdf_' . get_current_blog_id() . '-template-list' );
} );