<?php
/**
 * Plugin Name: Portfolio Tiles Grid for Jetpack Portfolio
 * Description: Provides a responsive tile grid shortcode for Jetpack Portfolio items.
 * Version: 0.01
 * Author: Netservice
 * Author URI: https://netservice.jp/
 * License: GPLv2 or later
 * Text Domain: portfolio-tiles-grid
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PTG_PLUGIN_VERSION', '0.01' );

define( 'PTG_TRANSIENT_TTL', MINUTE_IN_SECONDS );

/**
 * Load plugin text domain.
 */
function ptg_load_textdomain() {
    load_plugin_textdomain( 'portfolio-tiles-grid', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'ptg_load_textdomain' );

/**
 * Register shortcode.
 */
function ptg_register_shortcode() {
    add_shortcode( 'portfolio_tiles', 'ptg_render_portfolio_tiles' );
}
add_action( 'init', 'ptg_register_shortcode' );

/**
 * Enqueue frontend assets.
 */
function ptg_enqueue_assets() {
    if ( wp_style_is( 'ptg-portfolio-tiles-grid', 'enqueued' ) ) {
        return;
    }

    wp_register_style( 'ptg-portfolio-tiles-grid', false, array(), PTG_PLUGIN_VERSION );
    wp_enqueue_style( 'ptg-portfolio-tiles-grid' );

    $css = '.ptg-grid{display:grid;grid-template-columns:repeat(var(--ptg-cols),1fr);gap:var(--ptg-gap);}' .
        '.ptg-grid{--ptg-cols:var(--ptg-cols-sp);--ptg-gap:0;--ptg-aspect:1/1;}' .
        '@media (min-width:600px){.ptg-grid{--ptg-cols:var(--ptg-cols-tb,var(--ptg-cols-sp));}}' .
        '@media (min-width:1024px){.ptg-grid{--ptg-cols:var(--ptg-cols-pc,var(--ptg-cols-tb,var(--ptg-cols-sp)));}}' .
        '.ptg-item{display:block;position:relative;}' .
        '.ptg-item img{width:100%;height:100%;object-fit:cover;aspect-ratio:var(--ptg-aspect);display:block;}' .
        '.ptg-item .screen-reader-text{position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;}';

    wp_add_inline_style( 'ptg-portfolio-tiles-grid', $css );
}

/**
 * Render the portfolio tiles shortcode.
 *
 * @param array  Shortcode attributes.
 * @return string
 */
function ptg_render_portfolio_tiles( $atts ) {
    $defaults = array(
        'cols_pc' => 3,
        'cols_tb' => 2,
        'cols_sp' => 1,
        'rows'    => 3,
        'gap'     => '0',
        'aspect'  => '1:1',
        'size'    => 'medium_large',
        'type_in' => '',
        'type_ex' => '',
        'tag_in'  => '',
        'tag_ex'  => '',
    );

    $atts = shortcode_atts( $defaults, $atts, 'portfolio_tiles' );

    $cols_pc = ptg_sanitize_column_value( $atts['cols_pc'] );
    $cols_tb = ptg_sanitize_column_value( $atts['cols_tb'] );
    $cols_sp = ptg_sanitize_column_value( $atts['cols_sp'] );
    $rows = ptg_sanitize_row_value( $atts['rows'] );

    $total_items = max( 1, $cols_pc * $rows );

    $gap = ptg_sanitize_gap( $atts['gap'] );

    $aspect = ptg_sanitize_aspect( $atts['aspect'] );

    $size = ptg_sanitize_image_size( $atts['size'] );

    $type_in = ptg_sanitize_slug_list( $atts['type_in'] );
    $type_ex = ptg_sanitize_slug_list( $atts['type_ex'] );
    $tag_in = ptg_sanitize_slug_list( $atts['tag_in'] );
    $tag_ex = ptg_sanitize_slug_list( $atts['tag_ex'] );

    $cache_key = ptg_build_cache_key( compact( 'cols_pc', 'cols_tb', 'cols_sp', 'rows', 'gap', 'aspect', 'size', 'type_in', 'type_ex', 'tag_in', 'tag_ex' ) );
    $cached_html = get_transient( $cache_key );

    ptg_enqueue_assets();

    if ( false !== $cached_html ) {
        return $cached_html;
    }

    $query_args = array(
        'post_type'      => 'jetpack-portfolio',
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'posts_per_page' => $total_items,
        'meta_query'     => array(
            array(
                'key'     => '_thumbnail_id',
                'compare' => 'EXISTS',
            ),
        ),
    );

    $tax_query = ptg_build_tax_query( $type_in, $type_ex, $tag_in, $tag_ex );
    if ( ! empty( $tax_query ) ) {
        $query_args['tax_query'] = $tax_query;
    }

    $query = new WP_Query( $query_args );

    if ( ! $query->have_posts() ) {
        wp_reset_postdata();
        $html = esc_html__( 'No items', 'portfolio-tiles-grid' );
        set_transient( $cache_key, $html, PTG_TRANSIENT_TTL );
        return $html;
    }

    $grid_style = sprintf(
        'style="--ptg-cols-pc:%1$d;--ptg-cols-tb:%2$d;--ptg-cols-sp:%3$d;--ptg-gap:%4$s;--ptg-aspect:%5$s;"',
        (int) $cols_pc,
        (int) $cols_tb,
        (int) $cols_sp,
        esc_attr( $gap ),
        esc_attr( $aspect )
    );

    ob_start();
    printf( '<div class="ptg-grid" role="list" %s>', $grid_style );

    while ( $query->have_posts() ) {
        $query->the_post();
        $post_id = get_the_ID();
        $permalink = get_permalink( $post_id );
        $title = get_the_title( $post_id );
        $thumbnail_id = get_post_thumbnail_id( $post_id );

        if ( ! $thumbnail_id ) {
            continue;
        }

        $image_data = wp_get_attachment_image_src( $thumbnail_id, $size );
        if ( ! $image_data ) {
            continue;
        }

        $image_url = $image_data[0];

        printf(
            '<a role="listitem" class="ptg-item" href="%1$s"><img src="%2$s" alt="%3$s" loading="lazy" decoding="async" /><span class="screen-reader-text">%3$s</span></a>',
            esc_url( $permalink ),
            esc_url( $image_url ),
            esc_attr( $title )
        );
    }

    echo '</div>';

    wp_reset_postdata();

    $html = ob_get_clean();

    set_transient( $cache_key, $html, PTG_TRANSIENT_TTL );

    return $html;
}

/**
 * Sanitize column counts.
 *
 * @param mixed  Attribute value.
 * @return int
 */
function ptg_sanitize_column_value( $value ) {
    $value = absint( $value );

    if ( $value < 1 ) {
        $value = 1;
    }

    if ( $value > 4 ) {
        $value = 4;
    }

    return $value;
}

/**
 * Sanitize row value.
 *
 * @param mixed  Attribute value.
 * @return int
 */
function ptg_sanitize_row_value( $value ) {
    $value = absint( $value );

    if ( $value < 1 ) {
        $value = 1;
    }

    if ( $value > 12 ) {
        $value = 12;
    }

    return $value;
}

/**
 * Sanitize gap value.
 *
 * @param string  Attribute value.
 * @return string
 */
function ptg_sanitize_gap( $value ) {
    $value = trim( (string) $value );

    if ( '' === $value ) {
        return '0';
    }

    if ( preg_match( '/^\d+(?:\.\d+)?(?:px|rem|em|%)?$/', $value ) ) {
        return $value;
    }

    return '0';
}

/**
 * Sanitize aspect ratio value.
 *
 * @param string  Attribute value.
 * @return string
 */
function ptg_sanitize_aspect( $value ) {
    $allowed = array(
        '1:1'  => '1 / 1',
        '16:9' => '16 / 9',
        '4:3'  => '4 / 3',
        '3:4'  => '3 / 4',
    );

    $value = strtoupper( (string) $value );

    if ( isset( $allowed[ $value ] ) ) {
        return $allowed[ $value ];
    }

    return $allowed['1:1'];
}

/**
 * Sanitize image size value.
 *
 * @param string  Attribute value.
 * @return string
 */
function ptg_sanitize_image_size( $value ) {
    $value = sanitize_key( $value );

    if ( empty( $value ) ) {
        return 'medium_large';
    }

    $sizes = get_intermediate_image_sizes();
    $sizes[] = 'thumbnail';
    $sizes[] = 'medium';
    $sizes[] = 'large';
    $sizes[] = 'full';

    $sizes = array_unique( $sizes );

    if ( in_array( $value, $sizes, true ) ) {
        return $value;
    }

    return 'medium_large';
}

/**
 * Sanitize comma separated slug list.
 *
 * @param string  Attribute value.
 * @return array
 */
function ptg_sanitize_slug_list( $value ) {
    $value = trim( (string) $value );
    if ( '' === $value ) {
        return array();
    }

    $parts = array_filter( array_map( 'trim', explode( ',', $value ) ) );
    if ( empty( $parts ) ) {
        return array();
    }

    $result = array();
    foreach ( $parts as $part ) {
        $slug = sanitize_title( $part );
        if ( '' !== $slug ) {
            $result[] = $slug;
        }
    }

    return array_unique( $result );
}

/**
 * Build the tax query array.
 *
 * @param array  Included portfolio types.
 * @param array  Excluded portfolio types.
 * @param array   Included portfolio tags.
 * @param array   Excluded portfolio tags.
 * @return array
 */
function ptg_build_tax_query( $type_in, $type_ex, $tag_in, $tag_ex ) {
    $tax_query = array();

    if ( ! empty( $type_in ) ) {
        $tax_query[] = array(
            'taxonomy' => 'jetpack-portfolio-type',
            'field'    => 'slug',
            'terms'    => $type_in,
            'operator' => 'IN',
        );
    }

    if ( ! empty( $type_ex ) ) {
        $tax_query[] = array(
            'taxonomy' => 'jetpack-portfolio-type',
            'field'    => 'slug',
            'terms'    => $type_ex,
            'operator' => 'NOT IN',
        );
    }

    if ( ! empty( $tag_in ) ) {
        $tax_query[] = array(
            'taxonomy' => 'jetpack-portfolio-tag',
            'field'    => 'slug',
            'terms'    => $tag_in,
            'operator' => 'IN',
        );
    }

    if ( ! empty( $tag_ex ) ) {
        $tax_query[] = array(
            'taxonomy' => 'jetpack-portfolio-tag',
            'field'    => 'slug',
            'terms'    => $tag_ex,
            'operator' => 'NOT IN',
        );
    }

    if ( count( $tax_query ) > 1 ) {
        $tax_query['relation'] = 'AND';
    }

    return $tax_query;
}

/**
 * Build a unique cache key.
 *
 * @param array  Data to hash.
 * @return string
 */
function ptg_build_cache_key( $data ) {
    $data['locale'] = get_locale();
    return 'ptg_tiles_' . md5( wp_json_encode( $data ) );
}
