<?php
/*
Plugin Name: Scriblio Authority
Plugin URI: http://about.scriblio.net/
Version: a1
Author: Casey Bisson
Author URI: http://maisonbisson.com/blog/
*/

// include required components
require_once dirname( __FILE__ ) . '/components/class-authority.php';
require_once dirname( __FILE__ ) . '/components/class-authority-posttype.php';
require_once dirname( __FILE__ ) . '/components/class-authority-easyterms.php';
require_once dirname( __FILE__ ) . '/components/class-authority-csv-parser.php';

add_action( 'init', function() {
	Authority::init();

	Authority::easy_terms()->add_taxonomy('company');
	Authority::easy_terms()->add_taxonomy('technology');

	Authority::supported_taxonomies( array(
		'post_tag',
		'company',
		'technology',
	));
});
