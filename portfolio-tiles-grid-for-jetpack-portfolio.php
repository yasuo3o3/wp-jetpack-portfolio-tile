<?php
/**
 * Plugin Name: Portfolio Tiles Grid for Jetpack Portfolio
 * Description: Provides a responsive tile grid shortcode for Jetpack Portfolio items.
 * Version: 0.02
 * Author: Netservice
 * Author URI: https://netservice.jp/
 * License: GPLv2 or later
 * Text Domain: portfolio-tiles-grid
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PTG_PLUGIN_VERSION', '0.02' );

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
    static $style_added = false;
    static $script_registered = false;

    wp_register_style( 'ptg-portfolio-tiles-grid', false, array(), PTG_PLUGIN_VERSION );
    wp_enqueue_style( 'ptg-portfolio-tiles-grid' );

    if ( ! $style_added ) {
        $css = '.ptg-grid{display:grid;grid-template-columns:repeat(var(--ptg-cols),1fr);gap:var(--ptg-gap);}' .
            '.ptg-grid{--ptg-cols:var(--ptg-cols-sp);--ptg-gap:0;--ptg-aspect:1/1;--ptg-stagger:80ms;--ptg-duration:400ms;--ptg-ease:cubic-bezier(.2,.6,.2,1);}' .
            '@media (min-width:600px){.ptg-grid{--ptg-cols:var(--ptg-cols-tb,var(--ptg-cols-sp));}}' .
            '@media (min-width:1024px){.ptg-grid{--ptg-cols:var(--ptg-cols-pc,var(--ptg-cols-tb,var(--ptg-cols-sp)));}}' .
            '.ptg-item{display:block;position:relative;background:#f7f7f7;outline:1px solid #999;transition:opacity var(--ptg-duration) var(--ptg-ease),transform var(--ptg-duration) var(--ptg-ease);overflow:hidden;}' .
            '.ptg-item img{width:100%;height:100%;object-fit:cover;aspect-ratio:var(--ptg-aspect);display:block;transition:opacity var(--ptg-duration) var(--ptg-ease);opacity:1;}' .
            '.ptg-grid[data-ptg-reveal="on-scroll"] .ptg-item{opacity:0;transform:translateY(-6px);}' .
            '.ptg-grid[data-ptg-reveal="on-scroll"] .ptg-item img{opacity:0;}' .
            '.ptg-grid[data-ptg-reveal="on-scroll"] .ptg-item.is-visible{opacity:1;transform:none;}' .
            '.ptg-grid[data-ptg-reveal="on-scroll"] .ptg-item.is-visible img{opacity:1;}' .
            '.ptg-grid[data-ptg-reveal="on-scroll"] .ptg-item::before,.ptg-grid[data-ptg-reveal="on-scroll"] .ptg-item::after{content:"";position:absolute;inset:0;pointer-events:none;background-repeat:no-repeat;background-position:center;background-size:100% 100%;opacity:1;transition:opacity var(--ptg-duration) var(--ptg-ease);z-index:2;}' .
            '.ptg-grid[data-ptg-reveal="on-scroll"] .ptg-item::before{background-image:linear-gradient(45deg,transparent calc(50% - 0.5px),#999 calc(50% - 0.5px),#999 calc(50% + 0.5px),transparent calc(50% + 0.5px));}' .
            '.ptg-grid[data-ptg-reveal="on-scroll"] .ptg-item::after{background-image:linear-gradient(135deg,transparent calc(50% - 0.5px),#999 calc(50% - 0.5px),#999 calc(50% + 0.5px),transparent calc(50% + 0.5px));}' .
            '.ptg-grid[data-ptg-reveal="on-scroll"] .ptg-item.has-img::before,.ptg-grid[data-ptg-reveal="on-scroll"] .ptg-item.has-img::after{opacity:0;}' .
            '.ptg-grid[data-ptg-reveal="on-scroll"] .ptg-item.has-img{outline-color:rgba(153,153,153,0);}' .
            '.ptg-item .screen-reader-text{position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;}' .
            '.ptg-title{position:absolute;bottom:0;left:0;right:0;padding:12px;background:rgba(0,0,0,0.75);color:#fff;font-size:14px;font-weight:500;line-height:1.2;word-wrap:break-word;overflow:hidden;pointer-events:none;opacity:1;transition:opacity var(--ptg-duration) var(--ptg-ease);}' .
            '.ptg-grid[data-ptg-reveal="on-scroll"] .ptg-item .ptg-title{opacity:0;}' .
            '.ptg-grid[data-ptg-reveal="on-scroll"] .ptg-item.is-visible .ptg-title{opacity:1;}' .
            '.ptg-item[data-ptg-show-title=\"no\"] .ptg-title{display:none;}' .
            '@media (max-width:599px){.ptg-title{padding:8px;font-size:12px;}}' .
            '@media (min-width:600px) and (max-width:1023px){.ptg-title{padding:10px;font-size:13px;}}' .
            '@media (prefers-reduced-motion: reduce){.ptg-grid[data-ptg-reveal="on-scroll"] .ptg-item{transition:none;opacity:1;transform:none;}.ptg-grid[data-ptg-reveal="on-scroll"] .ptg-item img{transition:none;opacity:1;}.ptg-title{transition:none;}}';

        wp_add_inline_style( 'ptg-portfolio-tiles-grid', $css );
        $style_added = true;
    }

    if ( ! $script_registered ) {
        wp_register_script(
            'ptg-portfolio-tiles-grid',
            plugins_url( 'assets/js/portfolio-tiles-grid.js', __FILE__ ),
            array(),
            PTG_PLUGIN_VERSION,
            true
        );
        $script_registered = true;
    }

    wp_enqueue_script( 'ptg-portfolio-tiles-grid' );
}

/**
 * Render the portfolio tiles shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function ptg_render_portfolio_tiles( $atts ) {
    $defaults = array(
        'cols_pc'     => 3,
        'cols_tb'     => 2,
        'cols_sp'     => 1,
        'rows'        => 3,
        'gap'         => '0',
        'aspect'      => '1:1',
        'size'        => 'medium_large',
        'type_in'     => '',
        'type_ex'     => '',
        'tag_in'      => '',
        'tag_ex'      => '',
        'reveal'      => 'on-scroll',
        'stagger_ms'  => 80,
        'duration_ms' => 400,
        'easing'      => 'cubic-bezier(.2,.6,.2,1)',
        'root_margin' => '200px 0px 0px 0px',
        'threshold'   => 0.15,
        'prefetch'    => 'near',
        'show_title'  => 'yes',
        'orderby'     => 'date',
        'order'       => 'DESC',
    );

    $atts = shortcode_atts( $defaults, $atts, 'portfolio_tiles' );

    $cols_pc = ptg_sanitize_column_value( $atts['cols_pc'] );
    $cols_tb = ptg_sanitize_column_value( $atts['cols_tb'] );
    $cols_sp = ptg_sanitize_column_value( $atts['cols_sp'] );
    $rows    = ptg_sanitize_row_value( $atts['rows'] );

    $total_items = max( 1, $cols_pc * $rows );

    $gap    = ptg_sanitize_gap( $atts['gap'] );
    $aspect = ptg_sanitize_aspect( $atts['aspect'] );
    $size   = ptg_sanitize_image_size( $atts['size'] );

    $type_in = ptg_sanitize_slug_list( $atts['type_in'] );
    $type_ex = ptg_sanitize_slug_list( $atts['type_ex'] );
    $tag_in  = ptg_sanitize_slug_list( $atts['tag_in'] );
    $tag_ex  = ptg_sanitize_slug_list( $atts['tag_ex'] );

    $reveal      = ptg_sanitize_reveal_mode( $atts['reveal'] );
    $stagger_ms  = ptg_sanitize_timing_value( $atts['stagger_ms'], 80, 2000 );
    $duration_ms = ptg_sanitize_timing_value( $atts['duration_ms'], 400, 10000 );
    $easing      = ptg_sanitize_easing( $atts['easing'] );
    $root_margin = ptg_sanitize_root_margin( $atts['root_margin'] );
    $threshold   = ptg_sanitize_threshold( $atts['threshold'] );
    $prefetch    = ptg_sanitize_prefetch_mode( $atts['prefetch'] );
    $show_title  = ptg_sanitize_show_title( $atts['show_title'] );
    $orderby     = ptg_sanitize_orderby( $atts['orderby'] );
    $order       = ptg_sanitize_order( $atts['order'] );

    $cache_key = ptg_build_cache_key(
        compact(
            'cols_pc',
            'cols_tb',
            'cols_sp',
            'rows',
            'gap',
            'aspect',
            'size',
            'type_in',
            'type_ex',
            'tag_in',
            'tag_ex',
            'reveal',
            'stagger_ms',
            'duration_ms',
            'easing',
            'root_margin',
            'threshold',
            'prefetch',
            'show_title',
            'orderby',
            'order'
        )
    );

    ptg_enqueue_assets();

    $cached_html = get_transient( $cache_key );
    if ( false !== $cached_html ) {
        return $cached_html;
    }

    $query_args = array(
        'post_type'      => 'jetpack-portfolio',
        'orderby'        => $orderby,
        'order'          => $order,
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
        'style="--ptg-cols-pc:%1$d;--ptg-cols-tb:%2$d;--ptg-cols-sp:%3$d;--ptg-gap:%4$s;--ptg-aspect:%5$s;--ptg-stagger:%6$dms;--ptg-duration:%7$dms;--ptg-ease:%8$s;"',
        (int) $cols_pc,
        (int) $cols_tb,
        (int) $cols_sp,
        esc_attr( $gap ),
        esc_attr( $aspect ),
        (int) $stagger_ms,
        (int) $duration_ms,
        esc_attr( $easing )
    );

    $grid_data = sprintf(
        'data-ptg-reveal="%1$s" data-ptg-stagger="%2$d" data-ptg-duration="%3$d" data-ptg-easing="%4$s" data-ptg-rootmargin="%5$s" data-ptg-threshold="%6$s" data-ptg-prefetch="%7$s"',
        esc_attr( $reveal ),
        (int) $stagger_ms,
        (int) $duration_ms,
        esc_attr( $easing ),
        esc_attr( $root_margin ),
        esc_attr( $threshold ),
        esc_attr( $prefetch )
    );

    ob_start();
    printf( '<div class="ptg-grid" role="list" %1$s %2$s>', $grid_data, $grid_style );

    $index = 0;

    while ( $query->have_posts() ) {
        $query->the_post();

        $post_id      = get_the_ID();
        $permalink    = get_permalink( $post_id );
        $title        = get_the_title( $post_id );
        $thumbnail_id = get_post_thumbnail_id( $post_id );

        if ( ! $thumbnail_id ) {
            continue;
        }

        $image_data = wp_get_attachment_image_src( $thumbnail_id, $size );
        if ( ! $image_data ) {
            continue;
        }

        $image_url = $image_data[0];

        $classes = array( 'ptg-item' );
        if ( 'none' === $reveal ) {
            $classes[] = 'is-visible';
        }

        printf(
            '<a role="listitem" class="%1$s" href="%2$s" data-idx="%3$d" data-ptg-show-title="%6$s"><img src="%4$s" alt="%5$s" loading="lazy" decoding="async" /><span class="ptg-title">%5$s</span><span class="screen-reader-text">%5$s</span></a>',
            esc_attr( implode( ' ', $classes ) ),
            esc_url( $permalink ),
            (int) $index,
            esc_url( $image_url ),
            esc_attr( $title ),
            esc_attr( $show_title )
        );

        $index++;
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
 * @param mixed $value Attribute value.
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
 * @param mixed $value Attribute value.
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
 * @param string $value Attribute value.
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
 * @param string $value Attribute value.
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
 * @param string $value Attribute value.
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
 * @param string $value Attribute value.
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
 * @param array $type_in Included portfolio types.
 * @param array $type_ex Excluded portfolio types.
 * @param array $tag_in  Included portfolio tags.
 * @param array $tag_ex  Excluded portfolio tags.
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
 * @param array $data Data to hash.
 * @return string
 */
