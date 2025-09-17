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

     = '.ptg-grid{display:grid;grid-template-columns:repeat(var(--ptg-cols),1fr);gap:var(--ptg-gap);}' .
        '.ptg-grid{--ptg-cols:var(--ptg-cols-sp);--ptg-gap:0;--ptg-aspect:1/1;}' .
        '@media (min-width:600px){.ptg-grid{--ptg-cols:var(--ptg-cols-tb,var(--ptg-cols-sp));}}' .
        '@media (min-width:1024px){.ptg-grid{--ptg-cols:var(--ptg-cols-pc,var(--ptg-cols-tb,var(--ptg-cols-sp)));}}' .
        '.ptg-item{display:block;position:relative;}' .
        '.ptg-item img{width:100%;height:100%;object-fit:cover;aspect-ratio:var(--ptg-aspect);display:block;}' .
        '.ptg-item .screen-reader-text{position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;}';

    wp_add_inline_style( 'ptg-portfolio-tiles-grid',  );
}

/**
 * Render the portfolio tiles shortcode.
 *
 * @param array  Shortcode attributes.
 * @return string
 */
function ptg_render_portfolio_tiles(  ) {
     = array(
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

     = shortcode_atts( , , 'portfolio_tiles' );

     = ptg_sanitize_column_value( ['cols_pc'] );
     = ptg_sanitize_column_value( ['cols_tb'] );
     = ptg_sanitize_column_value( ['cols_sp'] );
        = ptg_sanitize_row_value( ['rows'] );

     = max( 1,  *  );

     = ptg_sanitize_gap( ['gap'] );

     = ptg_sanitize_aspect( ['aspect'] );

     = ptg_sanitize_image_size( ['size'] );

     = ptg_sanitize_slug_list( ['type_in'] );
     = ptg_sanitize_slug_list( ['type_ex'] );
      = ptg_sanitize_slug_list( ['tag_in'] );
      = ptg_sanitize_slug_list( ['tag_ex'] );

     = ptg_build_cache_key( compact( 'cols_pc', 'cols_tb', 'cols_sp', 'rows', 'gap', 'aspect', 'size', 'type_in', 'type_ex', 'tag_in', 'tag_ex' ) );
        = get_transient(  );

    ptg_enqueue_assets();

    if ( false !==  ) {
        return ;
    }

     = array(
        'post_type'      => 'jetpack-portfolio',
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'posts_per_page' => ,
        'meta_query'     => array(
            array(
                'key'     => '_thumbnail_id',
                'compare' => 'EXISTS',
            ),
        ),
    );

     = ptg_build_tax_query( , , ,  );
    if ( ! empty(  ) ) {
        ['tax_query'] = ;
    }

     = new WP_Query(  );

    if ( ! ->have_posts() ) {
        wp_reset_postdata();
         = esc_html__( 'No items', 'portfolio-tiles-grid' );
        set_transient( , , PTG_TRANSIENT_TTL );
        return ;
    }

     = sprintf(
        'style="--ptg-cols-pc:%1;--ptg-cols-tb:%2;--ptg-cols-sp:%3;--ptg-gap:%4;--ptg-aspect:%5;"',
        (int) ,
        (int) ,
        (int) ,
        esc_attr(  ),
        esc_attr(  )
    );

    ob_start();
    printf( '<div class="ptg-grid" role="list" %s>',  );

    while ( ->have_posts() ) {
        ->the_post();
          = get_the_ID();
              = get_permalink(  );
            = get_the_title(  );
         = get_post_thumbnail_id(  );

        if ( !  ) {
            continue;
        }

         = wp_get_attachment_image_src( ,  );
        if ( !  ) {
            continue;
        }

         = [0];

        printf(
            '<a role="listitem" class="ptg-item" href="%1"><img src="%2" alt="%3" loading="lazy" decoding="async" /><span class="screen-reader-text">%3</span></a>',
            esc_url(  ),
            esc_url(  ),
            esc_attr(  )
        );
    }

    echo '</div>';

    wp_reset_postdata();

     = ob_get_clean();

    set_transient( , , PTG_TRANSIENT_TTL );

    return ;
}

/**
 * Sanitize column counts.
 *
 * @param mixed  Attribute value.
 * @return int
 */
function ptg_sanitize_column_value(  ) {
     = absint(  );

    if (  < 1 ) {
         = 1;
    }

    if (  > 4 ) {
         = 4;
    }

    return ;
}

/**
 * Sanitize row value.
 *
 * @param mixed  Attribute value.
 * @return int
 */
function ptg_sanitize_row_value(  ) {
     = absint(  );

    if (  < 1 ) {
         = 1;
    }

    if (  > 12 ) {
         = 12;
    }

    return ;
}

/**
 * Sanitize gap value.
 *
 * @param string  Attribute value.
 * @return string
 */
function ptg_sanitize_gap(  ) {
     = trim( (string)  );

    if ( '' ===  ) {
        return '0';
    }

    if ( preg_match( '/^\d+(?:\.\d+)?(?:px|rem|em|%)?$/',  ) ) {
        return ;
    }

    return '0';
}

/**
 * Sanitize aspect ratio value.
 *
 * @param string  Attribute value.
 * @return string
 */
function ptg_sanitize_aspect(  ) {
     = array(
        '1:1'  => '1 / 1',
        '16:9' => '16 / 9',
        '4:3'  => '4 / 3',
        '3:4'  => '3 / 4',
    );

     = strtoupper( (string)  );

    if ( isset( [  ] ) ) {
        return [  ];
    }

    return ['1:1'];
}

/**
 * Sanitize image size value.
 *
 * @param string  Attribute value.
 * @return string
 */
function ptg_sanitize_image_size(  ) {
     = sanitize_key(  );

    if ( empty(  ) ) {
        return 'medium_large';
    }

     = get_intermediate_image_sizes();
    [] = 'thumbnail';
    [] = 'medium';
    [] = 'large';
    [] = 'full';

     = array_unique(  );

    if ( in_array( , , true ) ) {
        return ;
    }

    return 'medium_large';
}

/**
 * Sanitize comma separated slug list.
 *
 * @param string  Attribute value.
 * @return array
 */
function ptg_sanitize_slug_list(  ) {
     = trim( (string)  );
    if ( '' ===  ) {
        return array();
    }

     = array_filter( array_map( 'trim', explode( ',',  ) ) );
    if ( empty(  ) ) {
        return array();
    }

     = array();
    foreach (  as  ) {
         = sanitize_title(  );
        if ( '' !==  ) {
            [] = ;
        }
    }

    return array_unique(  );
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
function ptg_build_tax_query( , , ,  ) {
     = array();

    if ( ! empty(  ) ) {
        [] = array(
            'taxonomy' => 'jetpack-portfolio-type',
            'field'    => 'slug',
            'terms'    => ,
            'operator' => 'IN',
        );
    }

    if ( ! empty(  ) ) {
        [] = array(
            'taxonomy' => 'jetpack-portfolio-type',
            'field'    => 'slug',
            'terms'    => ,
            'operator' => 'NOT IN',
        );
    }

    if ( ! empty(  ) ) {
        [] = array(
            'taxonomy' => 'jetpack-portfolio-tag',
            'field'    => 'slug',
            'terms'    => ,
            'operator' => 'IN',
        );
    }

    if ( ! empty(  ) ) {
        [] = array(
            'taxonomy' => 'jetpack-portfolio-tag',
            'field'    => 'slug',
            'terms'    => ,
            'operator' => 'NOT IN',
        );
    }

    if ( count(  ) > 1 ) {
        ['relation'] = 'AND';
    }

    return ;
}

/**
 * Build a unique cache key.
 *
 * @param array  Data to hash.
 * @return string
 */
function ptg_build_cache_key(  ) {
    ['locale'] = get_locale();
    return 'ptg_tiles_' . md5( wp_json_encode(  ) );
}
