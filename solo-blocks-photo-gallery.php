<?php
/**
 * Plugin Name: Solo Blocks Photo Gallery
 * Plugin URI: https://soloblocks.com/
 * Description: Solo Blocks Photo Gallery - is a collection of the galleries for the Gutenberg WordPress editor.
 * Version: 1.0.6.1
 * Author: Solo Blocks
 * Text Domain: solo-gallery-photo-textdomain
 * Domain Path:  /languages
 */

// Exit if accessed directly.
defined('ABSPATH') OR exit;

function solo_blocks_photo_gallery__meta_links( $meta_fields, $file ) {
	if ( plugin_basename( __FILE__ ) == $file ) {
		$meta_fields[] = "<a href='https://wordpress.org/support/plugin/solo-blocks-photo-gallery' target='_blank'>" . esc_html__( 'Support Forum', 'solo-gallery-photo-textdomain' ) . "</a>";
		$meta_fields[] = "<a href='https://wordpress.org/support/plugin/solo-blocks-photo-gallery/reviews/#new-post' target='_blank' title='" . esc_html__( 'Rate', 'solo-gallery-photo-textdomain' ) . "'>
            <i class='gt3-rate-stars'>"
		                 . "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>"
		                 . "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>"
		                 . "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>"
		                 . "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>"
		                 . "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>"
		                 . "</i></a>";

		echo "<style>"
		     . ".gt3-rate-stars{display:inline-block;color: #ffb900;position:relative;top:3px;}"
		     . ".gt3-rate-stars svg{fill: #ffb900;}"
		     . ".gt3-rate-stars svg:hover{fill: #ffb900}"
		     . ".gt3-rate-stars svg:hover ~ svg{fill:none;}"
		     . "</style>";
	}

	return $meta_fields;
}

add_filter( "plugin_row_meta_disable", 'solo_blocks_photo_gallery__meta_links', 10, 2 );

if(!version_compare(PHP_VERSION, '5.6', '>=')) {
	add_action('admin_notices', 'solo_blocks_photo_gallery__fail_php_version');
} else {
	require_once __DIR__.'/loader.php';
}

function solo_blocks_photo_gallery__fail_php_version() {
	$message      = sprintf('Solo Blocks Photo Gallery requires PHP version %1$s+, plugin is currently NOT ACTIVE.', '5.6');
	$html_message = sprintf('<div class="error">%s</div>', wpautop($message));
	echo wp_kses_post($html_message);
}

