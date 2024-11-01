<?php

defined('ABSPATH') OR exit;

require_once __DIR__.'/assets.php';


// Blocks render
require_once __DIR__.'/basic_block.php';
foreach ( glob( __DIR__.'/block/*.php' ) as $block_logic ) {
	if ( is_file( $block_logic ) && is_readable( $block_logic ) ) {
		require_once $block_logic;
	}
}

add_action( 'after_setup_theme', function () {
	// Activate Full Width Block
	add_theme_support( 'align-wide' );

	// Activate Featured Image
//	add_theme_support( 'post-thumbnails' );
} );

