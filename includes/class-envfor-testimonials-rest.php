<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Endpoint REST para los testimoniales del Home:
 *   GET /wp-json/envfor/v1/testimonials
 */
class Envfor_Testimonials_Rest {

    const NAMESPACE = 'envfor/v1';

    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes() {
        register_rest_route(
            self::NAMESPACE,
            '/testimonials',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_testimonials' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    public static function get_testimonials( WP_REST_Request $request ) {
        $saved = get_option( Envfor_Testimonials_Admin::OPTION_NAME, null );

        // Caso: aún no configurado en WordPress -> 404 para que el frontend active fallback a los 4 por defecto
        if ( null === $saved || false === $saved ) {
            return new WP_Error(
                'envfor_testimonials_not_configured',
                'Los testimonios aún no han sido configurados en WordPress.',
                array( 'status' => 404 )
            );
        }

        if ( ! is_array( $saved ) ) {
            $saved = array();
        }

        $visible_items = array();

        foreach ( $saved as $idx => $item ) {
            $is_visible = ! empty( $item['visible'] );
            $company    = isset( $item['company'] ) ? trim( (string) $item['company'] ) : '';
            $quote      = isset( $item['quote'] ) ? trim( (string) $item['quote'] ) : '';

            // Solo exponer testimonios visibles con empresa y cita no vacías
            if ( ! $is_visible || '' === $company || '' === $quote ) {
                continue;
            }

            $id = isset( $item['id'] ) && '' !== trim( (string) $item['id'] )
                ? trim( (string) $item['id'] )
                : 'testimonio_' . ( $idx + 1 );

            $image_id = ! empty( $item['image_id'] ) ? absint( $item['image_id'] ) : 0;
            $image_url = '';
            $image_alt = '';

            if ( $image_id > 0 && wp_attachment_is_image( $image_id ) ) {
                $src = wp_get_attachment_image_src( $image_id, 'full' );
                $image_url = $src ? $src[0] : '';
                // Texto ALT propio del adjunto seleccionado. Si el adjunto no tiene ALT, es cadena vacía.
                // NUNCA hereda el ALT de una foto anterior.
                $image_alt = (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true );
            } elseif ( ! empty( $item['image_url'] ) ) {
                $image_url = (string) $item['image_url'];
                $image_alt = isset( $item['image_alt'] ) ? (string) $item['image_alt'] : $company;
            }

            $visible_items[] = array(
                'id'       => $id,
                'company'  => $company,
                'quote'    => $quote,
                'image'    => $image_url,
                'imageAlt' => $image_alt,
            );
        }

        // Si está configurado pero no hay visibles, devuelve array vacío [] con status 200
        return rest_ensure_response( $visible_items );
    }
}
