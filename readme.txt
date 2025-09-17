=== Portfolio Tiles Grid for Jetpack Portfolio ===
Contributors: netservice
Tags: jetpack, portfolio, grid, shortcode, responsive
Requires at least: 6.2
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.01
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Portfolio Tiles Grid for Jetpack Portfolio adds a responsive shortcode for displaying Jetpack Portfolio items as a tiled image grid. Each item links to the portfolio single page, enforces the presence of featured images, and adapts automatically across desktop, tablet, and mobile breakpoints.

* Responsive CSS Grid layout with customizable column counts per breakpoint
* Lazy-loaded thumbnails with object-fit cover and configurable aspect ratio
* Optional filtering by Jetpack Portfolio type or tag (include / exclude)
* Lightweight: single shortcode, inline CSS, and 60-second result caching via transients

== Installation ==
1. Upload the plugin files to the /wp-content/plugins/portfolio-tiles-grid-for-jetpack-portfolio/ directory or install via the WordPress admin.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Ensure Jetpack and the Portfolio custom post type module are enabled with featured images set for each item.

== Usage ==
Add the shortcode to any post, page, or block content:

```
[portfolio_tiles]
```

By default the grid displays 3 columns on desktop, 2 on tablets, 1 on mobile, and 3 rows of posts (9 items total). Images use the medium_large size.

== Shortcode Attributes ==
* `cols_pc` (default `3`): Desktop columns (min 1, max 4).
* `cols_tb` (default `2`): Tablet columns (min 1, max 4).
* `cols_sp` (default `1`): Mobile columns (min 1, max 4).
* `rows` (default `3`): Number of rows to display (min 1, max 12). Total posts = `cols_pc * rows`.
* `gap` (default `0`): Gap between tiles. Accepts numeric values with optional `px`, `rem`, `em`, or `%` unit (e.g. `12px`).
* `aspect` (default `1:1`): Aspect ratio for images. Allowed values: `1:1`, `16:9`, `4:3`, `3:4`.
* `size` (default `medium_large`): Registered image size to use for thumbnails.
* `type_in`: Comma-separated Jetpack Portfolio type slugs to include.
* `type_ex`: Comma-separated Jetpack Portfolio type slugs to exclude.
* `tag_in`: Comma-separated Jetpack Portfolio tag slugs to include.
* `tag_ex`: Comma-separated Jetpack Portfolio tag slugs to exclude.

Example:

```
[portfolio_tiles cols_pc="3" cols_tb="2" cols_sp="1" rows="3" gap="12px" aspect="16:9" type_in="branding,web-design"]
```

== Frequently Asked Questions ==

= Why are some items missing from the grid? =
Only portfolio items with a featured image are displayed. Ensure each Jetpack Portfolio post has an assigned thumbnail.

= How long is the cache stored? =
Query results are cached for 60 seconds using WordPress transients. Editing portfolio items or clearing caches will regenerate the output.

== Screenshots ==
1. Responsive portfolio tile grid on desktop

== Changelog ==

= 0.01 =
* Initial release with `[portfolio_tiles]` shortcode for Jetpack Portfolios.
