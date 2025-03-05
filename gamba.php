<?php
/*
Plugin Name: Plugin de Gamba
Description: Plugin que maneja funcionalidades como creación de productos y visualización de categorías.
Version: 1.0
Author: Francisco Cobas
*/

// Incluir los archivos del plugin
require_once plugin_dir_path(__FILE__) . 'includes/create-product-base-on-images.php';
require_once plugin_dir_path(__FILE__) . 'includes/general-options-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes-gallery-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes-home-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes-product-category-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes-product-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes-search-page.php';

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

/**
 * Sincroniza los valores de los campos personalizados de ACF con la tabla wp_termmeta.
 *
 * Por defecto, ACF almacena los valores de los campos en su propia estructura y no en wp_termmeta,
 * lo que impide que funciones como get_terms() puedan ordenar o filtrar correctamente por meta_key.
 *
 * Esta función detecta cuando se actualiza un campo de ACF en una taxonomía (como 'product_cat')
 * y lo guarda manualmente en wp_termmeta usando update_term_meta().
 *
 * 📌 ¿Cómo funciona?
 * - Se ejecuta cada vez que un valor de ACF se actualiza en un término.
 * - Si el campo pertenece a una taxonomía, extrae el term_id desde 'term_XX'.
 * - Guarda el valor en la base de datos con update_term_meta().
 *
 * ⚠️ IMPORTANTE:
 * - Después de agregar este código, es necesario editar y guardar cada categoría para que
 *   los valores de ACF se copien a wp_termmeta.
 * - Una vez sincronizados, se puede usar get_terms() con 'meta_key' y 'orderby' sin problemas.
 */
function sincronizar_acf_con_term_meta($value, $post_id, $field) {
  // Verificar si el post_id comienza con 'term_'
  if (strpos($post_id, 'term_') === 0) {
    // Extraer solo el ID numérico
    $term_id = str_replace('term_', '', $post_id);

    // Guardar el valor en wp_termmeta
    update_term_meta($term_id, $field['name'], $value);
  }

  return $value;
}
add_filter('acf/update_value', 'sincronizar_acf_con_term_meta', 10, 3);

// 🔥 Eliminar metadatos cuando se borra una categoría de productos
function eliminar_metadatos_al_borrar_categoria($term_id, $taxonomy) {
  if ($taxonomy === 'product_cat') {
    global $wpdb;

    // Registrar en debug.log antes de borrar
    error_log("🗑 Eliminando metadatos de la categoría - Term ID: {$term_id}");

    // Eliminar todos los metadatos asociados al término
    $wpdb->delete($wpdb->termmeta, ['term_id' => $term_id]);

    error_log("✅ Metadatos eliminados correctamente para Term ID: {$term_id}");
  }
}
add_action('delete_term', 'eliminar_metadatos_al_borrar_categoria', 10, 2);
