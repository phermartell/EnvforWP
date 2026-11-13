<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Endpoints REST públicos de Servicios:
 *   GET /envfor/v1/services
 *   GET /envfor/v1/services/{slug}
 */
class Envfor_Service_Rest {

    const NAMESPACE = 'envfor/v1';

    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes() {
        register_rest_route(
            self::NAMESPACE,
            '/services',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_services' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/services/(?P<slug>[a-z0-9-]+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_service' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'slug' => array(
                        'validate_callback' => function ( $param ) {
                            return is_string( $param ) && 1 === preg_match( '/^[a-z0-9-]+$/', $param );
                        },
                    ),
                ),
            )
        );
    }

    public static function get_services( WP_REST_Request $request ) {
        $query = new WP_Query(
            array(
                'post_type'      => 'envfor_service',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => array(
                    'menu_order' => 'ASC',
                    'title'      => 'ASC',
                ),
            )
        );

        $services = array();
        foreach ( $query->posts as $post ) {
            $services[] = self::transform_service( $post );
        }

        return rest_ensure_response( array( 'services' => $services ) );
    }

    public static function get_service( WP_REST_Request $request ) {
        $slug = $request->get_param( 'slug' );

        $posts = get_posts(
            array(
                'post_type'      => 'envfor_service',
                'post_status'    => 'publish',
                'name'           => $slug,
                'posts_per_page' => 1,
            )
        );

        if ( empty( $posts ) ) {
            return new WP_Error(
                'envfor_service_not_found',
                'Servicio no encontrado.',
                array( 'status' => 404 )
            );
        }

        return rest_ensure_response( self::transform_service( $posts[0] ) );
    }

    /**
     * Helper único para transformar WP_Post -> payload público de Servicio.
     */
    private static function transform_service( $post ) {
        $image_id      = absint( get_post_meta( $post->ID, '_envfor_service_image_id', true ) );
        $home_image_id = absint( get_post_meta( $post->ID, '_envfor_service_home_image_id', true ) );

        return array(
            'id'          => (string) $post->ID,
            'name'        => (string) get_the_title( $post->ID ),
            'slug'        => (string) $post->post_name,
            'intro'       => (string) get_post_meta( $post->ID, '_envfor_service_intro', true ),
            'description' => (string) get_post_meta( $post->ID, '_envfor_service_description', true ),
            'image'       => self::attachment_payload( $image_id ),
            'homeImage'   => self::attachment_payload( $home_image_id ),
            'groups'      => self::normalize_groups( get_post_meta( $post->ID, '_envfor_service_groups', true ) ),
            'order'       => (int) $post->menu_order,
        );
    }

    /**
     * Serializa un attachment de imagen en el payload público
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

    private static function normalize_groups( $raw ) {
        $groups = array();
        if ( ! is_array( $raw ) ) {
            return $groups;
        }

        foreach ( $raw as $group ) {
            if ( ! is_array( $group ) ) {
                continue;
            }

            $name  = isset( $group['name'] ) ? sanitize_text_field( (string) $group['name'] ) : '';
            $items = array();
            if ( isset( $group['items'] ) && is_array( $group['items'] ) ) {
                foreach ( $group['items'] as $item ) {
                    $clean = sanitize_text_field( (string) $item );
                    if ( '' !== $clean ) {
                        $items[] = $clean;
                    }
                }
            }

            $groups[] = array(
                'name'  => $name,
                'items' => $items,
            );
        }

        return $groups;
    }
}
