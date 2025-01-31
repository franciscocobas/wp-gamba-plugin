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

/**
 * Shortcode: [product_archive_grid]
 * Displays two identical grids of products from the current WooCommerce category archive.
 * Also enqueues a custom JavaScript file for DOM manipulation.
 */
function product_archive_grid_shortcode() {
  // Ensure we are on a product category archive page
  if ( ! is_tax('product_cat') ) {
    return '<p>This shortcode can only be used on a product category archive page.</p>';
  }

  // Enqueue CSS file ONLY on this WooCommerce category archive page
  wp_enqueue_style('product-grid-css', plugin_dir_url(__FILE__) . '../assets/css/product-grid.css');

  // Enqueue the JavaScript file for dynamic manipulation
  wp_enqueue_script('product-grid-js', plugin_dir_url(__FILE__) . '../assets/js/product-grid.js', array('jquery'), null, true);

  // Get the current category
  $currentTerm = get_queried_object();
  if ( ! $currentTerm || ! is_a($currentTerm, 'WP_Term') ) {
    return '<p>Invalid category.</p>';
  }

  // Fetch products from the current category
  $queryArgs = [
    'post_type'      => 'product',
    'posts_per_page' => 12, // Change as needed
    'tax_query'      => [
      [
        'taxonomy' => 'product_cat',
        'field'    => 'term_id',
        'terms'    => $currentTerm->term_id,
      ],
    ],
  ];
  $products = new WP_Query($queryArgs);

  // Check if there are products
  if ( ! $products->have_posts() ) {
    return '<p>No products found in this category.</p>';
  }

  // Function to generate the product grid HTML
  function generate_product_grid($gridId, $products, $hideExtra = false, $fullImage = false, $hideInfo = false) {
    $gridHtml = '<div class="product-grid" id="' . esc_attr($gridId) . '">';

    $count = 0; // Counter for hiding extra cards in Grid 2
    while ( $products->have_posts() ) {
      $products->the_post();
      global $product;

      // Use full-size image for Grid 2, medium for Grid 1
      $imageSize = $fullImage ? 'full' : 'medium';
      $productImage = get_the_post_thumbnail_url(get_the_ID(), $imageSize);
      $productName  = get_the_title();
      $productDesc  = apply_filters('the_content', get_the_content()); // Main product description
      $productPrice = $product->get_price_html();
      $productUrl   = get_permalink();
      $addToCartUrl = $product->add_to_cart_url();
      $addToCartText = $product->is_type('variable') ? 'View Options' : 'Add to Cart';

      // If $hideExtra is true (for Grid 2), apply "display: none" to all except the first card
      $hiddenClass = ($hideExtra && $count > 0) ? ' hidden-card' : '';

      $gridHtml .= '
        <div class="product-card' . esc_attr($hiddenClass) . '">
          <a href="#">
            <img src="' . esc_url($productImage) . '" alt="' . esc_attr($productName) . '" class="product-image' . ($fullImage ? ' full-image' : '') . '">
          </a>';

      // Only include product info if $hideInfo is false (Grid 2)
      if (!$hideInfo) {
        $gridHtml .= '
          <div class="product-info">
            <div class="product-data">
              <h3 class="product-title"><a href="' . esc_url($productUrl) . '">' . esc_html($productName) . '</a></h3>
              <div class="product-description">' . wp_kses_post($productDesc) . '</div>
            </div>
            <div class="product-actions">
              <span class="product-price">' . wp_kses_post($productPrice) . '</span>
              <a href="' . esc_url($addToCartUrl) . '" class="product-add-to-cart">
                Agregar al Carrito
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                  <path d="M9 22C9.55228 22 10 21.5523 10 21C10 20.4477 9.55228 20 9 20C8.44772 20 8 20.4477 8 21C8 21.5523 8.44772 22 9 22Z" stroke="#B59CD8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M20 22C20.5523 22 21 21.5523 21 21C21 20.4477 20.5523 20 20 20C19.4477 20 19 20.4477 19 21C19 21.5523 19.4477 22 20 22Z" stroke="#B59CD8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M1 1H5L7.68 14.39C7.77144 14.8504 8.02191 15.264 8.38755 15.5583C8.75318 15.8526 9.2107 16.009 9.68 16H19.4C19.8693 16.009 20.3268 15.8526 20.6925 15.5583C21.0581 15.264 21.3086 14.8504 21.4 14.39L23 6H6" stroke="#B59CD8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>
            </div>
          </div>';
      }

      $gridHtml .= '</div>'; // Close product-card
      $count++; // Increment counter
    }

    wp_reset_postdata();
    $gridHtml .= '</div>'; // Close product grid

    return $gridHtml;
  }

  $output = '<div class="grid-container">';
  // Generate two identical grids
  $output .= generate_product_grid('productGrid1', $products, false, false, true);

  $output .= '<div class="right-container">';

  $output .= generate_product_grid('productGrid2', $products, true, true, false);

  // Add pagination controls for Grid 2
  $output .= '
    <div class="pagination-container">
      <button id="prevProduct" disabled>
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
          <path d="M19 12H5" stroke="#FF2E5B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M12 19L5 12L12 5" stroke="#FF2E5B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
      <span id="photoCounter">Photo <span class="current-item">1</span> of ' . esc_html($totalProducts) . '</span>
      <button id="nextProduct">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
          <path d="M5 12H19" stroke="#FF2E5B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M12 5L19 12L12 19" stroke="#FF2E5B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </div>';

  $output .= '</div>'; // Close right container
  $output .= '</div>'; // Close grid container

  return $output;
}
add_shortcode('product_archive_grid', 'product_archive_grid_shortcode');
