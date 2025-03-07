<?php
// Evita el acceso directo al archivo
if (!defined('ABSPATH')) {
  exit;
}

// Renderizar la página de configuración
function mi_plugin_mostrar_pagina_configuracion() {
  ?>
  <div class="wrap">
    <h1>Configuraciones Generales de GAMBA</h1>
    <form method="post" action="options.php">
      <?php
      settings_fields('mi_plugin_opciones_grupo');
      do_settings_sections('mi-plugin-configuracion');
      submit_button();
      ?>
    </form>
  </div>
  <?php
}

// Registrar configuraciones
function mi_plugin_registrar_configuraciones() {
  register_setting('mi_plugin_opciones_grupo', 'mi_plugin_precio_1_foto');
  register_setting('mi_plugin_opciones_grupo', 'mi_plugin_precio_5_fotos');
  register_setting('mi_plugin_opciones_grupo', 'mi_plugin_precio_10_fotos');

  add_settings_section(
    'mi_plugin_seccion_principal',
    'Opciones para la Creación de Productos',
    null,
    'mi-plugin-configuracion'
  );

  add_settings_field(
    'mi_plugin_precio_1_foto',
    'Precio por Defecto (1 Foto):',
    'mi_plugin_mostrar_campo_precio_1',
    'mi-plugin-configuracion',
    'mi_plugin_seccion_principal'
  );

  add_settings_field(
    'mi_plugin_precio_5_fotos',
    'Precio por Defecto (5 Fotos):',
    'mi_plugin_mostrar_campo_precio_5',
    'mi-plugin-configuracion',
    'mi_plugin_seccion_principal'
  );

  add_settings_field(
    'mi_plugin_precio_10_fotos',
    'Precio por Defecto (10 Fotos):',
    'mi_plugin_mostrar_campo_precio_10',
    'mi-plugin-configuracion',
    'mi_plugin_seccion_principal'
  );
}
add_action('admin_init', 'mi_plugin_registrar_configuraciones');

// Campos de entrada para los precios
function mi_plugin_mostrar_campo_precio_1() {
  $valor = get_option('mi_plugin_precio_1_foto', '0.00');
  echo '<input type="number" name="mi_plugin_precio_1_foto" value="' . esc_attr($valor) . '" class="regular-text" step="0.01" min="0">';
}

function mi_plugin_mostrar_campo_precio_5() {
  $valor = get_option('mi_plugin_precio_5_fotos', '0.00');
  echo '<input type="number" name="mi_plugin_precio_5_fotos" value="' . esc_attr($valor) . '" class="regular-text" step="0.01" min="0">';
}

function mi_plugin_mostrar_campo_precio_10() {
  $valor = get_option('mi_plugin_precio_10_fotos', '0.00');
  echo '<input type="number" name="mi_plugin_precio_10_fotos" value="' . esc_attr($valor) . '" class="regular-text" step="0.01" min="0">';
}

// Aplicar descuentos basados en la cantidad
function aplicar_descuento_por_cantidad( $cart ) {
  if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

  $precio_1_foto = get_option('mi_plugin_precio_1_foto', 0);
  $precio_5_fotos = get_option('mi_plugin_precio_5_fotos', 0);
  $precio_10_fotos = get_option('mi_plugin_precio_10_fotos', 0);

  foreach ( $cart->get_cart() as $cart_item ) {
    $cantidad = $cart_item['quantity'];
    $nuevo_precio = $cart_item['data']->get_regular_price();

    // Aplicar precios según la cantidad
    if ( $cantidad == 1 ) {
      $nuevo_precio = $precio_1_foto;
    } elseif ( $cantidad >= 5 && $cantidad < 10 ) {
      $nuevo_precio = $precio_5_fotos / 5; // Precio unitario para 5 fotos
    } elseif ( $cantidad >= 10 ) {
      $nuevo_precio = $precio_10_fotos / 10; // Precio unitario para 10 fotos
    }

    $cart_item['data']->set_price( $nuevo_precio );
  }
}
add_action( 'woocommerce_before_calculate_totals', 'aplicar_descuento_por_cantidad' );
