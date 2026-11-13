<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Envfor_Rest_API {
    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes() {
        register_rest_route( 'envfor/v1', '/hero', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array( __CLASS__, 'get_hero_slides' ),
            'permission_callback' => '__return_true', // Public read access
        ) );
    }

    public static function get_hero_slides( WP_REST_Request $request ) {
        $args = array(
            'post_type'      => 'envfor_slide',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => array(
                'menu_order' => 'ASC',
                'ID'         => 'ASC'
            )
        );

        $query = new WP_Query( $args );
        $slides = array();

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id = get_the_ID();

                $image_id = get_post_meta( $post_id, '_envfor_image_id', true );
                $image_url = '';
                if ( $image_id && wp_attachment_is_image( $image_id ) ) {
                    $image_src = wp_get_attachment_image_src( $image_id, 'full' );
                    if ( $image_src ) {
                        $image_url = $image_src[0];
                    }
                }

                $focal_x = get_post_meta( $post_id, '_envfor_focal_x', true );
                $focal_y = get_post_meta( $post_id, '_envfor_focal_y', true );
                
                $fx = ( $focal_x !== '' ) ? absint( $focal_x ) : 50;
                $fy = ( $focal_y !== '' ) ? absint( $focal_y ) : 50;
                $object_position = $fx . '% ' . $fy . '%';

                $slides[] = array(
                    'id'             => (string) $post_id,
                    'title'          => get_the_title(),
                    'description'    => (string) get_post_meta( $post_id, '_envfor_description', true ),
                    'image'          => (string) $image_url, // empty string if missing or invalid
                    'ctaLabel'       => (string) get_post_meta( $post_id, '_envfor_cta_label', true ),
                    'ctaHref'        => (string) get_post_meta( $post_id, '_envfor_cta_url', true ),
                    'objectPosition' => $object_position
                );
            }
            wp_reset_postdata();
        }

        return rest_ensure_response( $slides );
    }
}