function ptg_build_cache_key( $data ) {
    $data['locale'] = get_locale();
    return 'ptg_tiles_' . md5( wp_json_encode( $data ) );
}

/**
 * Sanitize reveal mode value.
 *
 * @param string $value Attribute value.
 * @return string
 */
function ptg_sanitize_reveal_mode( $value ) {
    $value   = sanitize_key( $value );
    $allowed = array( 'on-scroll', 'none' );

    if ( in_array( $value, $allowed, true ) ) {
        return $value;
    }

    return 'on-scroll';
}

/**
 * Sanitize timing values.
 *
 * @param mixed $value   Attribute value.
 * @param int   $default Default fallback.
 * @param int   $max     Maximum allowed.
 * @return int
 */
function ptg_sanitize_timing_value( $value, $default, $max ) {
    if ( '' === $value || null === $value ) {
        return $default;
    }

    $value = absint( $value );

    if ( $value > $max ) {
        $value = $max;
    }

    return $value;
}

/**
 * Sanitize easing string.
 *
 * @param string $value Attribute value.
 * @return string
 */
function ptg_sanitize_easing( $value ) {
    $value = trim( (string) $value );

    if ( '' === $value ) {
        return 'cubic-bezier(.2,.6,.2,1)';
    }

    $keywords = array( 'linear', 'ease', 'ease-in', 'ease-out', 'ease-in-out' );
    if ( in_array( $value, $keywords, true ) ) {
        return $value;
    }

    if ( preg_match( '/^cubic-bezier\(\s*-?\d*\.?\d+\s*,\s*-?\d*\.?\d+\s*,\s*-?\d*\.?\d+\s*,\s*-?\d*\.?\d+\s*\)$/', $value ) ) {
        return $value;
    }

    if ( preg_match( '/^steps\(\s*\d+\s*(,\s*(start|end)\s*)?\)$/', $value ) ) {
        return $value;
    }

    return 'cubic-bezier(.2,.6,.2,1)';
}

