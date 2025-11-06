<?php
/**
 * NMDA Simplified Product Taxonomy
 * Creates hierarchical product taxonomy to replace 202 boolean fields
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Insert default product taxonomy terms
 * This runs once on theme activation
 */
function nmda_insert_product_taxonomy_terms() {
    // Check if terms already exist
    $existing = get_terms( array(
        'taxonomy'   => 'product_type',
        'hide_empty' => false,
        'number'     => 1,
    ) );

    if ( ! empty( $existing ) ) {
        return; // Already initialized
    }

    /**
     * PRODUCE CATEGORY
     */
    $produce_id = wp_insert_term( 'Produce', 'product_type', array(
        'description' => 'Fresh fruits and vegetables',
        'slug'        => 'produce',
    ) );

    if ( ! is_wp_error( $produce_id ) ) {
        $produce_items = array(
            'Apples', 'Cabbages', 'Chiles & Peppers', 'Lettuces', 'Onions',
            'Potatoes', 'Pumpkins', 'Watermelons', 'Peanuts', 'Legumes & Beans', 'Other Produce',
        );

        foreach ( $produce_items as $item ) {
            wp_insert_term( $item, 'product_type', array(
                'parent' => $produce_id['term_id'],
                'slug'   => sanitize_title( $item ),
            ) );
        }
    }

    /**
     * NUTS CATEGORY
     */
    $nuts_id = wp_insert_term( 'Nuts', 'product_type', array(
        'description' => 'Tree nuts and peanuts',
        'slug'        => 'nuts',
    ) );

    if ( ! is_wp_error( $nuts_id ) ) {
        $nut_items = array( 'Pecans', 'Pistachios', 'Piñon (Pine Nuts)', 'Other Nuts' );

        foreach ( $nut_items as $item ) {
            wp_insert_term( $item, 'product_type', array(
                'parent' => $nuts_id['term_id'],
                'slug'   => sanitize_title( $item ),
            ) );
        }
    }

    /**
     * LIVESTOCK & POULTRY CATEGORY
     */
    $livestock_id = wp_insert_term( 'Livestock & Poultry', 'product_type', array(
        'description' => 'Live animals',
        'slug'        => 'livestock-poultry',
    ) );

    if ( ! is_wp_error( $livestock_id ) ) {
        $livestock_items = array(
            'Cattle', 'Pigs', 'Sheep & Lambs', 'Goats', 'Chickens',
            'Turkeys', 'Ducks', 'Geese', 'Quail', 'Other Poultry', 'Game Animals',
        );

        foreach ( $livestock_items as $item ) {
            wp_insert_term( $item, 'product_type', array(
                'parent' => $livestock_id['term_id'],
                'slug'   => sanitize_title( $item ),
            ) );
        }
    }

    /**
     * MEAT PRODUCTS CATEGORY
     */
    $meat_id = wp_insert_term( 'Meat Products', 'product_type', array(
        'description' => 'Processed meat products',
        'slug'        => 'meat-products',
    ) );

    if ( ! is_wp_error( $meat_id ) ) {
        $meat_items = array(
            'Beef Products', 'Pork Products', 'Lamb Products', 'Goat Products',
            'Poultry Products', 'Game Meat', 'Other Meat Products',
        );

        foreach ( $meat_items as $item ) {
            wp_insert_term( $item, 'product_type', array(
                'parent' => $meat_id['term_id'],
                'slug'   => sanitize_title( $item ),
            ) );
        }
    }

    /**
     * DAIRY PRODUCTS CATEGORY
     */
    $dairy_id = wp_insert_term( 'Dairy Products', 'product_type', array(
        'description' => 'Milk and dairy products',
        'slug'        => 'dairy-products',
    ) );

    if ( ! is_wp_error( $dairy_id ) ) {
        $dairy_items = array(
            'Raw Milk', 'Pasteurized Milk', 'Cheese', 'Yogurt',
            'Ice Cream', 'Butter', 'Other Dairy',
        );

        foreach ( $dairy_items as $item ) {
            wp_insert_term( $item, 'product_type', array(
                'parent' => $dairy_id['term_id'],
                'slug'   => sanitize_title( $item ),
            ) );
        }
    }

    /**
     * BEVERAGES CATEGORY
     */
    $beverages_id = wp_insert_term( 'Beverages', 'product_type', array(
        'description' => 'Alcoholic and non-alcoholic beverages',
        'slug'        => 'beverages',
    ) );

    if ( ! is_wp_error( $beverages_id ) ) {
        $beverage_items = array(
            'Beer', 'Wine', 'Spirits', 'Coffee', 'Tea',
            'Juice & Cider', 'Soft Drinks', 'Energy Drinks', 'Other Beverages',
        );

        foreach ( $beverage_items as $item ) {
            wp_insert_term( $item, 'product_type', array(
                'parent' => $beverages_id['term_id'],
                'slug'   => sanitize_title( $item ),
            ) );
        }
    }

    /**
     * PREPARED FOODS CATEGORY
     */
    $prepared_id = wp_insert_term( 'Prepared Foods', 'product_type', array(
        'description' => 'Processed and prepared food products',
        'slug'        => 'prepared-foods',
    ) );

    if ( ! is_wp_error( $prepared_id ) ) {
        $prepared_items = array(
            'Sauces & Salsas', 'Seasonings & Spices', 'Baked Goods', 'Confections & Sweets',
            'Frozen Foods', 'Dried Foods', 'Canned Foods', 'Snack Foods', 'Other Prepared Foods',
        );

        foreach ( $prepared_items as $item ) {
            wp_insert_term( $item, 'product_type', array(
                'parent' => $prepared_id['term_id'],
                'slug'   => sanitize_title( $item ),
            ) );
        }
    }

    /**
     * OTHER PRODUCTS CATEGORY
     */
    $other_id = wp_insert_term( 'Other Products', 'product_type', array(
        'description' => 'Eggs, honey, fiber, and other agricultural products',
        'slug'        => 'other-products',
    ) );

    if ( ! is_wp_error( $other_id ) ) {
        $other_items = array(
            'Eggs', 'Honey & Bee Products', 'Fiber & Wool', 'Pet Food',
            'Hemp Products', 'Horticultural Products', 'Other',
        );

        foreach ( $other_items as $item ) {
            wp_insert_term( $item, 'product_type', array(
                'parent' => $other_id['term_id'],
                'slug'   => sanitize_title( $item ),
            ) );
        }
    }
}
add_action( 'init', 'nmda_insert_product_taxonomy_terms', 20 );

