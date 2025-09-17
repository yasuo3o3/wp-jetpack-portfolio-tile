# Portfolio Tiles Grid for Jetpack Portfolio

Responsive tile grid shortcode for Jetpack Portfolio posts. Outputs featured images in a CSS Grid layout with lazy loading, breakpoint-specific column counts, and optional taxonomy filtering.

## Requirements
- WordPress 6.2+
- PHP 7.4+
- Jetpack with the Portfolio custom post type enabled and featured images assigned

## Installation
1. Copy this repository into `wp-content/plugins/portfolio-tiles-grid-for-jetpack-portfolio/`.
2. Activate **Portfolio Tiles Grid for Jetpack Portfolio** from the WordPress admin.
3. Ensure Jetpack Portfolio items include a featured image (items without thumbnails are skipped).

## Usage
Insert the shortcode in any post, page, or block content:

```
[portfolio_tiles]
```

### Shortcode Attributes
| Attribute | Default | Allowed | Description |
| --- | --- | --- | --- |
| `cols_pc` | `3` | 1–4 | Desktop column count (≥1024px). |
| `cols_tb` | `2` | 1–4 | Tablet column count (600–1023px). |
| `cols_sp` | `1` | 1–4 | Mobile column count (<600px). |
| `rows` | `3` | 1–12 | Number of rows. Total posts = `cols_pc * rows`. |
| `gap` | `0` | CSS length | Gap between tiles (e.g. `12px`, `1.5rem`). |
| `aspect` | `1:1` | `1:1`, `16:9`, `4:3`, `3:4` | Aspect ratio applied via `aspect-ratio`. |
| `size` | `medium_large` | WP image sizes | Image size passed to `wp_get_attachment_image_src()`. |
| `type_in` | — | Slugs | Include Jetpack Portfolio types (comma separated). |
| `type_ex` | — | Slugs | Exclude Jetpack Portfolio types. |
| `tag_in` | — | Slugs | Include Jetpack Portfolio tags. |
| `tag_ex` | — | Slugs | Exclude Jetpack Portfolio tags. |

Example with custom layout and filtering:

```
[portfolio_tiles cols_pc="4" cols_tb="3" cols_sp="2" rows="2" gap="16px" aspect="16:9" type_in="branding" tag_ex="archived"]
```

## Caching
The rendered markup is cached for 60 seconds using a transient keyed by shortcode attributes and locale. Editing portfolio entries or clearing caches regenerates the grid.

## Development
- Run PHPCS: `vendor/bin/phpcs --standard=WordPress,WordPress-Extra` (if dependencies installed)
- Lint PHP: `php -l portfolio-tiles-grid-for-jetpack-portfolio.php`
- Flush transients during testing: delete entries starting with `ptg_tiles_`

See `readme.txt` for the WordPress.org plugin description and changelog.
