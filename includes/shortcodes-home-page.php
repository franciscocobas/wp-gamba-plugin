<?php
// Función para mostrar las últimas 3 subcategorías de productos
function mostrar_ultimas_subcategorias() {
  // Obtener todas las subcategorías (categorías con padre)
  $terms = get_terms([
    'taxonomy'   => 'product_cat',
    'meta_key'   => 'fecha_de_orden',
    'orderby'    => 'meta_value_num',
    'order'      => 'DESC',
    'hide_empty' => false,
    'number'     => 10, // Buscar hasta 10 categorías para asegurar subcategorías
  ]);

  // Filtrar para obtener solo subcategorías
  $subcategorias = array_filter($terms, function($term) {
    return $term->parent != 0;
  });

  // Limitar a 3 subcategorías
  $subcategorias = array_slice($subcategorias, 0, 3);

  // Verificar que se obtuvieron subcategorías
  if (empty($subcategorias)) {
    return '<p>No hay subcategorías disponibles.</p>';
  }

  // Iniciar el HTML para las cards
  $output = '<div class="categorias-grid">';

  foreach ($subcategorias as $term) {
    // Obtener la miniatura de la subcategoría
    $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
    $thumbnail_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : wc_placeholder_img_src();

    // Obtener la categoría padre
    $parent_name = get_term($term->parent)->name;

    // Obtener la fecha del evento
    $event_date = get_term_meta($term->term_id, 'fecha_de_orden', true);
    // Formatear la fecha como: 12/12/2024
    $event_date = date('d/m/Y', strtotime($event_date));

    // Generar el HTML de la card
    $output .= '
      <div class="category-card">
        <a href="' . esc_url(get_term_link($term)) . '">
          <img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($term->name) . '" class="category-thumbnail">
          <h3 class="category-title">' . esc_html($term->name) . '</h3>
        </a>
        <p class="parent-category"><span>' . esc_html($event_date) . '</span><span>' . esc_html($parent_name) . '</span></p>
      </div>';
  }

  $output .= '</div>';

  return $output;
}
add_shortcode('ultimas_categorias', 'mostrar_ultimas_subcategorias');
