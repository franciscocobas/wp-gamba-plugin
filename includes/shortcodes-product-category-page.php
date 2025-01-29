<?php
/**
 * Shortcode: [simple_breadcrumb]
 * Displays a simple breadcrumb with:
 *   - Link to the "Gallery" page (slug: 'galeria')
 *   - Link to the parent category of the current subcategory
 */
function custom_breadcrumb_shortcode() {
  // Get the current queried object (should be a WP_Term for taxonomy 'product_cat')
  $currentTerm = get_queried_object();

  // Check if we have a valid subcategory with a parent
  if (
    ! $currentTerm
    || ! is_a($currentTerm, 'WP_Term')
    || $currentTerm->taxonomy !== 'product_cat'
    || $currentTerm->parent === 0
  ) {
    return ''; // Do nothing if it's not a subcategory or has no parent
  }

  // Get the parent category
  $parentTerm = get_term($currentTerm->parent, 'product_cat');
  if (is_wp_error($parentTerm) || ! $parentTerm) {
    return ''; // Something went wrong, or there's no parent
  }

  // Get the "Gallery" page by slug
  $galleryPage = get_page_by_path('galeria');
  if (! $galleryPage) {
    return ''; // "galeria" page not found
  }

  // Build links
  $galleryUrl  = get_permalink($galleryPage);
  $parentUrl   = get_term_link($parentTerm);

  // Construct the breadcrumb HTML
  $breadcrumbHtml  = '<nav class="breadcrumb-nav">';
  $breadcrumbHtml .= '  <a href="' . esc_url($galleryUrl) . '">Gallery</a> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M9 18L15 12L9 6" stroke="#FFA3B7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> ';
  $breadcrumbHtml .= '  <a href="' . esc_url($parentUrl) . '">' . esc_html($parentTerm->name) . '</a>';
  $breadcrumbHtml .= '</nav>';

  return $breadcrumbHtml;
}
add_shortcode('simple_breadcrumb', 'custom_breadcrumb_shortcode');
