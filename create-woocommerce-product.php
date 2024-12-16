<?php
/*
Plugin Name: Mi Plugin de Subida de Imágenes
Description: Ejecuta una función al subir una imagen a WordPress.
Version: 1.0
Author: Francisco Cobas
*/

// Hook que se ejecuta cuando se sube un archivo a la biblioteca de medios
add_action('add_attachment', 'mi_funcion_subida_imagen');

function mi_funcion_subida_imagen($attachment_id) {
  // Verificar que el archivo sea una imagen
  $file_type = get_post_mime_type($attachment_id);
  if (strpos($file_type, 'image') !== false) {
    // Obtener la URL y la ruta del archivo
    $imagen_url = wp_get_attachment_url($attachment_id);
    $imagen_path = get_attached_file($attachment_id);

    // Debug: registrar la URL y la ruta del archivo
    error_log("Debug: Subiendo imagen - URL: $imagen_url | Path: $imagen_path");

    // Obtener metadatos XMP usando la instancia de adobeXMPforWP
    $adobeXMP =& adobeXMPforWP::get_instance();
    $xmp = $adobeXMP->get_xmp($attachment_id);

    // Aplicar marca de agua
    aplicar_marca_agua($imagen_path, $file_type);

    // Debug: registrar si la instancia de adobeXMPforWP fue obtenida
    if ($adobeXMP) {
      error_log("Debug: Instancia de adobeXMPforWP obtenida correctamente.");
    } else {
      error_log("Error: No se pudo obtener la instancia de adobeXMPforWP.");
    }

    // Debug: registrar el contenido de $xmp
    if (!empty($xmp)) {
      error_log("Debug: XMP Metadata obtenida: " . print_r($xmp, true));
    } else {
      error_log("Error: No se encontraron datos XMP en la imagen con ID $attachment_id.");
    }

    // Si se obtienen datos XMP, crear el producto
    if (!empty($xmp)) {
      crear_producto_simple($xmp, $attachment_id);
    } else {
      error_log("Error: No se pudo crear el producto porque los metadatos XMP están vacíos.");
    }
  }
}

// Función para crear el producto de WooCommerce
function crear_producto_simple($xmp, $image_id) {
  try {
    $product = new WC_Product_Simple();

    $title = $xmp['Title'][0] ?? 'Producto sin título';
    $description = $xmp['Description'][0] ?? 'Sin descripción disponible';
    $location = $xmp['Location'] ?? 'Sin ubicación';

    // Debug: registrar los datos XMP que se están utilizando
    error_log("Debug: Título: $title | Descripción: $description | Ubicación: $location");

    // Configuración del producto
    $product->set_name($title);
    $product->set_description($description);
    $product->set_short_description('Descripción corta del producto');

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

// Función para aplicar marca de agua
function aplicar_marca_agua($imagen_path, $mime_type) {
  // Ruta del archivo de la marca de agua (dentro del plugin)
  $marca_agua_path = plugin_dir_path(__FILE__) . 'marca-de-agua.png';

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
    // Si no es JPG o PNG, no se aplica la marca de agua
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
