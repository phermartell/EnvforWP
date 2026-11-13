<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * CPT envfor_sector (Giros de ENVFOR).
 *
 * Entidad informativa/clasificatoria. Sin páginas internas ni contenido
 * editorial. Usa el title como nombre, el slug nativo de WordPress, la imagen
 * destacada (thumbnail) como imagen del Giro y el menu_order como orden.
 */
class Envfor_Sector_CPT {

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_cpt' ) );
    }

    public static function register_cpt() {
        $labels = array(
            'name'               => 'Giros',
            'singular_name'      => 'Giro',
            'menu_name'          => 'Giros',
            'add_new'            => 'Añadir giro',
            'add_new_item'       => 'Añadir giro',
            'edit_item'          => 'Editar giro',
            'new_item'           => 'Nuevo giro',
            'view_item'          => 'Ver giro',
            'search_items'       => 'Buscar giros',
            'not_found'          => 'No se encontraron giros',
            'not_found_in_trash' => 'No hay giros en la papelera',
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 23,
            'menu_icon'           => 'dashicons-networking',
            'supports'            => array( 'title', 'thumbnail', 'page-attributes' ),
            'hierarchical'        => false,
            'show_in_rest'        => false, // Usamos un endpoint REST personalizado.
        );

        register_post_type( 'envfor_sector', $args );
    }
}
