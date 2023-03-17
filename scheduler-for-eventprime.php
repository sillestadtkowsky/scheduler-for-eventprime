<?php
/**
* Plugin Name: Scheduler For EventPrime
* Plugin URI: https://plugin.wp.osowsky-webdesign.de/scheduler-for-eventprime
* Description: This plugin offers the possibility to provide events from the EventPrime plugin with a publication period (date and time) so that events can be published automatically.
* Version: 1.0.0
* Requires at least: 5.8.0
* Requires PHP:      8.0
* Author: Silvio Osowsky
* License: GPLv3 or later
* License URI: https://www.gnu.org/licenses/gpl-3.0.html
* Author URI: https://osowsky-webdesign.de/
*/

function so_category_product_count_shortcode( $atts ) {
    // shortcode attributes
    $atts = shortcode_atts( array(
        'title' => '',
        'show_category_name' => true,
    ), $atts, 'category_product_count' );
  
    // get category id
    $category_id = get_term_by( 'slug', wp_strip_all_tags($atts['title']), 'product_cat' )->term_id;
  
    // get products in category with stock greater than zero
    $args = array(
        'post_type' => 'product',
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $category_id,
            ),
        ),
        'meta_query' => array(
            array(
                'key' => '_stock',
                'value' => '0',
                'compare' => '>'
            )
        )
    );
    $products = new WP_Query( $args );
  
    // build output string
    $output = '<div style="display: flex; align-items: center; justify-content: center;">';
    if ( wp_strip_all_tags($atts['show_category_name']) == 'true' ) {
        $output .= get_term_by('slug', $atts['title'], 'product_cat')->name . ' ';
    }
    $output .= '(' . $products->post_count . ')';
    $output .= '</div>';
  
    // return output
    return $output;
}
add_shortcode( 'so_cp_count', 'so_category_product_count_shortcode' );