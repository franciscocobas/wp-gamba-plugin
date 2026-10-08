<?php
/**
 * Fotos para descargar en los mails del pedido (HTML).
 *
 * Reemplaza la plantilla emails/email-downloads.php de WooCommerce (versión 10.4.0).
 * El botón lleva a la página "Mis fotos", donde se descargan (en iPhone, con el menú
 * de compartir para guardarlas en Fotos). Los estilos van en línea porque muchos
 * clientes de correo ignoran el CSS.
 *
 * @var WC_Order $order
 * @var array    $downloads
 * @var array    $columns
 * @var bool     $plain_text
 * @var WC_Email $email
 */

defined('ABSPATH') || exit;

$url_mis_fotos = gamba_mis_fotos_url($order);
?>
<h2 class="woocommerce-order-downloads__title"><?php echo count($downloads) === 1 ? 'Tu foto' : 'Tus fotos'; ?></h2>

<p style="margin: 0 0 16px;">En tamaño original y sin marca de agua. Podés usar este botón las veces que quieras.</p>

<p style="margin: 0 0 24px;">
  <a href="<?php echo esc_url($url_mis_fotos); ?>" style="display: inline-block; padding: 14px 24px; background: #8C65C3; border: 1px solid #613B96; border-radius: 6px; color: #ffffff; font-size: 16px; font-weight: bold; text-decoration: none;"><?php echo count($downloads) === 1 ? 'Descargar mi foto' : 'Descargar mis fotos'; ?></a>
</p>

<table cellspacing="0" cellpadding="0" border="0" style="width: 100%; margin-bottom: 24px; border-collapse: collapse;">
  <?php foreach ($downloads as $download) : ?>
    <tr>
      <td style="width: 76px; padding: 8px 12px 8px 0; vertical-align: middle; border-bottom: 1px solid #e5e5e5;">
        <a href="<?php echo esc_url($url_mis_fotos); ?>">
          <img src="<?php echo esc_url(gamba_descargas_miniatura_url($download['product_id'], 'thumbnail')); ?>" alt="<?php echo esc_attr($download['product_name']); ?>" width="64" height="64" style="display: block; width: 64px; height: 64px; border-radius: 4px; border: 0; object-fit: cover;" />
        </a>
      </td>
      <td style="padding: 8px 0; vertical-align: middle; text-align: left; border-bottom: 1px solid #e5e5e5;">
        <?php echo esc_html($download['product_name']); ?>
        <?php if (is_numeric($download['downloads_remaining']) || !empty($download['access_expires'])) : ?>
          <br />
          <span style="font-size: 13px; color: #666;">
            <?php
            $limites = [];
            if (is_numeric($download['downloads_remaining'])) {
              $limites[] = sprintf((int) $download['downloads_remaining'] === 1 ? 'Te queda %d descarga' : 'Te quedan %d descargas', (int) $download['downloads_remaining']);
            }
            if (!empty($download['access_expires'])) {
              $limites[] = 'Disponible hasta el ' . date_i18n(get_option('date_format'), strtotime($download['access_expires']));
            }
            echo esc_html(implode(' · ', $limites));
            ?>
          </span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<p style="margin: 0 0 8px;"><strong>¿Dónde queda la foto?</strong></p>
<ul style="margin: 0 0 32px; padding-left: 20px;">
  <?php foreach (gamba_descargas_ayuda() as $dispositivo => $texto) : ?>
    <li style="margin-bottom: 4px;"><strong><?php echo esc_html($dispositivo); ?>:</strong> <?php echo wp_kses_post($texto); ?></li>
  <?php endforeach; ?>
</ul>
