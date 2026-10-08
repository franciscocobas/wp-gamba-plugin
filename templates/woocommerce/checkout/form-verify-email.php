<?php
/**
 * Pantalla que pide el email del pedido cuando WooCommerce no reconoce al comprador
 * (por ejemplo, si vuelve desde otro navegador o desde la app de Mercado Pago).
 *
 * Reemplaza la plantilla checkout/form-verify-email.php de WooCommerce (versión 7.9.0).
 * Las compras se hacen como invitado, así que no se ofrece iniciar sesión.
 *
 * @var bool   $failed_submission Indica si el último intento de verificación falló.
 * @var string $verify_url        URL del formulario de verificación.
 */

defined('ABSPATH') || exit;
?>
<form name="checkout" method="post" class="woocommerce-form woocommerce-verify-email gamba-verificar-email" action="<?php echo esc_url($verify_url); ?>">

  <?php
  wp_nonce_field('wc_verify_email', 'check_submission');

  if ($failed_submission) {
    wc_print_notice('Ese email no coincide con el del pedido. Revisá que sea el mismo que ingresaste al comprar.', 'error');
  }
  ?>
  <p><strong>Para ver tu pedido y descargar tus fotos, ingresá el email que usaste al comprar.</strong></p>
  <p>Te lo pedimos por seguridad, porque abriste esta página desde otro navegador o dispositivo. Los links de descarga también te llegaron a ese email: si no lo encontrás, revisá la carpeta de spam o promociones.</p>

  <p class="form-row">
    <label for="email">Email&nbsp;<span class="required">*</span></label>
    <input type="email" class="input-text" name="email" id="email" autocomplete="email" required />
  </p>

  <p class="form-row">
    <button type="submit" class="woocommerce-button button <?php echo esc_attr(wc_wp_theme_get_element_class_name('button')); ?>" name="verify" value="1">
      Ver mis fotos
    </button>
  </p>
</form>