/**
 * Sanitize root margin value.
 *
 * @param string $value Attribute value.
 * @return string
 */
function ptg_sanitize_root_margin( $value ) {
    $value = trim( (string) $value );

    if ( '' === $value ) {
        return '200px 0px 0px 0px';
    }

    $parts     = preg_split( '/\s+/', $value );
    $sanitized = array();

    foreach ( $parts as $part ) {
        $part = strtolower( trim( $part ) );
        if ( '' === $part ) {
            continue;
        }

        $part = str_replace( ',', '.', $part );

        if ( '0' === $part ) {
            $sanitized[] = '0px';
            continue;
        }

        if ( preg_match( '/^-?\d*\.?\d+(px|%)$/', $part ) ) {
            $sanitized[] = $part;
        }
    }

    if ( empty( $sanitized ) ) {
        return '200px 0px 0px 0px';
    }

    if ( count( $sanitized ) === 1 ) {
        return $sanitized[0];
    }

    return implode( ' ', array_slice( $sanitized, 0, 4 ) );
}

/**
 * Sanitize threshold value.
 *
 * @param mixed $value Attribute value.
 * @return string
 */
function ptg_sanitize_threshold( $value ) {
    $value = str_replace( ',', '.', trim( (string) $value ) );

    if ( '' === $value ) {
        $float = 0.15;
    } else {
        $float = (float) $value;
    }

    if ( $float < 0 ) {
        $float = 0;
    }

    if ( $float > 1 ) {
        $float = 1;
    }

    return rtrim( rtrim( sprintf( '%.4f', $float ), '0' ), '.' );
}

