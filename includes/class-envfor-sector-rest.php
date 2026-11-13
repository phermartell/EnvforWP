<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Endpoint REST público de Giros:
 *   GET /envfor/v1/sectors
 *
 * No existe endpoint de detalle: los Giros son una entidad informativa sin
 * páginas internas.
 */
class Envfor_Sector_Rest {

    const NAMESPACE = 'envfor/v1';

    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes() {
        register_rest_route(
            self::NAMESPACE,
            '/sectors',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_sectors' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    public static function get_sectors( WP_REST_Request $request ) {
        $query = new WP_Query(
            array(
                'post_type'      => 'envfor_sector',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => array(
                    'menu_order' => 'ASC',
                    'title'      => 'ASC',
                ),
            )
        );

        $sectors = array();
        foreach ( $query->posts as $post ) {
            $sectors[] = array(
                'id'    => (int) $post->ID,
                'name'  => (string) get_the_title( $post->ID ),
                'slug'  => (string) $post->post_name,
                'order' => (int) $post->menu_order,
                'image' => self::attachment_payload( get_post_thumbnail_id( $post->ID ) ),
            );
        }

        return rest_ensure_response( array( 'sectors' => $sectors ) );
    }

    /**
     * Serializa la imagen destacada (thumbnail) en el payload público
     * (id, url, alt, width, height) o null si no existe/no es imagen.
     */
    private static function attachment_payload( $attachment_id ) {
        $attachment_id = absint( $attachment_id );

        if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
            return null;
        }

        $src = wp_get_attachment_image_src( $attachment_id, 'full' );

        return array(
            'id'     => $attachment_id,
            'url'    => $src ? (string) $src[0] : '',
            'alt'    => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
            'width'  => $src ? (int) $src[1] : 0,
            'height' => $src ? (int) $src[2] : 0,
        );
    }
}
