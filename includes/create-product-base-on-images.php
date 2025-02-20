<?php

// Cargar hoja de estilos específica para la página del plugin
function cpbf_cargar_estilos_admin($hook) {
  if ($hook === 'product_page_crear-productos-por-fotos') {
    wp_enqueue_style(
      'cpbf-estilos',
      plugin_dir_url(__FILE__) . '../assets/css/upload-products-admin.css',
      [],
      '1.0.0'
    );
  }
}
add_action('admin_enqueue_scripts', 'cpbf_cargar_estilos_admin');

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

// Contenido de la página con campo para descripción corta y select de categorías padre de WooCommerce
function cpbf_pagina_contenido() {
  echo '<div class="card-admin">';
  echo '<h1>Crear Productos por Fotos</h1>';
  echo '<form method="post" enctype="multipart/form-data" id="images-products-form">';

  echo '<div class="field-group">';
  echo '<label for="categoria">Nombre del evento: <span class="mark">*</span></label>';
  echo '<input type="text" name="categoria_woocommerce" id="categoria" placeholder="Ingrese el nombre de la categoría" required>';
  echo '</div>';

  echo '<div class="field-group">';
  echo '<label for="descripcion_corta">Descripción corta (opcional):</label>';
  echo '<textarea name="descripcion_corta" id="descripcion_corta" placeholder="Ingrese una descripción corta"></textarea>';
  echo '</div>';

  // Agregar select con solo categorías padre de WooCommerce
  echo '<div class="field-group">';
  echo '<label for="categoria_existente">Seleccionar una categoría padre: <span class="mark">*</span></label>';
  echo '<select name="categoria_existente" id="categoria_existente" required>';
  echo '<option value="">-- Seleccione la categoría --</option>';

  $categorias = get_terms([
    'taxonomy' => 'product_cat',
    'hide_empty' => false,
    'parent' => 0
  ]);

  foreach ($categorias as $categoria) {
    echo '<option value="' . esc_attr($categoria->term_id) . '">' . esc_html($categoria->name) . '</option>';
  }

  echo '</select>';
  echo '</div>';
  echo '<div class="field-group">';
  echo '<label for="product_images">Seleccionar las imágenes a subir: <span class="mark">*</span></label>';
  echo '<input id="proudct_images" type="file" name="product_images[]" multiple accept="image/*">';
  echo '</div>';
  echo '<input type="submit" name="upload_images" value="Subir Imágenes y crear productos" class="button button-primary">';
  echo '</form>';
  echo '<p><strong>Nota:</strong> Las imágenes subidas se guardarán en la Biblioteca de Medios y se crearán productos de WooCommerce con los datos XMP extraídos.</p>';
  echo '<p class="underline"><strong>⚠️ Este proceso puede tardar unos minutos.</strong></p>';
  echo '</div>'; // .card

  if (isset($_POST['upload_images']) && !empty($_FILES['product_images'])) {
    cpbf_procesar_subida_imagenes();
  }
}

// Procesar y subir imágenes a la Biblioteca de Medios y extraer datos
function cpbf_procesar_subida_imagenes() {
  // Obtener el valor del input 'categoria_woocommerce'
  $categoria_nombre = $_POST['categoria_woocommerce'] ?? '';
  $categoria_creada = false;

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

        // Extraer datos XMP y crear producto
        $xmp = extraer_datos_xmp($attach_id);

        // Aplicar marca de agua antes de subir
        aplicar_marca_agua($file_path, $attachment['post_mime_type']);

        // Crear la categoría solo para la primera imagen subida
        if (!$categoria_creada) {
          crear_categoria_woocommerce($categoria_nombre, $xmp);
          $categoria_creada = true;
        }

        if (!empty($xmp)) {
          crear_producto_simple($xmp, $attach_id, $categoria_nombre);
        }

        echo '<div class="updated"><p>Imagen subida y producto creado: ' . esc_html($file_name) . '</p></div>';
      } else {
        echo '<div class="error"><p>Error al subir la imagen: ' . esc_html($movefile['error']) . '</p></div>';
      }
    }
  }
}

// Función para extraer datos XMP con debug
function extraer_datos_xmp($attachment_id) {
  $adobeXMP =& adobeXMPforWP::get_instance();
  $xmp_datos = $adobeXMP ? $adobeXMP->get_xmp($attachment_id) : [];

  // Debug para verificar si se reciben los datos XMP
  if (!empty($xmp_datos)) {
    error_log("[DEBUG] XMP obtenidos: " . print_r($xmp_datos, true));
  } else {
    error_log("[DEBUG] No se encontraron datos XMP para el attachment ID: $attachment_id");
  }

  return $xmp_datos;
}
// Crear producto de WooCommerce y asignar la categoría creada
function crear_producto_simple($xmp, $image_id, $categoria_nombre) {
  try {
    $product = new WC_Product_Simple();

    $title = $xmp['Title'][0] ?? 'Producto sin título';
    $description = $xmp['Description'][0] ?? 'Sin descripción disponible';
    $location = $xmp['Location'] ?? 'Sin ubicación';

    // Extraer la parte central de la descripción
    $description_parts = explode('/', $description);
    $short_description = isset($description_parts[1]) ? trim($description_parts[1]) : $description;

    // Configuración del producto
    $product->set_name($title);
    $product->set_description($description);
    $product->set_short_description($short_description);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_price(350);
    $product->set_regular_price(350);
    $product->set_image_id($image_id);
    $product->set_downloadable(true);
    $product->set_virtual(true);
    $product->set_downloads([
      [
        'name' => $title,
        'file' => wp_get_attachment_url($image_id),
      ]
    ]);

    // Obtener el término de la categoría creada
    $term = get_term_by('name', $categoria_nombre, 'product_cat');
    if ($term && !is_wp_error($term)) {
      $product->set_category_ids([$term->term_id]);
    }

    // Guardar el producto
    $product->save();

    // Agregar metadatos personalizados al producto
    update_field('location', $location, $product->get_id());

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

// Crear categoría de WooCommerce con campos personalizados de ACF usando XMP
function crear_categoria_woocommerce($categoria_nombre, $xmp_datos) {
  // Usar la descripción corta ingresada y la categoría seleccionada en el formulario
  $descripcion = $_POST['descripcion_corta'] ?? '';
  $categoria_padre = $_POST['categoria_existente'] ?? 0;

  if (!term_exists($categoria_nombre, 'product_cat')) {
    $term = wp_insert_term(
      $categoria_nombre,
      'product_cat',
      [
        'description' => $descripcion,
        'slug' => sanitize_title($categoria_nombre),
        'parent' => intval($categoria_padre)
      ]
    );

    if (!is_wp_error($term)) {
      $term_id = $term['term_id'];

      // Obtener valores de XMP con defaults si faltan
      $fecha_raw = $xmp_datos['Creation Date'] ?? null;
      $fecha = $fecha_raw ? date_i18n('j \\d\\e F \\d\\e Y', strtotime($fecha_raw)) : 'Fecha no disponible';
      $lugar = $xmp_datos['Location'] ?? 'Lugar no disponible';
      $fotografo = $xmp_datos['Credit'] ?? 'Autor desconocido';

      // Agregar campos personalizados de ACF con valores de XMP
      update_field('fecha', $fecha, 'term_' . $term_id);
      update_field('lugar', $lugar, 'term_' . $term_id);
      update_field('fotografos', $fotografo, 'term_' . $term_id);
      update_field('descripcion_corta', $descripcion, 'term_' . $term_id);
    }
  }
}
