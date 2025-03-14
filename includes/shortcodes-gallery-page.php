<?php
// Shortcode para mostrar solo subcategorías con paginación
function mostrar_solo_subcategorias_con_paginacion($atts) {
  // Enqueue CSS file ONLY on this WooCommerce category archive page
  wp_enqueue_style('gallery-page', plugin_dir_url(__FILE__) . '../assets/css/gallery-page.css');

  // Atributos predeterminados
  $atts = shortcode_atts([
    'per_page' => 12, // Número de categorías por página
  ], $atts);

  // Obtener la página actual correctamente
  $paged = max(1, get_query_var('paged', 1));
  $per_page = intval($atts['per_page']);

  // Obtener solo las subcategorías de 'product_cat'
  $args = [
    'taxonomy'   => 'product_cat',
    'meta_key'   => 'fecha_de_orden',
    'orderby'    => 'meta_value_num',
    'order'      => 'DESC',
    'hide_empty' => false,
  ];

  $all_terms = get_terms($args);

  // Filtrar para obtener solo subcategorías (categorías que tienen un padre)
  $subcategories = array_filter($all_terms, function ($term) {
    return $term->parent !== 0;
  });

  // Contar total de subcategorías y paginar manualmente
  $total_subcategories = count($subcategories);
  $total_pages = ceil($total_subcategories / $per_page);
  $paged_subcategories = array_slice($subcategories, ($paged - 1) * $per_page, $per_page);

  if (empty($paged_subcategories)) {
    return '<p>No hay subcategorías disponibles.</p>';
  }

  // Iniciar el HTML para las cards
  $output = '<div class="subcategorias-grid">';

  foreach ($paged_subcategories as $term) {
    // Obtener la miniatura de la categoría
    $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
    $thumbnail_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : wc_placeholder_img_src();

    // Obtener la categoría padre
    $parent_name = get_term($term->parent)->name;

    // Obtener la fecha
    $event_date = get_term_meta($term->term_id, 'fecha_de_orden', true);
    $event_date = date('d/m/Y', strtotime($event_date));

    // Generar el HTML de la card
    $output .= '
      <div class="subcategory-card">
        <a href="' . esc_url(get_term_link($term)) . '">
          <img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($term->name) . '" class="subcategory-thumbnail">
          <h3 class="subcategory-title">' . esc_html($term->name) . '</h3>
        </a>
        <p class="parent-category"><span>' . esc_html($event_date) . '</span><span>' . esc_html($parent_name) . '</span></p>
      </div>';
  }

  $output .= '</div>';

  // Agregar la paginación
  if ($total_pages > 1) {
    $output .= '<div class="categorias-pagination">';

    // Botón a la primera página
    if ($paged > 1) {
      $output .= '<a class="first-page" href="' . esc_url(get_pagenum_link(1)) . '" class="pagination-button">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25" fill="none">
          <path d="M11 17.8799L6 12.8799L11 7.87988" stroke="#362154" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M18 17.8799L13 12.8799L18 7.87988" stroke="#362154" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </a>';
    }

    // Botones numerados
    $output .= paginate_links([
      'base'      => get_pagenum_link(1) . '%_%',
      'format'    => 'page/%#%',
      'current'   => $paged,
      'total'     => $total_pages,
      'prev_text' => '
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25" fill="none">
          <path d="M15 18.8799L9 12.8799L15 6.87988" stroke="#362154" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      ',
      'next_text' => '
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25" fill="none">
          <path d="M9 18.8799L15 12.8799L9 6.87988" stroke="#362154" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      ',
    ]);

    // Botón a la última página
    if ($paged < $total_pages) {
      $output .= '<a class="last-page" href="' . esc_url(get_pagenum_link($total_pages)) . '" class="pagination-button">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25" fill="none">
          <path d="M13 17.8799L18 12.8799L13 7.87988" stroke="#362154" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M6 17.8799L11 12.8799L6 7.87988" stroke="#362154" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </a>';
    }

    $output .= '</div>';
  }

  return $output;
}
add_shortcode('solo_subcategorias_paginadas', 'mostrar_solo_subcategorias_con_paginacion');
