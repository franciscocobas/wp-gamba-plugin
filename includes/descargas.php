<?php
/**
 * Experiencia de descarga de las fotos compradas.
 *
 * - Reemplaza las plantillas de WooCommerce de la lista de descargas (página de
 *   pedido recibido, Mi cuenta y mails) por una versión con miniatura, botón grande
 *   e instrucciones para encontrar la foto en el celular.
 * - Muestra el estado del pedido y la miniatura de cada foto en la página del pedido.
 * - Cambia el texto de la pantalla que pide verificar el email del pedido.
 * - Avisa en el checkout cómo se reciben las fotos.
 * - Le da un nombre legible al archivo descargado.
 */

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Plantillas de WooCommerce que se sobrescriben desde gamba/templates/woocommerce/.
 */
function gamba_descargas_plantillas() {
  return [
    'order/order-downloads.php',
    'emails/email-downloads.php',
    'emails/plain/email-downloads.php',
    'emails/customer-completed-order.php',
    'emails/plain/customer-completed-order.php',
    'checkout/form-verify-email.php',
  ];
}

function gamba_descargas_locate_template($template, $template_name, $template_path) {
  if (in_array($template_name, gamba_descargas_plantillas(), true)) {
    $plantilla_gamba = plugin_dir_path(__DIR__) . 'templates/woocommerce/' . $template_name;
    if (file_exists($plantilla_gamba)) {
      return $plantilla_gamba;
    }
  }
  return $template;
}
add_filter('woocommerce_locate_template', 'gamba_descargas_locate_template', 10, 3);

/**
 * URL de la miniatura (con marca de agua) del producto de una descarga.
 */
function gamba_descargas_miniatura_url($product_id, $size = 'medium') {
  $url = get_the_post_thumbnail_url($product_id, $size);
  return $url ? $url : wc_placeholder_img_src($size);
}

/**
 * Texto de ayuda para encontrar la foto descargada en cada dispositivo.
 */
function gamba_descargas_ayuda() {
  return [
    'iPhone / iPad' => 'tocá <strong>Descargar foto</strong> y en el menú que se abre elegí <strong>Guardar imagen</strong>: la foto queda en la app <strong>Fotos</strong>.',
    'Android'       => 'la foto queda en la carpeta <strong>Descargas</strong> y también aparece en la <strong>Galería</strong>.',
    'Computadora'   => 'la foto queda en la carpeta <strong>Descargas</strong>.',
  ];
}

/**
 * Nombre con el que se guarda el archivo de una descarga del pedido.
 */
function gamba_descargas_nombre_de_descarga($download) {
  $original = isset($download['file']['file']) ? wp_basename(wp_parse_url($download['file']['file'], PHP_URL_PATH)) : '';
  return gamba_descargas_nombre_archivo($original ? $original : 'foto.jpg', $download['product_id']);
}

/**
 * Textos del mail de pedido completado. Los de WooCommerce ("¡Te van a llegar cosas
 * bonitas!", "Hemos terminado de procesar tu pedido") son para envíos físicos.
 */
function gamba_descargas_cantidad_de_fotos($order) {
  return $order instanceof WC_Order ? count($order->get_downloadable_items()) : 0;
}

function gamba_descargas_texto_intro_mail($order) {
  $una = gamba_descargas_cantidad_de_fotos($order) === 1;
  return '¡Gracias por tu compra! Tu pago fue aprobado y ya podés descargar ' . ($una ? 'tu foto.' : 'tus fotos.');
}

/**
 * Indica si un ajuste del mail se cambió a mano en WooCommerce > Ajustes > Correos
 * electrónicos. Se mira la opción guardada porque $email->get_option() devuelve el
 * texto por defecto de WooCommerce cuando el campo está vacío.
 */
function gamba_descargas_ajuste_mail_personalizado($email, $clave) {
  if (!$email instanceof WC_Email) {
    return false;
  }
  $guardado = get_option($email->get_option_key(), []);
  return is_array($guardado) && !empty($guardado[$clave]);
}

/**
 * Asunto y encabezado del mail de pedido completado, salvo que se hayan cambiado a
 * mano en WooCommerce > Ajustes > Correos electrónicos.
 */
function gamba_descargas_asunto_mail_completado($subject, $order, $email) {
  if (!gamba_descargas_pedido_tiene_fotos($order) || gamba_descargas_ajuste_mail_personalizado($email, 'subject')) {
    return $subject;
  }
  return gamba_descargas_cantidad_de_fotos($order) === 1
    ? 'Tu foto de Gamba está lista para descargar'
    : 'Tus fotos de Gamba están listas para descargar';
}
add_filter('woocommerce_email_subject_customer_completed_order', 'gamba_descargas_asunto_mail_completado', 10, 3);

function gamba_descargas_encabezado_mail_completado($heading, $order, $email) {
  if (!gamba_descargas_pedido_tiene_fotos($order) || gamba_descargas_ajuste_mail_personalizado($email, 'heading')) {
    return $heading;
  }
  return gamba_descargas_cantidad_de_fotos($order) === 1 ? '¡Tu foto está lista!' : '¡Tus fotos están listas!';
}
add_filter('woocommerce_email_heading_customer_completed_order', 'gamba_descargas_encabezado_mail_completado', 10, 3);

/**
 * Indica si el pedido tiene fotos para descargar.
 */
function gamba_descargas_pedido_tiene_fotos($order) {
  return $order instanceof WC_Order && $order->has_downloadable_item();
}

/**
 * Mensaje de la página de pedido recibido.
 *
 * Cuando la página pide verificar el email, WooCommerce pasa $order = false, así que
 * en ese caso no se muestra ningún dato del pedido.
 */
