<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Endpoints REST públicos de Proyectos:
 *   GET /envfor/v1/projects
 *   GET /envfor/v1/projects/{slug}
 */
class Envfor_Project_Rest {

    const NAMESPACE = 'envfor/v1';
    const MAX_LIMIT = 100;

    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes() {
        register_rest_route(
            self::NAMESPACE,
            '/projects',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_projects' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/projects/(?P<slug>[a-z0-9-]+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_project' ),
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

    public static function get_projects( WP_REST_Request $request ) {
        $args = array(
            'post_type'      => 'envfor_project',
            'post_status'    => 'publish',
            'posts_per_page' => self::sanitize_limit( $request->get_param( 'limit' ) ),
            'orderby'        => array(
                'menu_order' => 'ASC',
                'title'      => 'ASC',
            ),
        );

        $meta_query = array();

        $service_slug = sanitize_text_field( (string) $request->get_param( 'service' ) );
        if ( '' !== $service_slug ) {
            $service_id = self::resolve_service_id( $service_slug );
            if ( ! $service_id ) {
                return rest_ensure_response( array( 'projects' => array() ) );
            }
            $meta_query[] = array(
                'key'     => '_envfor_project_service_id',
                'value'   => $service_id,
                'compare' => '=',
            );
        }

        $sector_slug = sanitize_text_field( (string) $request->get_param( 'sector' ) );
        if ( '' !== $sector_slug ) {
            $sector_id = self::resolve_sector_id( $sector_slug );
            if ( ! $sector_id ) {
                return rest_ensure_response( array( 'projects' => array() ) );
            }
            $meta_query[] = array(
                'key'     => '_envfor_project_sector_id',
                'value'   => $sector_id,
                'compare' => '=',
            );
        }

        if ( ! empty( $meta_query ) ) {
            $args['meta_query'] = $meta_query;
        }

        $location_slug = sanitize_text_field( (string) $request->get_param( 'location' ) );
        if ( '' !== $location_slug ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'project_location',
                    'field'    => 'slug',
                    'terms'    => $location_slug,
                ),
            );
        }

        $query    = new WP_Query( $args );
        $projects = array();
        foreach ( $query->posts as $post ) {
            $projects[] = self::transform_project_listing( $post );
        }

