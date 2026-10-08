<?php
/**
 * Lista de fotos para descargar (pedido recibido, ver pedido, Mi cuenta > Descargas
 * y la página "Mis fotos").
 *
 * Reemplaza la plantilla order/order-downloads.php de WooCommerce (versión 3.3.0).
 *
 * @var array         $downloads   Descargas del pedido (WC_Order::get_downloadable_items()).
 * @var bool          $show_title  Si se muestra el título de la sección.
 * @var WC_Order|null $gamba_order Pedido, para mostrar su número y fecha en el encabezado
 *                                 (solo lo pasa la página "Mis fotos").
 */

if (!defined('ABSPATH')) {
  exit;
}

$cantidad = count($downloads);
$pedido   = isset($gamba_order) && $gamba_order instanceof WC_Order ? $gamba_order : null;

if ($pedido) {
  $titulo = 'Pedido #' . $pedido->get_order_number();
} elseif (isset($show_title)) {
  $titulo = $cantidad === 1 ? 'Tu foto' : 'Tus fotos';
} else {
  $titulo = '';
}

$detalle = [];
if ($pedido) {
  $detalle[] = wc_format_datetime($pedido->get_date_created());
}
$detalle[] = $cantidad === 1 ? '1 foto' : $cantidad . ' fotos';
?>
<section class="woocommerce-order-downloads gamba-descargas">
  <div class="gamba-descargas__panel">
    <div class="gamba-descargas__encabezado">
      <div>
        <?php if ($titulo) : ?>
          <h2 class="woocommerce-order-downloads__title gamba-descargas__titulo"><?php echo esc_html($titulo); ?></h2>
        <?php endif; ?>
        <p class="gamba-descargas__detalle"><?php echo esc_html(implode(' · ', $detalle)); ?></p>
      </div>

      <?php if ($cantidad > 1) : ?>
        <button type="button" class="gamba-descargas__todas button alt">Descargar todas (<?php echo esc_html($cantidad); ?>)</button>
      <?php endif; ?>
    </div>

    <p class="gamba-descargas__mensaje" role="status" hidden></p>

    <ul class="gamba-descargas__lista">
      <?php foreach ($downloads as $download) : ?>
        <li class="gamba-descargas__item">
          <div class="gamba-descargas__miniatura">
            <img src="<?php echo esc_url(gamba_descargas_miniatura_url($download['product_id'])); ?>" alt="<?php echo esc_attr($download['product_name']); ?>" loading="lazy" />
          </div>

          <div class="gamba-descargas__info">
            <p class="gamba-descargas__nombre">
              <?php if (!empty($download['product_url'])) : ?>
                <a href="<?php echo esc_url($download['product_url']); ?>"><?php echo esc_html($download['product_name']); ?></a>
              <?php else : ?>
                <?php echo esc_html($download['product_name']); ?>
              <?php endif; ?>
            </p>

            <?php
            $limites = [];
            if (is_numeric($download['downloads_remaining'])) {
              $limites[] = sprintf(
                (int) $download['downloads_remaining'] === 1 ? 'Te queda %d descarga' : 'Te quedan %d descargas',
                (int) $download['downloads_remaining']
              );
            }
            if (!empty($download['access_expires'])) {
              $limites[] = 'Disponible hasta el ' . date_i18n(get_option('date_format'), strtotime($download['access_expires']));
            }
            ?>
            <?php if ($limites) : ?>
              <p class="gamba-descargas__limites"><?php echo esc_html(implode(' · ', $limites)); ?></p>
            <?php endif; ?>

            <a class="gamba-descargas__boton button alt" href="<?php echo esc_url($download['download_url']); ?>" data-gamba-archivo="<?php echo esc_attr(gamba_descargas_nombre_de_descarga($download)); ?>">Descargar foto</a>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div class="gamba-descargas__ayuda">
    <p><strong>¿Dónde queda la foto?</strong></p>
    <ul>
      <?php foreach (gamba_descargas_ayuda() as $dispositivo => $texto) : ?>
        <li><strong><?php echo esc_html($dispositivo); ?>:</strong> <?php echo wp_kses_post($texto); ?></li>
      <?php endforeach; ?>
    </ul>
    <p>También te enviamos un email con el link para descargarlas, así que podés volver a hacerlo cuando quieras. Si no lo encontrás, revisá la carpeta de spam o promociones.</p>
  </div>
</section>
