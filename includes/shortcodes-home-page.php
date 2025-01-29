<?php
// Función para mostrar las últimas 3 categorías de productos
function mostrar_ultimas_categorias() {
  // Obtener las últimas 3 categorías de productos
  $terms = get_terms([
    'taxonomy'   => 'product_cat', // Taxonomía de categorías de productos en WooCommerce
    'orderby'    => 'id',          // Ordenar por ID (últimas creadas primero)
    'order'      => 'DESC',        // Orden descendente
    'number'     => 3,             // Limitar a 3 categorías
    'hide_empty' => false,         // Incluir categorías vacías
  ]);

  // Verificar que se obtuvieron categorías
  if (is_wp_error($terms) || empty($terms)) {
    return '<p>No hay categorías disponibles.</p>';
  }

  // Iniciar el HTML para las cards
  $output = '<div class="categorias-grid">';

  foreach ($terms as $term) {
    // Obtener la miniatura de la categoría
    $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
    $thumbnail_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : wc_placeholder_img_src();

    // Obtener la categoría padre (si tiene)
    $parent_id = $term->parent;
    $parent_name = $parent_id ? get_term($parent_id)->name : null;

    // Generar el HTML de la card
    $output .= '
      <div class="category-card">
        <a href="' . esc_url(get_term_link($term)) . '">
          <img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($term->name) . '" class="category-thumbnail">
          <h3 class="category-title">' . esc_html($term->name) . '</h3>
        </a>';

    // Mostrar la categoría padre si existe, fuera del enlace
    if ($parent_name) {
      $output .= '<p class="parent-category">' . esc_html($parent_name) . '</p>';
    }

    $output .= '</div>';
  }

  $output .= '</div>';

  return $output;
}
add_shortcode('ultimas_categorias', 'mostrar_ultimas_categorias');
