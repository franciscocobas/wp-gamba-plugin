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

/**
 * Shortcode: [related_products_grid]
 * Muestra una grilla de 4 productos al azar relacionados (de la misma subcategoría)
 * en la página de un producto.
 *
 * Cada card muestra la foto, el título, el precio y un botón de agregar al carrito.
 */
function related_products_grid_shortcode() {
  // Asegurarse de que estamos en la página de un producto
  if ( ! is_product() ) {
    return '';
  }

  global $post, $product;

  // Enqueue CSS file ONLY on this WooCommerce category archive page
  wp_enqueue_style('product-page', plugin_dir_url(__FILE__) . '../assets/css/product-page.css');


  // Obtener las categorías del producto actual
  $terms = get_the_terms( $post->ID, 'product_cat' );
  if ( ! $terms || is_wp_error( $terms ) ) {
    return '';
  }

  // Filtrar solo las subcategorías (donde el padre es distinto de 0)
  $subterms = array_filter( $terms, function( $term ) {
    return $term->parent != 0;
  } );

  // Si existen subcategorías, usar sus IDs; de lo contrario, se usarán todas las categorías
  if ( ! empty( $subterms ) ) {
    $cat_ids = wp_list_pluck( $subterms, 'term_id' );
  } else {
    $cat_ids = wp_list_pluck( $terms, 'term_id' );
  }

  // Argumentos para obtener 4 productos al azar de las mismas categorías, excluyendo el producto actual
  $args = array(
    'post_type'      => 'product',
    'posts_per_page' => 4,
    'orderby'        => 'rand',
    'post__not_in'   => array( $post->ID ),
    'tax_query'      => array(
      array(
        'taxonomy' => 'product_cat',
        'field'    => 'term_id',
        'terms'    => $cat_ids,
      ),
    ),
  );

  $related_query = new WP_Query( $args );
  if ( ! $related_query->have_posts() ) {
    return '<p>No se encontraron productos relacionados.</p>';
  }

  // Iniciar la grilla HTML
  $output = '<div class="related-products-grid">';

  while ( $related_query->have_posts() ) {
    $related_query->the_post();
    global $product;

    // Datos del producto
    $product_image   = get_the_post_thumbnail_url( get_the_ID(), 'woocommerce_single' );;
    $product_title   = get_the_title();
    $product_price   = $product->get_price_html();
    $product_url     = get_permalink();
    $add_to_cart_url = $product->add_to_cart_url();
    $add_to_cart_text = $product->is_type('variable') ? 'Ver opciones' : 'Agregar al carrito';

    $output .= '<div class="related-product-card">';
      $output .= '<a href="' . esc_url( $product_url ) . '">';
        $output .= '<img src="' . esc_url( $product_image ) . '" alt="' . esc_attr( $product_title ) . '" class="related-product-image">';
      $output .= '</a>';
      $output .= '<h3 class="related-product-title"><a href="' . esc_url( $product_url ) . '">' . esc_html( $product_title ) . '</a></h3>';
      $output .= '<span class="related-product-price">' . wp_kses_post( $product_price ) . '</span>';
      $output .= '<a href="' . esc_url( $add_to_cart_url ) . '" class="btn related-product-add-to-cart">' . esc_html( $add_to_cart_text ) . '</a>';
    $output .= '</div>';
  }

  wp_reset_postdata();

  $output .= '</div>';

  return $output;
}
add_shortcode( 'related_products_grid', 'related_products_grid_shortcode' );
