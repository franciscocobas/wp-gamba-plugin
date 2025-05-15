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
  $breadcrumbHtml .= '  <a href="' . esc_url($galleryUrl) . '">Galería</a> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M9 18L15 12L9 6" stroke="#FFA3B7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> ';
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

  $css_file = plugin_dir_path(__FILE__) . '../assets/css/product-grid.css';

  // Enqueue CSS file ONLY on this WooCommerce category archive page
  wp_enqueue_style(
    'product-grid-css',
    plugin_dir_url(__FILE__) . '../assets/css/product-grid.css',
    [],
    filemtime($css_file)
  );

  $js_file = plugin_dir_path(__FILE__) . '../assets/js/product-grid.js';

  // Enqueue the JavaScript file for dynamic manipulation
  wp_enqueue_script(
    'product-grid-js',
    plugin_dir_url(__FILE__) . '../assets/js/product-grid.js',
    array('jquery'),
    filemtime($js_file), // Versión basada en fecha de modificación
    true // Cargar en footer
  );

  // Get the current category
  $currentTerm = get_queried_object();
  if ( ! $currentTerm || ! is_a($currentTerm, 'WP_Term') ) {
    return '<p>Invalid category.</p>';
  }

  // Fetch products from the current category
  $queryArgs = [
    'post_type'      => 'product',
    'posts_per_page' => -1, // Change as needed
    'orderby'        => 'date',
    'order'          => 'ASC',
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
      $product_id     = $product->get_id();
      $imageSize = $fullImage ? 'full' : 'medium';
      $productImage = get_the_post_thumbnail_url(get_the_ID(), $imageSize);
      $productName  = get_the_title();
      $productDesc  = apply_filters('the_content', get_the_content()); // Main product description
      $productPrice = $product->get_price_html();
      $productUrl   = get_permalink();
      $whatsappLink = "https://wa.me/?text=" . rawurlencode("Te comparto esta foto: $productUrl");
      $emailLink = "mailto:?subject=" . rawurlencode("Te recomiendo este producto") . "&body=" . rawurlencode("Mira este enlace: $productUrl");
      $addToCartUrl = $product->add_to_cart_url();
      $addToCartText = $product->is_type('variable') ? 'View Options' : 'Add to Cart';

      // IDs únicos
      $popupId        = "sharePopup_" . $product_id;
      $buttonId       = "openSharePopup_" . $product_id;
      $copyButtonId   = "copyLink_" . $product_id;

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
              <div class="share-actions">
                <div id="' . esc_attr($popupId) . '" class="share-popup-2 hidden">
                  <div class="share-popup-content">
                    <div class="share-buttons-2">
                      <a href="' . esc_url($whatsappLink) . '" target="_blank" rel="noopener" aria-label="Compartir en WhatsApp">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none">
                          <g clip-path="url(#clip0_1365_3019)">
                            <path d="M27.2766 20.5618C26.3097 19.5975 24.7535 19.5975 23.7867 20.5618L22.3374 22.0615C19.439 20.7772 17.9352 18.1521 17.3983 17.0812L18.8475 15.6358C19.8144 14.6716 19.8144 13.1195 18.8475 12.1553L16.1629 9.47789C15.6805 8.99679 15.0891 8.78134 14.4452 8.78134C13.8014 8.78134 13.1575 9.04908 12.7275 9.47789L11.6537 10.5488C10.7938 11.4587 10.2045 12.5841 10.1499 13.8161C9.98844 15.9036 10.2569 20.0286 13.9083 23.9901L14.6591 24.7933C18.363 28.6483 22.2828 29.3992 24.7536 29.3992C25.0766 29.3992 25.3975 29.3992 25.666 29.3448C26.9013 29.2382 28.0821 28.7027 28.9944 27.8451L30.0682 26.7741C30.5506 26.293 30.7666 25.7032 30.7666 25.0611C30.7666 24.4189 30.4982 23.7768 30.0682 23.348L27.2766 20.5618ZM28.4574 25.22L27.3835 26.2909C26.8466 26.8264 26.1482 27.1485 25.4498 27.2008C23.5161 27.3618 19.7054 27.0397 16.1607 23.2914L15.4098 22.5426C12.3499 19.1687 12.0814 15.7426 12.2429 14.0296C12.2975 13.3331 12.6183 12.6365 13.1552 12.1011L14.2291 11.0301C14.2836 10.9757 14.3906 10.9234 14.443 10.9234C14.4975 10.9234 14.6045 10.9234 14.6569 11.0301L17.3415 13.7075C17.4485 13.8142 17.4485 14.0296 17.3415 14.1363L15.8923 15.5816C15.2484 16.2238 15.0869 17.2425 15.4623 18.0457C16.2677 19.6521 18.0399 22.5428 21.4752 24.0424C22.2806 24.4169 23.2474 24.2035 23.8913 23.6136L25.3951 22.1139C25.502 22.0072 25.7181 22.0072 25.825 22.1139L28.5096 24.7913C28.5641 24.8456 28.6166 24.9523 28.6166 25.0046C28.6187 25.059 28.5664 25.1133 28.4574 25.22Z" fill="#B59CD8"/>
                            <path d="M20.189 0.00064242C14.8744 0.00064242 9.93289 2.03587 6.1745 5.78429C-0.268456 12.21 -1.50389 22.0097 3.11452 29.826L0 40L9.66443 36.5194C17.4497 41.3931 27.7058 40.2677 34.2008 33.7354C37.9592 29.9871 40 25.0612 40 19.7584C40 14.4581 37.9593 9.52983 34.2008 5.78151C30.4445 2.03319 25.5034 0 20.1885 0L20.189 0.00064242ZM32.6996 32.2356C26.7388 38.1805 17.3976 39.0902 10.4177 34.4319L9.98777 34.1098L3.33112 36.5194L5.47878 29.5037L5.21032 29.0749C0.808064 22.0591 1.82734 13.1172 7.68098 7.28162C11.0094 3.96211 15.4662 2.14021 20.1916 2.14021C24.9168 2.14021 29.3739 3.95997 32.7022 7.28162C36.0306 10.6011 37.8574 15.046 37.8574 19.7587C37.8532 24.4713 36.0281 28.9162 32.6996 32.2356Z" fill="#B59CD8"/>
                          </g>
                          <defs>
                            <clipPath id="clip0_1365_3019">
                              <rect width="40" height="40" fill="white"/>
                            </clipPath>
                          </defs>
                        </svg>
                      </a>
                      <a href="' . esc_url($emailLink) . '" target="_blank" rel="noopener" class="email-link" aria-label="Compartir por Email">
                        <svg xmlns="http://www.w3.org/2000/svg" width="57" height="40" viewBox="0 0 57 40" fill="none">
                          <g clip-path="url(#clip0_1365_3013)">
                            <path d="M5.56097 0C2.47561 0 0 2.57465 0 5.6681V34.3103C0 37.4037 2.47561 40 5.56097 40H51.439C54.5244 40 57 37.4037 57 34.3103V5.6681C57 2.57465 54.5244 0 51.439 0H5.56097ZM5.56097 2.75862H51.439C51.8505 2.75862 52.239 2.8762 52.5903 3.03875L30.2161 24.1164C29.1891 25.0837 27.8561 25.0838 26.8274 24.1164L4.40971 3.03875C4.76103 2.8762 5.14953 2.75862 5.56097 2.75862ZM2.82393 5.34482L18.6597 20.2371L3.0846 35.6682C2.88955 35.2663 2.78049 34.8063 2.78049 34.3103V5.6681C2.78049 5.5561 2.81351 5.45275 2.82393 5.34482ZM54.1761 5.34482C54.1872 5.45275 54.2192 5.5561 54.2192 5.6681V34.3103C54.2192 34.7991 54.1049 35.2489 53.915 35.6465L38.3835 20.2155L54.1761 5.34482ZM36.3636 22.1336L51.5694 37.2414C51.5256 37.2414 51.4831 37.2414 51.439 37.2414H5.56098C5.52413 37.2414 5.48868 37.2414 5.4524 37.2414L20.6799 22.1552L24.9158 26.1207C26.9333 28.0181 30.1107 28.0207 32.1277 26.1207L36.3636 22.1336Z" fill="#B59CD8"/>
                          </g>
                          <defs>
                            <clipPath id="clip0_1365_3013">
                              <rect width="57" height="40" fill="white"/>
                            </clipPath>
                          </defs>
                        </svg>
                      </a>
                      <button id="' . esc_attr($copyButtonId) . '" class="copy-link-btn" data-link="' . esc_url($productUrl) . '" aria-label="Copiar enlace">
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="41" viewBox="0 0 36 41" fill="none">
                          <g clip-path="url(#clip0_1365_3011)">
                            <path d="M11.0041 29.2536C10.4757 29.775 10.4757 30.6179 11.0041 31.1393C11.5324 31.6608 12.3865 31.6608 12.9149 31.1393L26.0689 18.1578C28.2635 15.9907 28.2635 12.4646 26.0689 10.2975C25.0054 9.24656 23.5905 8.6691 22.0865 8.6691C20.5824 8.6691 19.1676 9.2479 18.1041 10.2975L2.59054 25.6074C0.92027 27.2558 0 29.4469 0 31.7781C0 34.1093 0.92027 36.3004 2.59054 37.9474C4.31351 39.6492 6.57838 40.5 8.84324 40.5C11.1081 40.5 13.373 39.6492 15.0959 37.9474L32.4703 20.8011C34.7473 18.5539 36 15.5679 36 12.3899C36 9.21189 34.7459 6.22457 32.4703 3.97875C27.7716 -0.659585 20.123 -0.659585 15.4257 3.97875L0.412162 18.7953C-0.116216 19.3167 -0.116216 20.1596 0.412162 20.681C0.940541 21.2025 1.79459 21.2025 2.32297 20.681L17.3365 5.86716C20.9811 2.27038 26.9149 2.27038 30.5595 5.86716C32.3257 7.61021 33.2973 9.92671 33.2973 12.3926C33.2973 14.8584 32.3243 17.1736 30.5595 18.918L13.1851 36.063C10.7919 38.4249 6.89595 38.4249 4.50135 36.063C3.34189 34.9188 2.7027 33.3971 2.7027 31.7794C2.7027 30.1604 3.34189 28.6388 4.50135 27.4945L20.0149 12.1845C21.1581 11.0563 23.0162 11.0563 24.1581 12.1845C25.3 13.3114 25.3 15.1452 24.1581 16.2721L11.0041 29.2536Z" fill="#B59CD8"/>
                          </g>
                          <defs>
                            <clipPath id="clip0_1365_3011">
                              <rect width="36" height="40" fill="white" transform="translate(0 0.5)"/>
                            </clipPath>
                          </defs>
                        </svg>
                      </button>
                    </div>
                    <p class="copy-message" style="display:none;">¡Enlace copiado!</p>
                  </div>
                </div>
                <button id="' .  esc_attr($buttonId)  . '" class="share-photo" data-popup="' . esc_attr($popupId) . '">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 8C19.6569 8 21 6.65685 21 5C21 3.34315 19.6569 2 18 2C16.3431 2 15 3.34315 15 5C15 6.65685 16.3431 8 18 8Z" stroke="#B59CD8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M6 15C7.65685 15 9 13.6569 9 12C9 10.3431 7.65685 9 6 9C4.34315 9 3 10.3431 3 12C3 13.6569 4.34315 15 6 15Z" stroke="#B59CD8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M18 22C19.6569 22 21 20.6569 21 19C21 17.3431 19.6569 16 18 16C16.3431 16 15 17.3431 15 19C15 20.6569 16.3431 22 18 22Z" stroke="#B59CD8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M8.58984 13.5098L15.4198 17.4898" stroke="#B59CD8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M15.4098 6.50977L8.58984 10.4898" stroke="#B59CD8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <span>Compartir foto</span>
                </button>
                <p id="holis"></p>
              </div>
              <div class="price-and-cart">
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
      <span id="photoCounter">Foto <span class="current-item">1</span> de ' . esc_html($totalProducts) . '</span>
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
