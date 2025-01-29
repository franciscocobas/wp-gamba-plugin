<?php
/**
 * Shortcode: [full_product_breadcrumb]
 *
 * Displays a breadcrumb for a single product in the format:
 *   Gallery > Parent Category > Sub Category > Product Name
 *
 * Usage: [full_product_breadcrumb]
 */
function full_product_breadcrumb_shortcode() {
  // Only apply on single product pages
  if ( ! is_singular('product') ) {
    return '';
  }

  // Get the "Gallery" page by slug "galeria"
  $galleryPage = get_page_by_path('galeria');
  if ( ! $galleryPage ) {
    return ''; // If the Gallery page doesn't exist, do nothing
  }
  $galleryUrl = get_permalink($galleryPage);

  // Get the product's categories
  global $post;
  $productCats = get_the_terms($post->ID, 'product_cat');
  if ( empty($productCats) || is_wp_error($productCats) ) {
    return ''; // No product categories found
  }

  // We'll pick the category with the deepest level (in case there's more than one).
  // This ensures we always get the "lowest" subcategory chain.
  usort($productCats, function($a, $b) {
    return $b->parent - $a->parent;
  });
  $primaryCat = reset($productCats);

  // Gather all ancestors of that category (from bottom to top)
  $ancestors = get_ancestors($primaryCat->term_id, 'product_cat');
  // $ancestors will be an array of parent IDs, sorted from child to parent

  // We want them in ascending order from top-level to the subcategory
  $ancestors = array_reverse($ancestors);

  // Build the breadcrumb segments
  $segments = [];

  // 1) Always start with "Gallery"
  $segments[] = '<a href="' . esc_url($galleryUrl) . '">Gallery</a>';

  // 2) Then each ancestor category (in ascending order)
  foreach ($ancestors as $ancestorId) {
    $ancestorTerm = get_term($ancestorId, 'product_cat');
    if ( $ancestorTerm && ! is_wp_error($ancestorTerm) ) {
      $segments[] = '<a href="' . esc_url(get_term_link($ancestorTerm)) . '">'
                  . esc_html($ancestorTerm->name) . '</a>';
    }
  }

  // 3) Then the "final" category (the one we chose as primary)
  //    (but only if it’s not in the ancestors array, which it shouldn’t be)
  if ( ! in_array($primaryCat->term_id, $ancestors, true) ) {
    $segments[] = '<a href="' . esc_url(get_term_link($primaryCat)) . '">'
                . esc_html($primaryCat->name) . '</a>';
  }

  // 4) Finally, the product name (no link)
  $segments[] = get_the_title($post->ID);

  // Join them with " > "
  $breadcrumbHtml = '<nav class="breadcrumb-nav">' . implode(' <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M9 18L15 12L9 6" stroke="#FFA3B7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> ', $segments) . '</nav>';

  return $breadcrumbHtml;
}
add_shortcode('breadcrum_producto_simple', 'full_product_breadcrumb_shortcode');
