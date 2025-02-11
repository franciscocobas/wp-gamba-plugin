<?php

// Evitar acceso directo
if (!defined('ABSPATH')) {
  exit;
}

// Agregar el submenú dentro de Productos en WooCommerce
function cpbf_agregar_submenu() {
  add_submenu_page(
    'edit.php?post_type=product', // Parent slug: Productos de WooCommerce
    'Crear productos por fotos',  // Título de la página
    'Crear productos por fotos',  // Título del menú
    'manage_woocommerce',         // Capacidad requerida
    'crear-productos-por-fotos',  // Slug de la página
    'cpbf_pagina_contenido'       // Función de contenido
  );
}
add_action('admin_menu', 'cpbf_agregar_submenu');

// Contenido de la página
function cpbf_pagina_contenido() {
  echo '<div class="wrap">';
  echo '<h1>Crear Productos por Fotos</h1>';
  echo '<p>Aquí se podrá subir imágenes para generar productos automáticamente.</p>';
  echo '<form method="post" enctype="multipart/form-data">';
  echo '<input type="file" name="product_images[]" multiple accept="image/*">';
  echo '<br><br>';
  echo '<input type="submit" name="upload_images" value="Subir Imágenes" class="button button-primary">';
  echo '</form>';
  echo '</div>';

  if (isset($_POST['upload_images']) && !empty($_FILES['product_images'])) {
    cpbf_procesar_subida_imagenes();
  }
}

// Procesar y subir imágenes a la Biblioteca de Medios y crear productos
function cpbf_procesar_subida_imagenes() {
  if (!function_exists('wp_handle_upload')) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
  }
  if (!function_exists('wp_insert_attachment')) {
    require_once ABSPATH . 'wp-admin/includes/image.php';
  }

  foreach ($_FILES['product_images']['name'] as $key => $value) {
    if ($_FILES['product_images']['error'][$key] === UPLOAD_ERR_OK) {
      $file = [
        'name'     => $_FILES['product_images']['name'][$key],
        'type'     => $_FILES['product_images']['type'][$key],
        'tmp_name' => $_FILES['product_images']['tmp_name'][$key],
        'error'    => $_FILES['product_images']['error'][$key],
        'size'     => $_FILES['product_images']['size'][$key],
      ];
      $upload_overrides = ['test_form' => false];
      $movefile = wp_handle_upload($file, $upload_overrides);

      if ($movefile && !isset($movefile['error'])) {
        $file_path = $movefile['file'];
        $file_name = basename($file_path);
        $file_type = wp_check_filetype($file_name);

        $attachment = [
          'guid'           => $movefile['url'],
          'post_mime_type' => $file_type['type'],
          'post_title'     => preg_replace('/\.[^.]+$/', '', $file_name),
          'post_content'   => '',
          'post_status'    => 'inherit',
        ];

        $attach_id = wp_insert_attachment($attachment, $file_path);
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Crear producto de WooCommerce con la imagen subida
        procesar_imagen($attach_id);

        echo '<div class="updated"><p>Imagen subida y producto creado: ' . esc_html($file_name) . '</p></div>';
      } else {
        echo '<div class="error"><p>Error al subir la imagen: ' . esc_html($movefile['error']) . '</p></div>';
      }
    }
  }
}
