<?php
/**
 * Ajustes de la página de finalizar compra.
 */

if (!defined('ABSPATH')) {
  exit;
}

/**
 * En Uruguay se dice "Localidad" y no "Población". La etiqueta se cambia en los
 * campos por defecto, así vale para facturación, envío y Mi cuenta.
 */
function gamba_checkout_etiqueta_localidad($fields) {
  if (isset($fields['city'])) {
    $fields['city']['label'] = 'Localidad';
  }
  return $fields;
}
add_filter('woocommerce_default_address_fields', 'gamba_checkout_etiqueta_localidad');

/**
 * Estilos de la página de finalizar compra.
 */
function gamba_checkout_enqueue_styles() {
  if (is_checkout()) {
    wp_enqueue_style(
      'gamba-checkout',
      plugin_dir_url(__DIR__) . 'assets/css/checkout.css',
      [],
      '1.0'
    );
  }
}
add_action('wp_enqueue_scripts', 'gamba_checkout_enqueue_styles');