/**
 * Add ACF fields to product taxonomy terms for attributes
 */
function nmda_add_product_taxonomy_acf_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( array(
        'key'      => 'group_product_attributes',
        'title'    => 'Product Attributes',
        'fields'   => array(
            array(
                'key'          => 'field_organic',
                'label'        => 'Organic',
                'name'         => 'organic',
                'type'         => 'true_false',
                'instructions' => 'Check if this product is certified organic',
                'message'      => 'Organic',
            ),
            array(
                'key'          => 'field_free_range',
                'label'        => 'Free Range',
                'name'         => 'free_range',
                'type'         => 'true_false',
                'instructions' => 'Check if applicable (poultry)',
                'message'      => 'Free Range',
            ),
            array(
                'key'          => 'field_grass_fed',
                'label'        => 'Grass Fed',
                'name'         => 'grass_fed',
                'type'         => 'true_false',
                'instructions' => 'Check if applicable (beef, lamb)',
                'message'      => 'Grass Fed',
            ),
            array(
                'key'          => 'field_preparation_type',
                'label'        => 'Preparation Types',
                'name'         => 'preparation_type',
                'type'         => 'checkbox',
                'instructions' => 'Select all that apply (for meat products)',
                'choices'      => array(
                    'fresh'  => 'Fresh',
                    'frozen' => 'Frozen',
                    'smoked' => 'Smoked',
                    'cured'  => 'Cured',
                    'dried'  => 'Dried',
                ),
                'layout'       => 'horizontal',
            ),
            array(
                'key'          => 'field_product_use',
                'label'        => 'Product Use',
                'name'         => 'product_use',
                'type'         => 'checkbox',
                'instructions' => 'Select all that apply (for animals)',
                'choices'      => array(
                    'meat'  => 'Meat',
                    'dairy' => 'Dairy',
                    'fiber' => 'Fiber/Wool',
                    'eggs'  => 'Eggs/Laying',
                ),
                'layout'       => 'horizontal',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'    => 'taxonomy',
                    'operator' => '==',
                    'value'    => 'product_type',
                ),
            ),
        ),
    ) );
}
add_action( 'acf/init', 'nmda_add_product_taxonomy_acf_fields' );

/**
 * Get products for a business with attributes
 *
 * @param int $business_id Business post ID.
 * @return array Array of product objects with attributes.
 */
function nmda_get_business_products( $business_id ) {
    $products = array();

    $terms = wp_get_post_terms( $business_id, 'product_type' );

    foreach ( $terms as $term ) {
        $product = array(
            'term_id'     => $term->term_id,
            'name'        => $term->name,
            'slug'        => $term->slug,
            'parent'      => $term->parent,
            'description' => $term->description,
            'attributes'  => array(),
        );

        // Get ACF fields for this term
        if ( function_exists( 'get_field' ) ) {
            $product['attributes'] = array(
                'organic'           => get_field( 'organic', $term ),
                'free_range'        => get_field( 'free_range', $term ),
                'grass_fed'         => get_field( 'grass_fed', $term ),
                'preparation_type'  => get_field( 'preparation_type', $term ),
                'product_use'       => get_field( 'product_use', $term ),
            );
        }

        $products[] = $product;
    }

    return $products;
}

/**
 * Get product categories for display
 *
 * @return array Hierarchical array of product categories.
 */
function nmda_get_product_categories() {
    return get_terms( array(
        'taxonomy'   => 'product_type',
        'parent'     => 0,
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );
}

/**
 * Get products by category
 *
 * @param int $category_id Parent term ID.
 * @return array Array of child terms.
 */
function nmda_get_products_by_category( $category_id ) {
    return get_terms( array(
        'taxonomy'   => 'product_type',
        'parent'     => $category_id,
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );
}
