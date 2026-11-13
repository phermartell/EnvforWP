<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * CPT envfor_project (proyectos/obras de ENVFOR).
 *
 * Cada entrada representa UN proyecto. La relación con Servicios se guarda
 * como múltiples filas de post_meta `_envfor_project_service_id` (sin array
 * serializado). La ubicación se modela con la taxonomy `project_location`.
 */
class Envfor_Project_CPT {

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_cpt' ) );
        add_action( 'init', array( __CLASS__, 'register_taxonomy' ) );
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'save_post', array( __CLASS__, 'save_meta_boxes' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );
    }

    public static function register_cpt() {
        $labels = array(
            'name'               => 'Proyectos',
            'singular_name'      => 'Proyecto',
            'menu_name'          => 'Proyectos',
            'add_new'            => 'Añadir Nuevo',
            'add_new_item'       => 'Añadir Nuevo Proyecto',
            'edit_item'          => 'Editar Proyecto',
            'new_item'           => 'Nuevo Proyecto',
            'view_item'          => 'Ver Proyecto',
            'search_items'       => 'Buscar Proyectos',
            'not_found'          => 'No se encontraron proyectos',
            'not_found_in_trash' => 'No hay proyectos en la papelera',
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 22,
            'menu_icon'           => 'dashicons-portfolio',
            'supports'            => array( 'title', 'page-attributes' ),
            'hierarchical'        => false,
            'show_in_rest'        => false, // Usamos un endpoint REST personalizado.
        );

        register_post_type( 'envfor_project', $args );
    }

    public static function register_taxonomy() {
        $labels = array(
            'name'              => 'Ubicaciones',
            'singular_name'     => 'Ubicación',
            'menu_name'         => 'Ubicaciones',
            'search_items'      => 'Buscar Ubicaciones',
            'all_items'         => 'Todas las Ubicaciones',
            'parent_item'       => 'Ubicación padre',
            'parent_item_colon' => 'Ubicación padre:',
            'edit_item'         => 'Editar Ubicación',
            'update_item'       => 'Actualizar Ubicación',
            'add_new_item'      => 'Añadir Nueva Ubicación',
            'new_item_name'     => 'Nuevo nombre de Ubicación',
            'not_found'         => 'No se encontraron ubicaciones',
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => true, // checkboxes nativos, selección múltiple.
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => false,
            'public'            => false,
            'show_in_menu'      => true,
        );

        register_taxonomy( 'project_location', array( 'envfor_project' ), $args );
    }

    public static function add_meta_boxes() {
        add_meta_box(
            'envfor_project_meta',
            'Configuración del Proyecto',
            array( __CLASS__, 'render_meta_box' ),
            'envfor_project',
            'normal',
            'high'
        );

        add_meta_box(
            'envfor_project_services',
            'Servicios relacionados',
            array( __CLASS__, 'render_services_meta_box' ),
            'envfor_project',
            'side',
            'default'
        );

        add_meta_box(
            'envfor_project_sectors',
            'Giros relacionados',
            array( __CLASS__, 'render_sectors_meta_box' ),
            'envfor_project',
            'side',
            'default'
        );
    }

    public static function render_meta_box( $post ) {
        wp_nonce_field( 'envfor_project_save', 'envfor_project_nonce' );

        $client      = get_post_meta( $post->ID, '_envfor_project_client', true );
        $intro       = get_post_meta( $post->ID, '_envfor_project_intro', true );
        $description = get_post_meta( $post->ID, '_envfor_project_description', true );

        $hero_image_id  = absint( get_post_meta( $post->ID, '_envfor_project_hero_image_id', true ) );
        $hero_image_url = $hero_image_id ? wp_get_attachment_url( $hero_image_id ) : '';

        $home_image_id  = absint( get_post_meta( $post->ID, '_envfor_project_home_image_id', true ) );
        $home_image_url = $home_image_id ? wp_get_attachment_url( $home_image_id ) : '';

        $works = get_post_meta( $post->ID, '_envfor_project_works', true );
        if ( ! is_array( $works ) ) {
            $works = array();
        }

        $gallery_raw = get_post_meta( $post->ID, '_envfor_project_gallery', true );
        if ( ! is_array( $gallery_raw ) ) {
            $gallery_raw = array();
        }

        $gallery_for_js = array();
        foreach ( $gallery_raw as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }
            $gallery_id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
            if ( ! $gallery_id || ! wp_attachment_is_image( $gallery_id ) ) {
                continue;
            }
            $gallery_url      = wp_get_attachment_url( $gallery_id );
            $gallery_for_js[] = array(
                'id'      => $gallery_id,
                'caption' => isset( $item['caption'] ) ? (string) $item['caption'] : '',
                'url'     => $gallery_url ? $gallery_url : '',
            );
        }
        ?>
        <div style="display: flex; flex-direction: column; gap: 15px; padding: 10px 0;">
            <p>
                <label for="envfor_project_client"><strong>Cliente:</strong></label><br>
                <input type="text" name="envfor_project_client" id="envfor_project_client" value="<?php echo esc_attr( $client ); ?>" style="width:100%;">
            </p>

            <p>
                <label for="envfor_project_intro"><strong>Intro:</strong></label><br>
                <textarea name="envfor_project_intro" id="envfor_project_intro" rows="3" style="width:100%;"><?php echo esc_textarea( $intro ); ?></textarea>
            </p>

            <p>
                <label for="envfor_project_description"><strong>Descripción:</strong></label><br>
                <textarea name="envfor_project_description" id="envfor_project_description" rows="8" style="width:100%;"><?php echo esc_textarea( $description ); ?></textarea>
            </p>

            <p>
                <label><strong>Imagen hero:</strong></label><br>
                <input type="hidden" name="envfor_project_hero_image_id" id="envfor_project_hero_image_id" value="<?php echo esc_attr( $hero_image_id ); ?>">
                <div id="envfor_project_hero_image_preview" style="max-width: 300px; margin-bottom: 10px;">
                    <?php if ( $hero_image_url ) : ?>
                        <img src="<?php echo esc_url( $hero_image_url ); ?>" style="max-width: 100%; height: auto; border: 1px solid #ccc;">
                    <?php endif; ?>
                </div>
                <button type="button" class="button" id="envfor_project_upload_hero_image_btn">Seleccionar imagen</button>
                <button type="button" class="button" id="envfor_project_remove_hero_image_btn" <?php echo $hero_image_id ? '' : 'style="display:none;"'; ?>>Quitar imagen</button>
            </p>

            <p>
                <label><strong>Imagen Home:</strong></label><br>
                <input type="hidden" name="envfor_project_home_image_id" id="envfor_project_home_image_id" value="<?php echo esc_attr( $home_image_id ); ?>">
                <div id="envfor_project_home_image_preview" style="max-width: 300px; margin-bottom: 10px;">
                    <?php if ( $home_image_url ) : ?>
                        <img src="<?php echo esc_url( $home_image_url ); ?>" style="max-width: 100%; height: auto; border: 1px solid #ccc;">
                    <?php endif; ?>
                </div>
                <button type="button" class="button" id="envfor_project_upload_home_image_btn">Seleccionar imagen</button>
                <button type="button" class="button" id="envfor_project_remove_home_image_btn" <?php echo $home_image_id ? '' : 'style="display:none;"'; ?>>Quitar imagen</button>
            </p>


            <hr>

            <p><strong>Trabajos realizados:</strong></p>
            <div id="envfor-project-works" data-works="<?php echo esc_attr( wp_json_encode( $works ) ); ?>"></div>
            <p>
                <button type="button" class="button" id="envfor-add-work">Agregar trabajo</button>
            </p>
            <input type="hidden" name="envfor_project_works" id="envfor_project_works" value="">

            <hr>

            <p><strong>Galería:</strong></p>
            <div id="envfor-project-gallery" data-gallery="<?php echo esc_attr( wp_json_encode( $gallery_for_js ) ); ?>"></div>
            <p>
                <button type="button" class="button" id="envfor-add-gallery">Agregar imágenes</button>
            </p>
            <input type="hidden" name="envfor_project_gallery" id="envfor_project_gallery" value="">
        </div>
        <?php
    }

    public static function render_services_meta_box( $post ) {
        wp_nonce_field( 'envfor_project_services_save', 'envfor_project_services_nonce' );

        $services = get_posts(
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

        $selected = get_post_meta( $post->ID, '_envfor_project_service_id', false );
        $selected = array_map( 'absint', (array) $selected );

        if ( empty( $services ) ) {
            echo '<p>No hay servicios publicados.</p>';
            return;
        }

        echo '<ul style="margin:0; padding:0; list-style:none;">';
        foreach ( $services as $service ) {
            $checked = in_array( (int) $service->ID, $selected, true ) ? 'checked="checked"' : '';
            echo '<li style="margin:4px 0;">';
            echo '<label style="display:flex; gap:6px; align-items:flex-start;">';
            echo '<input type="checkbox" name="envfor_project_service_id[]" value="' . esc_attr( $service->ID ) . '" ' . $checked . '>';
            echo '<span>' . esc_html( get_the_title( $service->ID ) ) . '</span>';
            echo '</label>';
            echo '</li>';
        }
        echo '</ul>';
    }

    public static function render_sectors_meta_box( $post ) {
        wp_nonce_field( 'envfor_project_sectors_save', 'envfor_project_sectors_nonce' );

        $sectors = get_posts(
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

        $selected = get_post_meta( $post->ID, '_envfor_project_sector_id', false );
        $selected = array_map( 'absint', (array) $selected );

        if ( empty( $sectors ) ) {
            echo '<p>No hay giros publicados.</p>';
            return;
        }

        echo '<ul style="margin:0; padding:0; list-style:none;">';
        foreach ( $sectors as $sector ) {
            $checked = in_array( (int) $sector->ID, $selected, true ) ? 'checked="checked"' : '';
            echo '<li style="margin:4px 0;">';
            echo '<label style="display:flex; gap:6px; align-items:flex-start;">';
            echo '<input type="checkbox" name="envfor_project_sector_id[]" value="' . esc_attr( $sector->ID ) . '" ' . $checked . '>';
            echo '<span>' . esc_html( get_the_title( $sector->ID ) ) . '</span>';
            echo '</label>';
            echo '</li>';
        }
        echo '</ul>';
    }


    public static function save_meta_boxes( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['envfor_project_nonce'] ) && wp_verify_nonce( $_POST['envfor_project_nonce'], 'envfor_project_save' ) ) {
            self::save_fields( $post_id );
        }

        if ( isset( $_POST['envfor_project_services_nonce'] ) && wp_verify_nonce( $_POST['envfor_project_services_nonce'], 'envfor_project_services_save' ) ) {
            self::save_services( $post_id );
        }

        if ( isset( $_POST['envfor_project_sectors_nonce'] ) && wp_verify_nonce( $_POST['envfor_project_sectors_nonce'], 'envfor_project_sectors_save' ) ) {
            self::save_sectors( $post_id );
        }
    }

    private static function save_fields( $post_id ) {
        if ( isset( $_POST['envfor_project_client'] ) ) {
            update_post_meta( $post_id, '_envfor_project_client', sanitize_text_field( wp_unslash( $_POST['envfor_project_client'] ) ) );
        }

        if ( isset( $_POST['envfor_project_intro'] ) ) {
            update_post_meta( $post_id, '_envfor_project_intro', sanitize_textarea_field( wp_unslash( $_POST['envfor_project_intro'] ) ) );
        }

        if ( isset( $_POST['envfor_project_description'] ) ) {
            update_post_meta( $post_id, '_envfor_project_description', sanitize_textarea_field( wp_unslash( $_POST['envfor_project_description'] ) ) );
        }

        self::save_image( $post_id, 'envfor_project_hero_image_id', '_envfor_project_hero_image_id' );
        self::save_image( $post_id, 'envfor_project_home_image_id', '_envfor_project_home_image_id' );

        if ( isset( $_POST['envfor_project_works'] ) ) {
            $works = self::sanitize_works( wp_unslash( $_POST['envfor_project_works'] ) );
            update_post_meta( $post_id, '_envfor_project_works', $works );
        }

        if ( isset( $_POST['envfor_project_gallery'] ) ) {
            $gallery = self::sanitize_gallery( wp_unslash( $_POST['envfor_project_gallery'] ) );
            update_post_meta( $post_id, '_envfor_project_gallery', $gallery );
        }
    }

    private static function save_image( $post_id, $field, $meta_key ) {
        if ( isset( $_POST[ $field ] ) ) {
            $image_id = absint( $_POST[ $field ] );
            if ( $image_id && wp_attachment_is_image( $image_id ) ) {
                update_post_meta( $post_id, $meta_key, $image_id );
            } else {
                delete_post_meta( $post_id, $meta_key );
            }
        } else {
            delete_post_meta( $post_id, $meta_key );
        }
    }

    private static function save_services( $post_id ) {
        // 1. Limpiar la relación existente antes de guardar la nueva selección.
        delete_post_meta( $post_id, '_envfor_project_service_id' );

        $service_ids = array();
        if ( isset( $_POST['envfor_project_service_id'] ) ) {
            $service_ids = (array) wp_unslash( $_POST['envfor_project_service_id'] );
        }

        foreach ( $service_ids as $service_id ) {
            $service_id = absint( $service_id );
            if ( ! $service_id ) {
                continue;
            }

            // Validar que el ID apunta realmente a un servicio publicado.
            if ( get_post_type( $service_id ) !== 'envfor_service' ) {
                continue;
            }

            // Una fila de post_meta por servicio. Sin array serializado.
            add_post_meta( $post_id, '_envfor_project_service_id', $service_id, false );
        }
    }

    private static function save_sectors( $post_id ) {
        // Limpiar la relación existente antes de guardar la nueva selección.
        delete_post_meta( $post_id, '_envfor_project_sector_id' );

        $sector_ids = array();
        if ( isset( $_POST['envfor_project_sector_id'] ) ) {
            $sector_ids = (array) wp_unslash( $_POST['envfor_project_sector_id'] );
        }

        foreach ( $sector_ids as $sector_id ) {
            $sector_id = absint( $sector_id );
            if ( ! $sector_id ) {
                continue;
            }

            // Validar que el ID apunta realmente a un giro publicado.
            if ( get_post_type( $sector_id ) !== 'envfor_sector' ) {
                continue;
            }

            // Una fila de post_meta por giro. Sin array serializado.
            add_post_meta( $post_id, '_envfor_project_sector_id', $sector_id, false );
        }
    }


    private static function sanitize_works( $raw ) {
        if ( is_array( $raw ) ) {
            $decoded = $raw;
        } else {
            $decoded = json_decode( (string) $raw, true );
        }

        if ( ! is_array( $decoded ) ) {
            return array();
        }

        $works = array();
        foreach ( $decoded as $work ) {
            $clean = sanitize_text_field( (string) $work );
            if ( '' !== $clean ) {
                $works[] = $clean;
            }
        }

        return $works;
    }

    private static function sanitize_gallery( $raw ) {
        if ( is_array( $raw ) ) {
            $decoded = $raw;
        } else {
            $decoded = json_decode( (string) $raw, true );
        }

        if ( ! is_array( $decoded ) ) {
            return array();
        }

        $gallery = array();
        foreach ( $decoded as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }

            $image_id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
            if ( ! $image_id || ! wp_attachment_is_image( $image_id ) ) {
                continue;
            }

            $caption = isset( $item['caption'] ) ? sanitize_text_field( (string) $item['caption'] ) : '';

            $gallery[] = array(
                'id'      => $image_id,
                'caption' => $caption,
            );
        }

        return $gallery;
    }

    public static function enqueue_admin_scripts( $hook ) {
        global $typenow;
        if ( 'envfor_project' === $typenow && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            wp_enqueue_media();
            wp_enqueue_script(
                'envfor-admin-project-js',
                ENVFOR_HEADLESS_URL . 'assets/js/admin-project.js',
                array( 'jquery' ),
                ENVFOR_HEADLESS_VERSION,
                true
            );
        }
    }
}

