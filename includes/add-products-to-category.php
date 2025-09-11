<?php

add_action('admin_enqueue_scripts', function($hook) {
  // Verifica si estamos en la página específica del administrador
  if (isset($_GET['page']) && $_GET['page'] === 'gamba-agregar-productos-evento') {
    wp_enqueue_style(
      'gamba-custom-style',
      plugin_dir_url(__FILE__) . '../assets/css/add-product-page.css',
      [],
      '1.0.0'
    );
  }
});

/**
 * Verifica si una categoría de productos tiene un precio personalizado
 * distinto al precio por defecto configurado en las opciones del plugin.
 *
 * Esta función obtiene el primer producto asociado a la categoría (`product_cat`)
 * y compara su precio con el precio por defecto establecido mediante la opción
 * `mi_plugin_precio_1_foto`.
 *
 * @param int $categoria_id El ID de la categoría de producto (product_cat).
 * @return float|null Devuelve el precio personalizado si es distinto al valor por defecto.
 *                    Si el precio coincide con el valor por defecto o no se encuentra
 *                    un producto válido, devuelve null.
 */
function cpbf_obtener_precio_personalizado_categoria($categoria_id) {
  $precio_defecto = floatval(get_option('mi_plugin_precio_1_foto', 350));

  $args = [
    'post_type'      => 'product',
    'posts_per_page' => 1,
    'tax_query'      => [
      [
        'taxonomy' => 'product_cat',
        'field'    => 'term_id',
        'terms'    => intval($categoria_id),
      ]
    ],
    'meta_query'     => [
      [
        'key'     => '_price',
        'compare' => 'EXISTS',
      ]
    ]
  ];

  $query = new WP_Query($args);

  if ($query->have_posts()) {
    $query->the_post();
    $product = wc_get_product(get_the_ID());

    if ($product) {
      $precio_producto = floatval($product->get_price());
      wp_reset_postdata();

      return ($precio_producto !== $precio_defecto) ? $precio_producto : null;
    }
  }

  wp_reset_postdata();
  return null;
}

// Función para la página "Agregar productos a evento"
function gamba_pagina_agregar_productos_evento() {
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['categoria'])) {
    $categoria_id = intval($_POST['categoria']);
    $categoria_obj = get_term($categoria_id, 'product_cat');
    $categoria_nombre = $categoria_obj ? $categoria_obj->name : '';

    if (!empty($categoria_nombre)) {
      $precio_personalizado = cpbf_obtener_precio_personalizado_categoria($categoria_id);

      // Llama a la función pasando el precio personalizado si existe
      cpbf_procesar_subida_imagenes($categoria_nombre, $precio_personalizado);
    }
  }
  ?>
  <div class="wrap">
    <div class="card-admin">
      <h1>Agregar productos a evento</h1>
      <form method="post" enctype="multipart/form-data" id="agregar-fotos-form">
        <div class="field-group">
          <label for="product_images">Subir imágenes <span class="mark">*</span></label>
          <input type="file" name="product_images[]" id="product_images" multiple accept="image/*" required>
        </div>
        <div class="field-group">
          <label for="categoria">Seleccionar evento <span class="mark">*</span></label>
          <select name="categoria" id="categoria" required>
            <option value="">Selecciona el evento</option>
            <?php
              $args = array(
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
              );
              $categorias = get_terms($args);

              foreach ($categorias as $categoria) {
                if ($categoria->parent != 0) { // Solo categorías con padre
                  echo '<option value="' . esc_attr($categoria->term_id) . '">' . esc_html($categoria->name) . '</option>';
                }
              }
            ?>
          </select>
        </div>
        <?php submit_button('Agregar productos'); ?>
      </form>
    </div>
  </div>
  <?php
}
