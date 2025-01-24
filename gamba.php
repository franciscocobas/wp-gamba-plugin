<?php
/*
Plugin Name: Plugin de Gamba
Description: Plugin que maneja funcionalidades como creación de productos y visualización de categorías.
Version: 1.0
Author: Francisco Cobas
*/

// Incluir los archivos del plugin
require_once plugin_dir_path(__FILE__) . 'includes/create-woocommerce-product.php';
require_once plugin_dir_path(__FILE__) . 'includes/mostrar-categorias-home.php';

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
