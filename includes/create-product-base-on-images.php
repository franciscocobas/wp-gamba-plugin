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

// Procesar y subir imágenes a la Biblioteca de Medios y extraer datos
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

        // Aplicar marca de agua antes de subir
        aplicar_marca_agua($file_path, $attachment['post_mime_type']);

        // Extraer datos XMP y crear producto
        $xmp = extraer_datos_xmp($attach_id);
        if (!empty($xmp)) {
          crear_producto_simple($xmp, $attach_id);
        }

        echo '<div class="updated"><p>Imagen subida y producto creado: ' . esc_html($file_name) . '</p></div>';
      } else {
        echo '<div class="error"><p>Error al subir la imagen: ' . esc_html($movefile['error']) . '</p></div>';
      }
    }
  }
}

// Función para extraer datos XMP
function extraer_datos_xmp($attachment_id) {
  $adobeXMP =& adobeXMPforWP::get_instance();
  return $adobeXMP ? $adobeXMP->get_xmp($attachment_id) : [];
}

// Resto del código incluyendo funciones de marca de agua y creación de productos...


// Función para crear el producto de WooCommerce
function crear_producto_simple($xmp, $image_id) {
  try {
    $product = new WC_Product_Simple();

    $title = $xmp['Title'][0] ?? 'Producto sin título';
    $description = $xmp['Description'][0] ?? 'Sin descripción disponible';
    $location = $xmp['Location'] ?? 'Sin ubicación';

    // Extraer la parte central de la descripción
    $description_parts = explode('/', $description);
    $short_description = isset($description_parts[1]) ? trim($description_parts[1]) : $description;

    // Debug: registrar los datos XMP que se están utilizando
    error_log("Debug: Título: $title | Descripción: $description | Descripción corta: $short_description | Ubicación: $location");

    // Configuración del producto
    $product->set_name($title);
    $product->set_description($description);
    $product->set_short_description($short_description);

    $product->set_status('publish');  // Publicar
    $product->set_catalog_visibility('visible'); // Visible en el catálogo

    $product->set_price(350);
    $product->set_regular_price(350);

    // Asociar la imagen al producto
    $product->set_image_id($image_id);

    // Producto descargable
    $imagen_url = wp_get_attachment_url($image_id);
    $product->set_downloadable(true);
    $product->set_virtual(true);
    $product->set_downloads(array(array(
      'name' => $title,
      'file' => $imagen_url,
    )));

    // Guardar el producto
    $product->save();

    // Agregar metadatos personalizados al producto
    update_field('location', $location, $product->get_id());

    // Debug: registrar que el producto fue creado correctamente
    error_log("Debug: Producto creado con ID: " . $product->get_id());

  } catch (WC_Data_Exception $e) {
    error_log("Error: Excepción de WooCommerce: " . $e->getMessage());
  } catch (Exception $e) {
    error_log("Error: Excepción general: " . $e->getMessage());
  }
}

// Función para aplicar la marca de agua sin redimensionar
function aplicar_marca_agua($imagen_path, $mime_type) {
  // Ruta del archivo de la marca de agua (dentro del plugin)
  $marca_agua_path = plugin_dir_path(__FILE__) . '../marca-de-agua.png';

  if (!file_exists($marca_agua_path)) {
    error_log("La marca de agua no se encontró en la ruta especificada.");
    return;
  }

  // Cargar la imagen original
  if ($mime_type === 'image/jpeg') {
    $image = imagecreatefromjpeg($imagen_path);
  } elseif ($mime_type === 'image/png') {
    $image = imagecreatefrompng($imagen_path);
  } else {
    error_log("El tipo de archivo no es compatible para aplicar marca de agua.");
    return;
  }

  // Cargar la marca de agua
  $marca_agua = imagecreatefrompng($marca_agua_path);

  // Obtener dimensiones de la imagen y de la marca de agua
  $image_width = imagesx($image);
  $image_height = imagesy($image);
  $watermark_width = imagesx($marca_agua);
  $watermark_height = imagesy($marca_agua);

  // Calcular posición de la marca de agua (abajo a la derecha con margen)
  $margin = 10;
  $dest_x = $image_width - $watermark_width - $margin;
  $dest_y = $image_height - $watermark_height - $margin;

  // Fusionar la marca de agua con la imagen
  imagecopy($image, $marca_agua, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height);

  // Guardar la imagen modificada
  if ($mime_type === 'image/jpeg') {
    imagejpeg($image, $imagen_path, 90);
  } elseif ($mime_type === 'image/png') {
    imagepng($image, $imagen_path);
  }

  // Liberar memoria
  imagedestroy($image);
  imagedestroy($marca_agua);

  error_log("Marca de agua aplicada a: " . $imagen_path);
}