function gamba_descargas_texto_pedido_recibido($message, $order) {
  if (!gamba_descargas_pedido_tiene_fotos($order)) {
    return $message;
  }

  $email = '<strong class="gamba-estado__email">' . esc_html($order->get_billing_email()) . '</strong>';

  // El mensaje va dentro de un <p>, por eso se arma con <span>
  if ($order->is_download_permitted()) {
    $estado = 'aprobado';
    $titulo = '¡Gracias por tu compra!';
    $texto  = 'Tu pago fue aprobado y tus fotos ya están listas: las podés descargar acá abajo. '
      . 'También te enviamos los links de descarga a ' . $email . '.';
  } else {
    $estado = 'pendiente';
    $titulo = '¡Gracias por tu compra! Estamos esperando la confirmación del pago';
    $texto  = 'Cuando se acredite, te enviamos a ' . $email . ' los links para descargar tus fotos.';
  }

  return '<span class="gamba-estado gamba-estado--' . $estado . '">'
    . '<span class="gamba-estado__titulo">' . esc_html($titulo) . '</span>'
    . '<span class="gamba-estado__texto">' . $texto . '</span>'
    . '</span>';
}
add_filter('woocommerce_thankyou_order_received_text', 'gamba_descargas_texto_pedido_recibido', 10, 2);

/**
 * Indica si se está mostrando la página de un pedido (pedido recibido o ver pedido).
 */
function gamba_descargas_es_pagina_pedido() {
  return is_wc_endpoint_url('order-received') || is_wc_endpoint_url('view-order');
}

/**
 * Miniatura de la foto en la tabla de detalles del pedido.
 *
 * Los mails usan el mismo filtro pero con $is_visible = false, así que no se ven
 * afectados.
 */
function gamba_descargas_miniatura_en_pedido($item_name, $item, $is_visible = false) {
  if (!$is_visible || !gamba_descargas_es_pagina_pedido() || !is_callable([$item, 'get_product_id'])) {
    return $item_name;
  }

  $miniatura = '<img class="gamba-pedido-item__miniatura" src="' . esc_url(gamba_descargas_miniatura_url($item->get_product_id(), 'thumbnail')) . '" alt="" loading="lazy" />';

  return '<span class="gamba-pedido-item">' . $miniatura . '<span class="gamba-pedido-item__nombre">' . $item_name . '</span></span>';
}
add_filter('woocommerce_order_item_name', 'gamba_descargas_miniatura_en_pedido', 10, 3);

/**
 * Cada foto se compra una sola vez, así que el "× 1" no aporta.
 */
function gamba_descargas_ocultar_cantidad_uno($quantity_html, $item) {
  if (gamba_descargas_es_pagina_pedido() && (int) $item->get_quantity() === 1) {
    return '';
  }
  return $quantity_html;
}
add_filter('woocommerce_order_item_quantity_html', 'gamba_descargas_ocultar_cantidad_uno', 10, 2);

/**
 * Aviso en el checkout sobre cómo se reciben las fotos.
 */
function gamba_descargas_aviso_checkout() {
  if (!WC()->cart || WC()->cart->is_empty()) {
    return;
  }
  echo '<div class="gamba-aviso-entrega">';
  echo '<strong>¿Cómo recibo mis fotos?</strong> ';
  echo 'Apenas se apruebe el pago vas a poder descargarlas en la página siguiente, en tamaño original y sin marca de agua. ';
  echo 'También te enviamos los links al email que ingreses, así que revisá que esté bien escrito.';
  echo '</div>';
}
add_action('woocommerce_review_order_before_payment', 'gamba_descargas_aviso_checkout');

/**
 * Nombre del archivo descargado: gamba-<evento>-<id>.<extensión>
 * en lugar del uniqid() con el que se guarda el original.
 */
function gamba_descargas_nombre_archivo($filename, $product_id) {
  $extension = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
  if (!$product_id || !$extension) {
    return $filename;
  }

  $partes = ['gamba'];

  // Se usa el evento (la subcategoría más profunda) del producto
  $categorias = get_the_terms($product_id, 'product_cat');
  if (!empty($categorias) && !is_wp_error($categorias)) {
    usort($categorias, function ($a, $b) {
      return count(get_ancestors($b->term_id, 'product_cat')) - count(get_ancestors($a->term_id, 'product_cat'));
    });
    $slug = sanitize_title($categorias[0]->slug);
    if ($slug) {
      $partes[] = trim(substr($slug, 0, 60), '-');
    }
  }

  $partes[] = $product_id;

  return implode('-', $partes) . '.' . $extension;
}
add_filter('woocommerce_file_download_filename', 'gamba_descargas_nombre_archivo', 10, 2);

/**
 * Estilos y JS de la lista de descargas y del aviso del checkout.
 */
function gamba_descargas_enqueue_assets() {
  $es_mis_fotos = function_exists('gamba_mis_fotos_es_pagina') && gamba_mis_fotos_es_pagina();

  if (is_checkout() || is_account_page() || $es_mis_fotos) {
    wp_enqueue_style(
      'gamba-descargas',
      plugin_dir_url(__DIR__) . 'assets/css/descargas.css',
      [],
      '1.5'
    );
    wp_enqueue_script(
      'gamba-descargas',
      plugin_dir_url(__DIR__) . 'assets/js/descargas.js',
      [],
      '1.0',
      true
    );
  }

  if (gamba_descargas_es_pagina_pedido()) {
    wp_enqueue_style(
      'gamba-pedido',
      plugin_dir_url(__DIR__) . 'assets/css/pedido.css',
      ['gamba-descargas'],
      '1.2'
    );
  }
}
add_action('wp_enqueue_scripts', 'gamba_descargas_enqueue_assets');
