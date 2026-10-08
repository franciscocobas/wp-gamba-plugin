<?php
/**
 * Página "Mis fotos" (/mis-fotos/?pedido=<clave>&uid=<hash>).
 *
 * Es la página a la que lleva el botón del mail. Muestra las fotos del pedido para
 * descargarlas sin pedir el email: el link se valida igual que los links de
 * descarga de WooCommerce, con la clave del pedido y el hash sha256 del email.
 *
 * Es una página de WordPress con el shortcode [gamba_mis_fotos] y la plantilla
 * "Elementor Ancho completo", para que Elementor le aplique el kit y cargue sus
 * scripts (header, carrito, etc.) como en el resto del sitio. Si la página no
 * existe, se crea sola.
 */

if (!defined('ABSPATH')) {
  exit;
}

/**
 * ID de la página "Mis fotos". La crea si no existe.
 */
function gamba_mis_fotos_page_id() {
  $page_id = (int) get_option('gamba_mis_fotos_page_id');
  if ($page_id && get_post_status($page_id) === 'publish') {
    return $page_id;
  }

  $page_id = wp_insert_post([
    'post_type'      => 'page',
    'post_status'    => 'publish',
    'post_title'     => 'Tus fotos',
    'post_name'      => 'mis-fotos',
    'post_content'   => '<!-- wp:shortcode -->[gamba_mis_fotos]<!-- /wp:shortcode -->',
    'comment_status' => 'closed',
  ]);

  if (!$page_id || is_wp_error($page_id)) {
    error_log('❌ Gamba: no se pudo crear la página "Mis fotos".');
    return 0;
  }

  // Header y footer de Elementor, sin el título del tema (lo muestra el shortcode)
  update_post_meta($page_id, '_wp_page_template', 'elementor_header_footer');

  // Que Yoast no la indexe ni la incluya en el sitemap
  update_post_meta($page_id, '_yoast_wpseo_meta-robots-noindex', '1');
  update_option('gamba_mis_fotos_page_id', $page_id);

  return $page_id;
}
add_action('init', 'gamba_mis_fotos_page_id');

function gamba_mis_fotos_es_pagina() {
  $page_id = (int) get_option('gamba_mis_fotos_page_id');
  return $page_id && is_page($page_id);
}

/**
 * URL de la página "Mis fotos" de un pedido.
 */
function gamba_mis_fotos_url($order) {
  $page_id = gamba_mis_fotos_page_id();

  return add_query_arg(
    [
      'pedido' => $order->get_order_key(),
      'uid'    => hash('sha256', $order->get_billing_email()),
    ],
    $page_id ? get_permalink($page_id) : home_url('/mis-fotos/')
  );
}

/**
 * Devuelve el pedido del link, o null si el link no es válido.
 */
function gamba_mis_fotos_pedido_del_link() {
  $clave = isset($_GET['pedido']) ? wc_clean(wp_unslash($_GET['pedido'])) : '';
  $uid   = isset($_GET['uid']) ? wc_clean(wp_unslash($_GET['uid'])) : '';

  if (!$clave || !$uid) {
    return null;
  }

  $order = wc_get_order(wc_get_order_id_by_order_key($clave));
  if (!$order || !hash_equals(hash('sha256', $order->get_billing_email()), $uid)) {
    return null;
  }

  return $order;
}

/**
 * Los links son personales: que no los guarde ningún caché.
 */
function gamba_mis_fotos_sin_cache() {
  if (gamba_mis_fotos_es_pagina()) {
    nocache_headers();
    if (!defined('DONOTCACHEPAGE')) {
      define('DONOTCACHEPAGE', true);
    }
  }
}
add_action('template_redirect', 'gamba_mis_fotos_sin_cache');

function gamba_mis_fotos_robots($robots) {
  if (gamba_mis_fotos_es_pagina()) {
    $robots['noindex']  = true;
    $robots['nofollow'] = true;
  }
  return $robots;
}
add_filter('wp_robots', 'gamba_mis_fotos_robots');

/**
 * Shortcode: [gamba_mis_fotos]
 *
 * La página usa la plantilla "Elementor Ancho completo", que no muestra el título
 * del tema, así que el título va acá.
 */
function gamba_mis_fotos_shortcode() {
  $order = gamba_mis_fotos_pedido_del_link();

  ob_start();
  ?>
  <div class="gamba-mis-fotos woocommerce">
    <h1 class="gamba-mis-fotos__titulo"><?php echo esc_html(get_the_title()); ?></h1>

    <?php if (!$order) : ?>
      <div class="gamba-estado gamba-estado--error">
        <span class="gamba-estado__titulo">No encontramos tu pedido</span>
        <span class="gamba-estado__texto">El link no es válido o está incompleto. Probá abrirlo de nuevo desde el email que te enviamos, o escribinos y te ayudamos.</span>
      </div>

    <?php elseif (!$order->is_download_permitted()) : ?>
      <div class="gamba-estado gamba-estado--pendiente">
        <span class="gamba-estado__titulo">Tus fotos todavía no están listas</span>
        <span class="gamba-estado__texto">Estamos esperando la confirmación del pago del pedido #<?php echo esc_html($order->get_order_number()); ?>. Cuando se acredite, te enviamos un email y vas a poder descargarlas desde esta misma página.</span>
      </div>

    <?php else : ?>
      <?php
      wc_get_template('order/order-downloads.php', [
        'downloads'   => $order->get_downloadable_items(),
        'gamba_order' => $order,
      ]);
      ?>
    <?php endif; ?>
  </div>
  <?php
  return ob_get_clean();
}
add_shortcode('gamba_mis_fotos', 'gamba_mis_fotos_shortcode');
