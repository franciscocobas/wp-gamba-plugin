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

// Función para la página "Agregar productos a evento"
function gamba_pagina_agregar_productos_evento() {
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['categoria'])) {
    $categoria_id = intval($_POST['categoria']);
    $categoria_obj = get_term($categoria_id, 'product_cat');
    $categoria_nombre = $categoria_obj ? $categoria_obj->name : '';

    if (!empty($categoria_nombre)) {
      cpbf_procesar_subida_imagenes($categoria_nombre);
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
