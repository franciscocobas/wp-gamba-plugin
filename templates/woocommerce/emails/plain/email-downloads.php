<?php
/**
 * Fotos para descargar en los mails del pedido (texto plano).
 *
 * Reemplaza la plantilla emails/plain/email-downloads.php de WooCommerce.
 *
 * @var WC_Order $order
 * @var array    $downloads
 * @var array    $columns
 * @var bool     $plain_text
 * @var WC_Email $email
 */

defined('ABSPATH') || exit;

echo esc_html(wc_strtoupper(count($downloads) === 1 ? 'Tu foto' : 'Tus fotos')) . "\n\n";
echo "En tamaño original y sin marca de agua. Podés usar este link las veces que quieras:\n";
echo esc_url_raw(gamba_mis_fotos_url($order)) . "\n\n";

foreach ($downloads as $download) {
  echo '- ' . wp_kses_post($download['product_name']) . "\n";
  if (is_numeric($download['downloads_remaining'])) {
    echo 'Descargas restantes: ' . (int) $download['downloads_remaining'] . "\n";
  }
  if (!empty($download['access_expires'])) {
    echo 'Disponible hasta el ' . esc_html(date_i18n(get_option('date_format'), strtotime($download['access_expires']))) . "\n";
  }
  echo "\n";
}

echo "¿DÓNDE QUEDA LA FOTO?\n";
foreach (gamba_descargas_ayuda() as $dispositivo => $texto) {
  echo '- ' . esc_html($dispositivo) . ': ' . wp_strip_all_tags($texto) . "\n";
}

echo "\n==========\n\n";
