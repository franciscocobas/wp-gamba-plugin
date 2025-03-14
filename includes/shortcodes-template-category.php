<?php

function mostrar_subcategorias_en_cards() {
  if (!is_tax('product_cat')) return ''; // Verificar si estamos en una categoría de productos

  $term = get_queried_object(); // Obtener la categoría actual
  if (!$term) return '';

  wp_enqueue_style('gallery-page', plugin_dir_url(__FILE__) . '../assets/css/gallery-page.css');

  $subcategorias = get_terms([
    'taxonomy'   => 'product_cat',
    'meta_key'   => 'fecha_de_orden',
    'orderby'    => 'meta_value_num',
    'order'      => 'DESC',
    'parent'     => $term->term_id,
    'hide_empty' => false
  ]);

  if (empty($subcategorias)) return '<p>No hay subcategorías disponibles.</p>';

  ob_start(); ?>

  <div class="subcategorias-grid">
    <?php foreach ($subcategorias as $subcat):
      $thumbnail_id = get_term_meta($subcat->term_id, 'thumbnail_id', true);
      $image_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : wc_placeholder_img_src();
      $subcat_link = get_term_link($subcat);

      // Obtener la fecha
      $event_date = get_term_meta($subcat->term_id, 'fecha_de_orden', true);
      $event_date = date('d/m/Y', strtotime($event_date));

    ?>
      <div class="subcategory-card">
        <a href="<?php echo esc_url($subcat_link); ?>">
          <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($subcat->name); ?>">
          <h3><?php echo esc_html($subcat->name); ?></h3>
        </a>
        <p class="parent-category"><span><?php echo esc_html($event_date) ?></span></p>
      </div>
    <?php endforeach; ?>
  </div>

  <?php
  return ob_get_clean();
}

add_shortcode('subcategorias_cards', 'mostrar_subcategorias_en_cards');
