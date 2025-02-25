<?php
/*
Plugin Name: Plugin de Gamba
Description: Plugin que maneja funcionalidades como creación de productos y visualización de categorías.
Version: 1.0
Author: Francisco Cobas
*/

// Incluir los archivos del plugin
require_once plugin_dir_path(__FILE__) . 'includes/create-product-base-on-images.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes-gallery-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes-home-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes-product-category-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes-product-page.php';

// Registrar y encolar los estilos del plugin
function mi_plugin_enqueue_styles() {
  // Registrar el archivo CSS del plugin
  wp_enqueue_style(
    'mi-plugin-estilos', // Handle único para identificar el estilo
    plugin_dir_url(__FILE__) . 'assets/css/styles.css', // URL del archivo CSS
    [], // Dependencias (dejar vacío si no hay)
    '1.0', // Versión del archivo CSS
    'all' // Tipo de medios (como 'all', 'screen', etc.)
  );
}
add_action('wp_enqueue_scripts', 'mi_plugin_enqueue_styles');

// Cambiar el logo en la pantalla de login
function mi_personalizacion_logo_login() {
  ?>
  <style type="text/css">
    #login h1 a {
      background-image: url('<?php echo plugin_dir_url(__FILE__) . '/logo-gamba.png'; ?>');
      width: 320px;
      height: 84px;
      background-size: contain;
      background-repeat: no-repeat;
    }
  </style>
  <?php
}
add_action('login_enqueue_scripts', 'mi_personalizacion_logo_login');

// Cambiar la URL del logo
function mi_personalizacion_logo_url() {
  return home_url(); // Cambia esto si quieres otra URL
}
add_filter('login_headerurl', 'mi_personalizacion_logo_url');

// Cambiar el texto alternativo del logo
function mi_personalizacion_logo_title() {
  return get_bloginfo('name');
}
add_filter('login_headertext', 'mi_personalizacion_logo_title');