        return rest_ensure_response( array( 'projects' => $projects ) );
    }

    public static function get_project( WP_REST_Request $request ) {
        $slug = $request->get_param( 'slug' );

        $posts = get_posts(
            array(
                'post_type'      => 'envfor_project',
                'post_status'    => 'publish',
                'name'           => $slug,
                'posts_per_page' => 1,
            )
        );

        if ( empty( $posts ) ) {
            return new WP_Error(
                'envfor_project_not_found',
                'Proyecto no encontrado.',
                array( 'status' => 404 )
            );
        }

        return rest_ensure_response( self::transform_project_detail( $posts[0] ) );
    }

    private static function transform_project_listing( $post ) {
        return array(
            'id'        => (int) $post->ID,
            'name'      => (string) get_the_title( $post->ID ),
            'slug'      => (string) $post->post_name,
            'client'    => (string) get_post_meta( $post->ID, '_envfor_project_client', true ),
            'intro'     => (string) get_post_meta( $post->ID, '_envfor_project_intro', true ),
            'homeImage' => self::attachment_payload( get_post_meta( $post->ID, '_envfor_project_home_image_id', true ) ),
            'services'  => self::services_payload( $post->ID ),
            'sectors'   => self::sectors_payload( $post->ID ),
            'locations' => self::locations_payload( $post->ID ),
            'order'     => (int) $post->menu_order,
        );
    }

    private static function transform_project_detail( $post ) {
        $data = self::transform_project_listing( $post );

        $data['description'] = (string) get_post_meta( $post->ID, '_envfor_project_description', true );
        $data['heroImage']   = self::attachment_payload( get_post_meta( $post->ID, '_envfor_project_hero_image_id', true ) );
        $data['works']       = self::normalize_works( get_post_meta( $post->ID, '_envfor_project_works', true ) );
        $data['gallery']     = self::normalize_gallery( get_post_meta( $post->ID, '_envfor_project_gallery', true ) );

        return $data;
    }

    private static function services_payload( $project_id ) {
        $service_ids = get_post_meta( $project_id, '_envfor_project_service_id', false );
        $services    = array();

        foreach ( $service_ids as $service_id ) {
            $service_id = absint( $service_id );
            if ( ! $service_id || get_post_type( $service_id ) !== 'envfor_service' ) {
                continue;
            }
            if ( 'publish' !== get_post_status( $service_id ) ) {
                continue;
            }

            $services[] = array(
                'id'   => (int) $service_id,
                'name' => (string) get_the_title( $service_id ),
                'slug' => (string) get_post_field( 'post_name', $service_id ),
            );
        }

        return $services;
    }

    private static function sectors_payload( $project_id ) {
        $sector_ids = get_post_meta( $project_id, '_envfor_project_sector_id', false );
        $sectors    = array();

        foreach ( $sector_ids as $sector_id ) {
            $sector_id = absint( $sector_id );
            if ( ! $sector_id || get_post_type( $sector_id ) !== 'envfor_sector' ) {
                continue;
            }
            if ( 'publish' !== get_post_status( $sector_id ) ) {
                continue;
            }

            $sectors[] = array(
                'id'   => (int) $sector_id,
                'name' => (string) get_the_title( $sector_id ),
                'slug' => (string) get_post_field( 'post_name', $sector_id ),
            );
        }

        return $sectors;
    }

    private static function locations_payload( $project_id ) {
        $terms = get_the_terms( $project_id, 'project_location' );
        if ( ! $terms || is_wp_error( $terms ) ) {
            return array();
        }

        $locations = array();
        foreach ( $terms as $term ) {
            $locations[] = array(
                'id'   => (int) $term->term_id,
                'name' => (string) $term->name,
                'slug' => (string) $term->slug,
            );
        }

        return $locations;
    }


    private static function normalize_works( $raw ) {
        $works = array();
        if ( ! is_array( $raw ) ) {
            return $works;
        }

        foreach ( $raw as $work ) {
            $clean = sanitize_text_field( (string) $work );
            if ( '' !== $clean ) {
                $works[] = $clean;
            }
        }

        return $works;
    }

    private static function normalize_gallery( $raw ) {
        $gallery = array();
        if ( ! is_array( $raw ) ) {
            return $gallery;
        }

        foreach ( $raw as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }

            $image_id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
            if ( ! $image_id ) {
                continue;
            }

            $image = self::attachment_payload( $image_id );
            if ( ! $image ) {
                continue;
            }

            $caption = isset( $item['caption'] ) ? sanitize_text_field( (string) $item['caption'] ) : '';

            $gallery[] = array(
                'image'   => $image,
                'caption' => $caption,
            );
        }

        return $gallery;
    }

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

    private static function resolve_service_id( $slug ) {
        $posts = get_posts(
            array(
                'post_type'      => 'envfor_service',
                'post_status'    => 'publish',
                'name'           => $slug,
                'posts_per_page' => 1,
            )
        );

        if ( empty( $posts ) ) {
            return 0;
        }

        return (int) $posts[0]->ID;
    }

    private static function resolve_sector_id( $slug ) {
        $posts = get_posts(
            array(
                'post_type'      => 'envfor_sector',
                'post_status'    => 'publish',
                'name'           => $slug,
                'posts_per_page' => 1,
            )
        );

        if ( empty( $posts ) ) {
            return 0;
        }

        return (int) $posts[0]->ID;
    }

    private static function sanitize_limit( $limit ) {
        if ( null === $limit || '' === $limit ) {
            return -1;
        }

        $limit = absint( $limit );
        if ( 0 === $limit ) {
            return -1;
        }

        return $limit > self::MAX_LIMIT ? self::MAX_LIMIT : $limit;
    }
}

