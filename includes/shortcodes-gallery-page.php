<?php
// Shortcode para mostrar subcategorías con paginación
function mostrar_solo_subcategorias_con_paginacion($atts) {

  // Enqueue CSS file ONLY on this WooCommerce category archive page
  wp_enqueue_style('gallery-page', plugin_dir_url(__FILE__) . '../assets/css/gallery-page.css');

  // Atributos predeterminados
  $atts = shortcode_atts([
    'per_page' => 12, // Número de categorías por página
  ], $atts);

  // Obtener la página actual desde el parámetro 'paged'
  $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
  $per_page = intval($atts['per_page']);
  $current_page = max(1, get_query_var('paged'));
  $offset = ($paged - 1) * $per_page;

  // Obtener todas las categorías de productos con orden por ACF 'fecha_de_orden'
  $terms = get_terms([
    'taxonomy'   => 'product_cat',
    'meta_key'   => 'fecha_de_orden',
    'orderby'    => 'meta_value_num',
    'order'      => 'DESC',
    'hide_empty' => false,
  ]);

  // Verificar si hubo un error al obtener los términos
  if (is_wp_error($terms)) {
    return '<p>Error al obtener las subcategorías.</p>';
  }

  // Filtrar para obtener solo subcategorías (categorías que tienen un padre)
  $subcategories = array_filter($terms, function ($term) {
    return $term->parent !== 0;
  });

  if (empty($subcategories)) {
    return '<p>No hay subcategorías disponibles.</p>';
  }

  // Calcular la paginación
  $total_pages = ceil(count($subcategories) / $per_page);
  $paged_subcategories = array_slice($subcategories, $offset, $per_page);

  // Iniciar el HTML para las cards
  $output = '<div class="subcategorias-grid">';

  foreach ($paged_subcategories as $term) {
    // Obtener la miniatura de la categoría
    $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
    $thumbnail_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : wc_placeholder_img_src();
    // Obtener la categoría padre
    $parent_name = get_term($term->parent)->name;

    $event_date = get_term_meta($term->term_id, 'fecha_de_orden', true);
    // Format date like this: 12/12/2024
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

  // Agregar la paginación con botones de primera, anterior, siguiente y última página
  if ($total_pages > 1) {
    $output .= '<div class="categorias-pagination">';

    // Botón a la primera página
    if ($current_page > 1) {
      $output .= '<a href="' . esc_url(get_pagenum_link(1)) . '" class="pagination-button">Primera</a>';
    }

    // Botones numerados
    $output .= paginate_links([
      'base'      => get_pagenum_link(1) . '%_%',
      'format'    => 'page/%#%',
      'current'   => $current_page,
      'total'     => $total_pages,
      'prev_text' => 'Anterior',
      'next_text' => 'Siguiente',
    ]);

    // Botón a la última página
    if ($current_page < $total_pages) {
      $output .= '<a href="' . esc_url(get_pagenum_link($total_pages)) . '" class="pagination-button">Última</a>';
    }

    $output .= '</div>';
  }

  return $output;
}
add_shortcode('solo_subcategorias_paginadas', 'mostrar_solo_subcategorias_con_paginacion');
