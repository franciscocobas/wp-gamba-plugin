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
    // Ejecuta el código que deseas cuando se sube una imagen
    // Puedes usar $attachment_id para obtener información de la imagen
    $imagen_url = wp_get_attachment_url($attachment_id);

    $adobeXMP =& adobeXMPforWP::get_instance();
    $xmp = $adobeXMP->get_xmp($attachment_id);

    crear_producto_simple($xmp, $attachment_id);

    // Por ejemplo, registrar en el log de errores
    error_log("Se subió una imagen: " . $imagen_url);
  }
}

function crear_producto_simple($xmp, $image_id) {
  try {
    $product = new WC_Product_Simple();

    $title = $xmp['Title'][0];
    $description = $xmp['Description'][0];
    $location = $xmp['Location'];

    // General info
    $product->set_name($title);
    $product->set_description($description);
    $product->set_short_description('Descripción corta del producto');

    // Visibility info
    $product->set_status('publish');  // can be publish, pending, draft, etc
    $product->set_catalog_visibility('visible'); // add the product visibility status

    // Price info
    $product->set_price(350);
    $product->set_regular_price(350);

    // Image info
    $product->set_image_id($image_id); // image id from media library

    // Set product as downloadable
    $product->set_downloadable(true);
    $product->set_virtual(true);

    // debug $imagen_url $title
    $imagen_url = wp_get_attachment_url($image_id);
    error_log("imagen_url: " . $imagen_url);

    // Attach file to downloadable product
    $product->set_downloads(array(array(
      'name' => $title,
      'file' => $imagen_url,
      'download_url' => $imagen_url
    )));

    // Save product
    $product->save();

    update_field('location', $location, $product->get_id());

  } catch (WC_Data_Exception $e) {
    error_log(print_r($e->getMessage(), true));
  } catch (Exception $e) {
    echo $e;
  }
}

/**
 * Función para agregar marca de agua a la imagen subida
 *
 * @param array $metadata Metadatos de la imagen generados por WP
 * @param int $attachment_id ID del attachment (imagen)
 * @return array $metadata Metadatos sin modificar, salvo que la imagen haya sido procesada
 */
function mi_plugin_agregar_marca_agua($metadata, $attachment_id) {
  $file_path = get_attached_file($attachment_id);
  $mime_type = get_post_mime_type($attachment_id);

  // Verificar que sea una imagen PNG o JPEG
  if (strpos($mime_type, 'image/jpeg') !== false || strpos($mime_type, 'image/png') !== false) {

    // Cargar la imagen original en función del tipo
    if (strpos($mime_type, 'image/jpeg') !== false) {
        $image = imagecreatefromjpeg($file_path);
    } elseif (strpos($mime_type, 'image/png') !== false) {
        $image = imagecreatefrompng($file_path);
    } else {
        // Si no es jpg o png, no se aplica la marca de agua
        return $metadata;
    }

    // Ruta de la marca de agua: archivo marca-de-agua.png ubicado en la misma carpeta del plugin
    $watermark_path = plugin_dir_path(__FILE__) . 'marca-de-agua.png';

    if (!file_exists($watermark_path)) {
        // Si no existe el archivo de la marca de agua, no se hace nada
        return $metadata;
    }

    $marca_agua = imagecreatefrompng($watermark_path);

    // Obtener dimensiones de la imagen y la marca de agua
    $image_width = imagesx($image);
    $image_height = imagesy($image);
    $watermark_width = imagesx($marca_agua);
    $watermark_height = imagesy($marca_agua);

    // Calcular posición de la marca de agua (abajo a la derecha con margen)
    $margin = 10;
    $dest_x = $image_width - $watermark_width - $margin;
    $dest_y = $image_height - $watermark_height - $margin;

    // Copiar la marca de agua en la imagen original
    imagecopy($image, $marca_agua, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height);

    // Guardar la imagen modificada
    if (strpos($mime_type, 'image/jpeg') !== false) {
        imagejpeg($image, $file_path, 90);
    } elseif (strpos($mime_type, 'image/png') !== false) {
        imagepng($image, $file_path);
    }

    // Liberar recursos
    imagedestroy($image);
    imagedestroy($marca_agua);
  }

  return $metadata;
}