/**
 * Sanitize prefetch mode.
 *
 * @param string $value Attribute value.
 * @return string
 */
function ptg_sanitize_prefetch_mode( $value ) {
    $value   = sanitize_key( $value );
    $allowed = array( 'near', 'all', 'none' );

    if ( in_array( $value, $allowed, true ) ) {
        return $value;
    }

    return 'near';
}

/**
 * Sanitize show_title attribute.
 *
 * @param string $value Attribute value.
 * @return string
 */
function ptg_sanitize_show_title( $value ) {
    $value   = sanitize_key( trim( (string) $value ) );
    $allowed = array( 'yes', 'no' );

    if ( in_array( $value, $allowed, true ) ) {
        return $value;
    }

    return 'yes';
}

/**
 * Sanitize orderby attribute.
 *
 * @param string $value Attribute value.
 * @return string
 */
function ptg_sanitize_orderby( $value ) {
    $value   = sanitize_key( $value );
    $allowed = array( 'date', 'menu_order', 'title', 'ID' );

    if ( in_array( $value, $allowed, true ) ) {
        return $value;
    }

    return 'date';
}

/**
 * Sanitize order attribute.
 *
 * @param string $value Attribute value.
 * @return string
 */
function ptg_sanitize_order( $value ) {
    $value   = strtoupper( sanitize_key( $value ) );
    $allowed = array( 'ASC', 'DESC' );

    if ( in_array( $value, $allowed, true ) ) {
        return $value;
    }

    return 'DESC';
}



