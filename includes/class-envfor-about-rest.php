<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Endpoint REST para la sección «Nosotros» del Home:
 *   GET /wp-json/envfor/v1/about
 */
class Envfor_About_Rest {

    const NAMESPACE = 'envfor/v1';

    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes() {
        register_rest_route(
            self::NAMESPACE,
            '/about',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_about' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    public static function get_about( WP_REST_Request $request ) {
        $saved = get_option( Envfor_About_Admin::OPTION_NAME, array() );
        if ( ! is_array( $saved ) ) {
            $saved = array();
        }

        $defaults = Envfor_About_Admin::DEFAULTS;

        // Textos con fallback por campo individual
        $eyebrow     = ( isset( $saved['eyebrow'] ) && is_string( $saved['eyebrow'] ) && '' !== trim( $saved['eyebrow'] ) )
            ? trim( $saved['eyebrow'] )
            : $defaults['eyebrow'];

        $title       = ( isset( $saved['title'] ) && is_string( $saved['title'] ) && '' !== trim( $saved['title'] ) )
            ? trim( $saved['title'] )
            : $defaults['title'];

        $description = ( isset( $saved['description'] ) && is_string( $saved['description'] ) && '' !== trim( $saved['description'] ) )
            ? trim( $saved['description'] )
            : $defaults['description'];

        $f1_title    = ( isset( $saved['feature1_title'] ) && is_string( $saved['feature1_title'] ) && '' !== trim( $saved['feature1_title'] ) )
            ? trim( $saved['feature1_title'] )
            : $defaults['feature1_title'];

        $f1_desc     = ( isset( $saved['feature1_description'] ) && is_string( $saved['feature1_description'] ) && '' !== trim( $saved['feature1_description'] ) )
            ? trim( $saved['feature1_description'] )
            : $defaults['feature1_description'];

        $f2_title    = ( isset( $saved['feature2_title'] ) && is_string( $saved['feature2_title'] ) && '' !== trim( $saved['feature2_title'] ) )
            ? trim( $saved['feature2_title'] )
            : $defaults['feature2_title'];

        $f2_desc     = ( isset( $saved['feature2_description'] ) && is_string( $saved['feature2_description'] ) && '' !== trim( $saved['feature2_description'] ) )
            ? trim( $saved['feature2_description'] )
            : $defaults['feature2_description'];

        $f3_title    = ( isset( $saved['feature3_title'] ) && is_string( $saved['feature3_title'] ) && '' !== trim( $saved['feature3_title'] ) )
            ? trim( $saved['feature3_title'] )
            : $defaults['feature3_title'];

        $f3_desc     = ( isset( $saved['feature3_description'] ) && is_string( $saved['feature3_description'] ) && '' !== trim( $saved['feature3_description'] ) )
            ? trim( $saved['feature3_description'] )
            : $defaults['feature3_description'];

        $cta_label   = ( isset( $saved['cta_label'] ) && is_string( $saved['cta_label'] ) && '' !== trim( $saved['cta_label'] ) )
            ? trim( $saved['cta_label'] )
            : $defaults['cta_label'];

        $cta_href    = $defaults['cta_href']; // Fijo a /nosotros

        // Imagen 1: Foto inferior izquierda (Desktop) / Primera foto (Móvil)
        $image1_id = ! empty( $saved['image1_id'] ) ? absint( $saved['image1_id'] ) : 0;
        if ( $image1_id && wp_attachment_is_image( $image1_id ) ) {
            $src = wp_get_attachment_image_src( $image1_id, 'full' );
            $image1_url = $src ? $src[0] : '';
            // El ALT pertenece exclusivamente al adjunto seleccionado.
            // Si el adjunto no tiene ALT, devuelve cadena vacía; NUNCA hereda el ALT de la foto anterior o predeterminada.
            $image1_alt = (string) get_post_meta( $image1_id, '_wp_attachment_image_alt', true );
        } else {
            $image1_url = $defaults['image1_url'];
            $image1_alt = $defaults['image1_alt'];
        }

        // Imagen 2: Foto superior derecha (Desktop) / Segunda foto (Móvil)
        $image2_id = ! empty( $saved['image2_id'] ) ? absint( $saved['image2_id'] ) : 0;
        if ( $image2_id && wp_attachment_is_image( $image2_id ) ) {
            $src = wp_get_attachment_image_src( $image2_id, 'full' );
            $image2_url = $src ? $src[0] : '';
            // El ALT pertenece exclusivamente al adjunto seleccionado.
            $image2_alt = (string) get_post_meta( $image2_id, '_wp_attachment_image_alt', true );
        } else {
            $image2_url = $defaults['image2_url'];
            $image2_alt = $defaults['image2_alt'];
        }

        $response = array(
            'eyebrow'     => $eyebrow,
            'title'       => $title,
            'description' => $description,
            'feature1'    => array(
                'title'       => $f1_title,
                'description' => $f1_desc,
            ),
            'feature2'    => array(
                'title'       => $f2_title,
                'description' => $f2_desc,
            ),
            'feature3'    => array(
                'title'       => $f3_title,
                'description' => $f3_desc,
            ),
            'features'    => array(
                array(
                    'title'       => $f1_title,
                    'description' => $f1_desc,
                ),
                array(
                    'title'       => $f2_title,
                    'description' => $f2_desc,
                ),
                array(
                    'title'       => $f3_title,
                    'description' => $f3_desc,
                ),
            ),
            'ctaLabel'    => $cta_label,
            'ctaHref'     => $cta_href,
            'image1'      => array(
                'url' => $image1_url,
                'alt' => $image1_alt,
            ),
            'image2'      => array(
                'url' => $image2_url,
                'alt' => $image2_alt,
            ),
        );

        return rest_ensure_response( $response );
    }
}
