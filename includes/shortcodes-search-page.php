<?php

function custom_search_results_shortcode() {
  if (!isset($_GET['s']) || empty($_GET['s'])) {
    return '<p>No se encontraron resultados.</p>';
  }

  // Enqueue CSS file ONLY on this WooCommerce category archive page
  wp_enqueue_style('product-page', plugin_dir_url(__FILE__) . '../assets/css/search-page.css');

  $search_query = sanitize_text_field($_GET['s']);

  $args = array(
    'post_type'      => 'product',
    'posts_per_page' => -1,
    's'              => $search_query,
    'post_status'    => 'publish'
  );

  $query = new WP_Query($args);

  if (!$query->have_posts()) {
    return '<p>No se encontraron productos para "' . esc_html($search_query) . '".</p>';
  }

  ob_start();
  echo '<div class="custom-product-grid">';

  while ($query->have_posts()) {
    $query->the_post();
    global $product;

    $product_image   = get_the_post_thumbnail_url(get_the_ID(), 'woocommerce_single');
    $product_title   = get_the_title();
    $product_price   = $product->get_price_html();
    $product_url     = get_permalink();
    $add_to_cart_url = $product->add_to_cart_url();
    $add_to_cart_text = $product->is_type('variable') ? 'Ver opciones' : 'Agregar al carrito';

    echo '<div class="related-product-card">';
      echo '<a href="' . esc_url($product_url) . '">';
        echo '<img src="' . esc_url($product_image) . '" alt="' . esc_attr($product_title) . '" class="related-product-image">';
      echo '</a>';
      echo '<h3 class="related-product-title"><a href="' . esc_url($product_url) . '">' . esc_html($product_title) . '</a></h3>';
      echo '<span class="related-product-price">' . wp_kses_post($product_price) . '</span>';
      echo '<a href="' . esc_url($add_to_cart_url) . '" class="btn related-product-add-to-cart">' . esc_html($add_to_cart_text) . '</a>';
    echo '</div>';
  }

  echo '</div>';
  wp_reset_postdata();
  return ob_get_clean();
}
add_shortcode('custom_search_results', 'custom_search_results_shortcode');
