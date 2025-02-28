<?php
// Evita el acceso directo al archivo
if (!defined('ABSPATH')) {
  exit;
}

// Agregar la página de configuración al menú de administración
function mi_plugin_agregar_pagina_configuracion() {
  add_menu_page(
    'Configuraciones Generales GAMBA', // Nombre en el menú
    'Configuraciones Generales GAMBA', // Título de la página
    'manage_options', // Capacidad requerida
    'mi-plugin-configuracion', // Slug del menú
    'mi_plugin_mostrar_pagina_configuracion', // Función de renderizado
    'dashicons-admin-generic', // Ícono del menú
    20 // Posición en el menú
  );
}
add_action('admin_menu', 'mi_plugin_agregar_pagina_configuracion');

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
  register_setting('mi_plugin_opciones_grupo', 'mi_plugin_precio_producto');

  add_settings_section(
    'mi_plugin_seccion_principal',
    'Opciones para la Creación de Productos',
    null,
    'mi-plugin-configuracion'
  );

  add_settings_field(
    'mi_plugin_precio_producto',
    'Precio por Defecto:',
    'mi_plugin_mostrar_campo_precio',
    'mi-plugin-configuracion',
    'mi_plugin_seccion_principal'
  );
}
add_action('admin_init', 'mi_plugin_registrar_configuraciones');

// Campo de entrada para el precio de los productos
function mi_plugin_mostrar_campo_precio() {
  $valor = get_option('mi_plugin_precio_producto', '0.00');
  echo '<input type="number" name="mi_plugin_precio_producto" value="' . esc_attr($valor) . '" class="regular-text" step="0.01" min="0">';
}
