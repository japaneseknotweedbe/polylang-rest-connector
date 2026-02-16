<?php
/**
 * Plugin Name: Polylang REST API Connector
 * Description: Exposes Polylang language data and translation linking capabilities to the WordPress REST API for external automation (n8n, Zapier, etc).
 * Version: 1.0.0
 * Author: japaneseknotweed
 * Author URI: https://example.com
 * License: GPL-2.0+
 */

defined( 'ABSPATH' ) || exit;

/**
 * Initialize REST API extensions only if Polylang is active.
 */
add_action( 'rest_api_init', 'prc_init_rest_fields' );

function prc_init_rest_fields() {
	// Safety Check: Ensure Polylang is active to avoid fatal errors
	if ( ! function_exists( 'pll_default_language' ) ) {
		return;
	}

	// Get all public post types (posts, pages, etc.)
	$post_types = get_post_types( array( 'public' => true ), 'names' );

	foreach ( $post_types as $post_type ) {
		
		// 1. READ SUPPORT: Register 'lang' field
		register_rest_field( $post_type, 'lang', array(
			'get_callback' => 'prc_get_post_language',
			'schema'       => array(
				'description' => 'Polylang language slug (e.g., en, fr)',
				'type'        => 'string',
			),
		));

		// 2. READ SUPPORT: Register 'translations' field
		register_rest_field( $post_type, 'translations', array(
			'get_callback' => 'prc_get_post_translations',
			'schema'       => array(
				'description' => 'Object of linked translation IDs keyed by language slug',
				'type'        => 'object',
			),
		));

		// 3. WRITE SUPPORT: Hook into insert/update actions
		add_action( "rest_insert_{$post_type}", 'prc_handle_rest_save', 10, 3 );
	}
}

/**
 * GET Callback: Retrieve language slug for the post.
 *
 * @param array $object     Details of current post.
 * @return string|null      Language slug or null.
 */
function prc_get_post_language( $object ) {
	$lang = pll_get_post_language( $object['id'], 'slug' );
	return $lang ? $lang : null;
}

/**
 * GET Callback: Retrieve translations object for the post.
 *
 * @param array $object     Details of current post.
 * @return array            Associative array ['en' => 123, 'fr' => 456].
 */
function prc_get_post_translations( $object ) {
	$translations = pll_get_post_translations( $object['id'] );
	return ! empty( $translations ) ? $translations : array();
}

/**
 * WRITE Callback: Handle language setting and linking on POST/PUT.
 *
 * @param WP_Post         $post     The post object being created/updated.
 * @param WP_REST_Request $request  The request object.
 * @param bool            $creating True if creating, false if updating.
 */
function prc_handle_rest_save( $post, $request, $creating ) {
	$post_id = $post->ID;

	// 1. Handle 'lang' parameter: Set the language of the current post
	if ( $request->has_param( 'lang' ) ) {
		$lang_slug = $request->get_param( 'lang' );
		pll_set_post_language( $post_id, $lang_slug );
	}

	// 2. Handle 'translation_of' parameter: Link to an original post
	if ( $request->has_param( 'translation_of' ) ) {
		$original_post_id = absint( $request->get_param( 'translation_of' ) );

		if ( $original_post_id && $original_post_id !== $post_id ) {
			// Get existing translations of the original post
			$existing_translations = pll_get_post_translations( $original_post_id );

			// Get the language of the CURRENT post (required for the mapping array)
			// We check the request param first, then fallback to stored value
			$current_lang = $request->has_param( 'lang' ) 
				? $request->get_param( 'lang' ) 
				: pll_get_post_language( $post_id, 'slug' );

			if ( $current_lang ) {
				// Add the current post to the translation list
				$existing_translations[ $current_lang ] = $post_id;

				// Save the updated association
				pll_save_post_translations( $existing_translations );
			}
		}
	}
}
