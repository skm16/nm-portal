<?php
/**
 * NMDA Custom Post Types
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Business Custom Post Type
 */
function nmda_register_business_post_type() {
    $labels = array(
        'name'                  => _x( 'Businesses', 'Post Type General Name', 'nmda-understrap' ),
        'singular_name'         => _x( 'Business', 'Post Type Singular Name', 'nmda-understrap' ),
        'menu_name'             => __( 'Businesses', 'nmda-understrap' ),
        'name_admin_bar'        => __( 'Business', 'nmda-understrap' ),
        'archives'              => __( 'Business Archives', 'nmda-understrap' ),
        'attributes'            => __( 'Business Attributes', 'nmda-understrap' ),
        'parent_item_colon'     => __( 'Parent Business:', 'nmda-understrap' ),
        'all_items'             => __( 'All Businesses', 'nmda-understrap' ),
        'add_new_item'          => __( 'Add New Business', 'nmda-understrap' ),
        'add_new'               => __( 'Add New', 'nmda-understrap' ),
        'new_item'              => __( 'New Business', 'nmda-understrap' ),
        'edit_item'             => __( 'Edit Business', 'nmda-understrap' ),
        'update_item'           => __( 'Update Business', 'nmda-understrap' ),
        'view_item'             => __( 'View Business', 'nmda-understrap' ),
        'view_items'            => __( 'View Businesses', 'nmda-understrap' ),
        'search_items'          => __( 'Search Business', 'nmda-understrap' ),
        'not_found'             => __( 'Not found', 'nmda-understrap' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'nmda-understrap' ),
        'featured_image'        => __( 'Business Logo', 'nmda-understrap' ),
        'set_featured_image'    => __( 'Set business logo', 'nmda-understrap' ),
        'remove_featured_image' => __( 'Remove business logo', 'nmda-understrap' ),
        'use_featured_image'    => __( 'Use as business logo', 'nmda-understrap' ),
        'insert_into_item'      => __( 'Insert into business', 'nmda-understrap' ),
        'uploaded_to_this_item' => __( 'Uploaded to this business', 'nmda-understrap' ),
        'items_list'            => __( 'Businesses list', 'nmda-understrap' ),
        'items_list_navigation' => __( 'Businesses list navigation', 'nmda-understrap' ),
        'filter_items_list'     => __( 'Filter businesses list', 'nmda-understrap' ),
    );

    $args = array(
        'label'                 => __( 'Business', 'nmda-understrap' ),
        'description'           => __( 'NMDA Member Businesses', 'nmda-understrap' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'revisions' ),
        'taxonomies'            => array( 'business_category', 'product_type' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-store',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => 'directory',
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'show_in_rest'          => true,
        'rewrite'               => array( 'slug' => 'business' ),
    );

    register_post_type( 'nmda_business', $args );
}
add_action( 'init', 'nmda_register_business_post_type', 0 );

/**
 * Register Business Category Taxonomy
 */
function nmda_register_business_category_taxonomy() {
    $labels = array(
        'name'                       => _x( 'Business Categories', 'Taxonomy General Name', 'nmda-understrap' ),
        'singular_name'              => _x( 'Business Category', 'Taxonomy Singular Name', 'nmda-understrap' ),
        'menu_name'                  => __( 'Categories', 'nmda-understrap' ),
        'all_items'                  => __( 'All Categories', 'nmda-understrap' ),
        'parent_item'                => __( 'Parent Category', 'nmda-understrap' ),
        'parent_item_colon'          => __( 'Parent Category:', 'nmda-understrap' ),
        'new_item_name'              => __( 'New Category Name', 'nmda-understrap' ),
        'add_new_item'               => __( 'Add New Category', 'nmda-understrap' ),
        'edit_item'                  => __( 'Edit Category', 'nmda-understrap' ),
        'update_item'                => __( 'Update Category', 'nmda-understrap' ),
        'view_item'                  => __( 'View Category', 'nmda-understrap' ),
        'separate_items_with_commas' => __( 'Separate categories with commas', 'nmda-understrap' ),
        'add_or_remove_items'        => __( 'Add or remove categories', 'nmda-understrap' ),
        'choose_from_most_used'      => __( 'Choose from the most used', 'nmda-understrap' ),
        'popular_items'              => __( 'Popular Categories', 'nmda-understrap' ),
        'search_items'               => __( 'Search Categories', 'nmda-understrap' ),
        'not_found'                  => __( 'Not Found', 'nmda-understrap' ),
        'no_terms'                   => __( 'No categories', 'nmda-understrap' ),
        'items_list'                 => __( 'Categories list', 'nmda-understrap' ),
        'items_list_navigation'      => __( 'Categories list navigation', 'nmda-understrap' ),
    );

    $args = array(
        'labels'                     => $labels,
        'hierarchical'               => true,
        'public'                     => true,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => true,
        'show_tagcloud'              => true,
        'show_in_rest'               => true,
        'rewrite'                    => array( 'slug' => 'category' ),
    );

    register_taxonomy( 'business_category', array( 'nmda_business' ), $args );
}
add_action( 'init', 'nmda_register_business_category_taxonomy', 0 );

/**
 * Register Product Type Taxonomy
 */
function nmda_register_product_type_taxonomy() {
    $labels = array(
        'name'                       => _x( 'Product Types', 'Taxonomy General Name', 'nmda-understrap' ),
        'singular_name'              => _x( 'Product Type', 'Taxonomy Singular Name', 'nmda-understrap' ),
        'menu_name'                  => __( 'Product Types', 'nmda-understrap' ),
        'all_items'                  => __( 'All Product Types', 'nmda-understrap' ),
        'parent_item'                => __( 'Parent Product Type', 'nmda-understrap' ),
        'parent_item_colon'          => __( 'Parent Product Type:', 'nmda-understrap' ),
        'new_item_name'              => __( 'New Product Type Name', 'nmda-understrap' ),
        'add_new_item'               => __( 'Add New Product Type', 'nmda-understrap' ),
        'edit_item'                  => __( 'Edit Product Type', 'nmda-understrap' ),
        'update_item'                => __( 'Update Product Type', 'nmda-understrap' ),
        'view_item'                  => __( 'View Product Type', 'nmda-understrap' ),
        'separate_items_with_commas' => __( 'Separate types with commas', 'nmda-understrap' ),
        'add_or_remove_items'        => __( 'Add or remove product types', 'nmda-understrap' ),
        'choose_from_most_used'      => __( 'Choose from the most used', 'nmda-understrap' ),
        'popular_items'              => __( 'Popular Product Types', 'nmda-understrap' ),
        'search_items'               => __( 'Search Product Types', 'nmda-understrap' ),
        'not_found'                  => __( 'Not Found', 'nmda-understrap' ),
        'no_terms'                   => __( 'No product types', 'nmda-understrap' ),
        'items_list'                 => __( 'Product types list', 'nmda-understrap' ),
        'items_list_navigation'      => __( 'Product types list navigation', 'nmda-understrap' ),
    );

    $args = array(
        'labels'                     => $labels,
        'hierarchical'               => true,
        'public'                     => true,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => true,
        'show_tagcloud'              => true,
        'show_in_rest'               => true,
        'rewrite'                    => array( 'slug' => 'product' ),
    );

    register_taxonomy( 'product_type', array( 'nmda_business' ), $args );
}
add_action( 'init', 'nmda_register_product_type_taxonomy', 0 );

/**
 * Register Resource Custom Post Type
 */
function nmda_register_resource_post_type() {
    $labels = array(
        'name'                  => _x( 'Resources', 'Post Type General Name', 'nmda-understrap' ),
        'singular_name'         => _x( 'Resource', 'Post Type Singular Name', 'nmda-understrap' ),
        'menu_name'             => __( 'Resources', 'nmda-understrap' ),
        'name_admin_bar'        => __( 'Resource', 'nmda-understrap' ),
        'all_items'             => __( 'All Resources', 'nmda-understrap' ),
        'add_new_item'          => __( 'Add New Resource', 'nmda-understrap' ),
        'add_new'               => __( 'Add New', 'nmda-understrap' ),
        'new_item'              => __( 'New Resource', 'nmda-understrap' ),
        'edit_item'             => __( 'Edit Resource', 'nmda-understrap' ),
        'update_item'           => __( 'Update Resource', 'nmda-understrap' ),
        'view_item'             => __( 'View Resource', 'nmda-understrap' ),
        'search_items'          => __( 'Search Resources', 'nmda-understrap' ),
    );

    $args = array(
        'label'                 => __( 'Resource', 'nmda-understrap' ),
        'description'           => __( 'Member Resources and Downloads', 'nmda-understrap' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'thumbnail' ),
        'taxonomies'            => array( 'resource_category' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 6,
        'menu_icon'             => 'dashicons-download',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => 'resources',
        'exclude_from_search'   => true,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'show_in_rest'          => true,
    );

    register_post_type( 'nmda_resource', $args );
}
add_action( 'init', 'nmda_register_resource_post_type', 0 );

/**
 * Register Resource Category Taxonomy
 */
function nmda_register_resource_category_taxonomy() {
    $labels = array(
        'name'              => _x( 'Resource Categories', 'taxonomy general name', 'nmda-understrap' ),
        'singular_name'     => _x( 'Resource Category', 'taxonomy singular name', 'nmda-understrap' ),
        'search_items'      => __( 'Search Resource Categories', 'nmda-understrap' ),
        'all_items'         => __( 'All Resource Categories', 'nmda-understrap' ),
        'edit_item'         => __( 'Edit Resource Category', 'nmda-understrap' ),
        'update_item'       => __( 'Update Resource Category', 'nmda-understrap' ),
        'add_new_item'      => __( 'Add New Resource Category', 'nmda-understrap' ),
        'new_item_name'     => __( 'New Resource Category Name', 'nmda-understrap' ),
        'menu_name'         => __( 'Resource Categories', 'nmda-understrap' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'show_in_rest'      => true,
        'rewrite'           => array( 'slug' => 'resource-category' ),
    );

    register_taxonomy( 'resource_category', array( 'nmda_resource' ), $args );
}
add_action( 'init', 'nmda_register_resource_category_taxonomy', 0 );
