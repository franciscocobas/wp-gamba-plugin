<?php
/*
Plugin Name: Plugin de Gamba
Description: Plugin que maneja funcionalidades como creación de productos y visualización de categorías.
Version: 1.0
Author: Francisco Cobas
*/

// Incluir los archivos del plugin
require_once plugin_dir_path(__FILE__) . 'includes/add-products-to-category.php';
require_once plugin_dir_path(__FILE__) . 'includes/create-product-base-on-images.php';
require_once plugin_dir_path(__FILE__) . 'includes/general-options-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes-gallery-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes-home-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes-product-category-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes-product-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes-search-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes-template-category.php';

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

// Agregar un nuevo menú principal llamado "Gamba"
function gamba_agregar_menu() {
  // Menú principal (apunta a "Crear productos por foto")
  add_menu_page(
    'Gamba',
    'Gamba',
    'manage_woocommerce',
    'crear-productos-por-fotos',  // Ahora apunta al slug de "Crear productos por foto"
    'cpbf_pagina_contenido',
    'dashicons-camera',
    56
  );

  // Submenú: Crear productos por foto (mismo slug que el menú principal)
  add_submenu_page(
    'crear-productos-por-fotos',
    'Crear productos por foto',
    'Crear productos por foto',
    'manage_woocommerce',
    'crear-productos-por-fotos',
    'cpbf_pagina_contenido'
  );

  // Submenú: Ajustes generales
  add_submenu_page(
    'crear-productos-por-fotos',
    'Ajustes generales',
    'Ajustes generales',
    'manage_woocommerce',
    'gamba-ajustes-generales',
    'mi_plugin_mostrar_pagina_configuracion'
  );

  // Submenú: Agregar productos a evento
  add_submenu_page(
    'crear-productos-por-fotos',
    'Agregar productos a evento',
    'Agregar productos a evento',
    'manage_woocommerce',
    'gamba-agregar-productos-evento',
    'gamba_pagina_agregar_productos_evento'
  );
}
add_action('admin_menu', 'gamba_agregar_menu');

add_filter( 'template_include', function( $template ) {
  error_log('🔍 WordPress está cargando la plantilla: ' . $template);

  if ( is_tax( 'product_cat' ) ) {
    $term = get_queried_object();

    error_log('🟡 Entrando en is_tax(product_cat)');
    error_log('🔹 ID de la categoría: ' . $term->term_id);
    error_log('🔹 Parent ID: ' . $term->parent);

    if ( $term->parent != 0 ) { // Solo si es subcategoría
      $custom_template = WP_PLUGIN_DIR . '/gamba/elementor-templates/template-subcategories.php';

      // Registrar si el archivo existe
      if ( file_exists( $custom_template ) ) {
        error_log('✅ Plantilla encontrada: ' . $custom_template);
        return $custom_template;
      } else {
        error_log('❌ ERROR: No se encontró la plantilla en: ' . $custom_template);
      }
    } else {
      $custom_template = WP_PLUGIN_DIR . '/gamba/elementor-templates/template-categories.php';
      return $custom_template;
    }
  }
  return $template;
}, 999);
