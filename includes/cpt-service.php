<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * CPT envfor_service (categorías principales de Servicios).
 * Cada entrada representa UNA categoría principal (no subservicios).
 */
class Envfor_Service_CPT {

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_cpt' ) );
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'save_post', array( __CLASS__, 'save_meta_boxes' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );
    }

    public static function register_cpt() {
        $labels = array(
            'name'               => 'Servicios',
            'singular_name'      => 'Servicio',
            'menu_name'          => 'Servicios',
            'add_new'            => 'Añadir Nuevo',
            'add_new_item'       => 'Añadir Nuevo Servicio',
            'edit_item'          => 'Editar Servicio',
            'new_item'           => 'Nuevo Servicio',
            'view_item'          => 'Ver Servicio',
            'search_items'       => 'Buscar Servicios',
            'not_found'          => 'No se encontraron servicios',
            'not_found_in_trash' => 'No hay servicios en la papelera',
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 21,
            'menu_icon'           => 'dashicons-category',
            'supports'            => array( 'title', 'thumbnail', 'page-attributes' ),
            'hierarchical'        => false,
            'show_in_rest'        => false, // Usamos un endpoint REST personalizado.
        );

        register_post_type( 'envfor_service', $args );
    }

    public static function add_meta_boxes() {
        add_meta_box(
            'envfor_service_meta',
            'Configuración del Servicio',
            array( __CLASS__, 'render_meta_box' ),
            'envfor_service',
            'normal',
            'high'
        );
    }

    public static function render_meta_box( $post ) {
        wp_nonce_field( 'envfor_service_save', 'envfor_service_nonce' );

        $intro       = get_post_meta( $post->ID, '_envfor_service_intro', true );
        $description = get_post_meta( $post->ID, '_envfor_service_description', true );
        $image_id    = absint( get_post_meta( $post->ID, '_envfor_service_image_id', true ) );
        $image_url   = $image_id ? wp_get_attachment_url( $image_id ) : '';
        $home_image_id  = absint( get_post_meta( $post->ID, '_envfor_service_home_image_id', true ) );
        $home_image_url = $home_image_id ? wp_get_attachment_url( $home_image_id ) : '';
        $groups      = get_post_meta( $post->ID, '_envfor_service_groups', true );
        if ( ! is_array( $groups ) ) {
            $groups = array();
        }
        ?>
        <div style="display: flex; flex-direction: column; gap: 15px; padding: 10px 0;">
            <p>
                <label for="envfor_service_intro"><strong>Intro editorial:</strong></label><br>
                <textarea name="envfor_service_intro" id="envfor_service_intro" rows="3" style="width:100%;"><?php echo esc_textarea( $intro ); ?></textarea>
                <span class="description">Texto corto que aparece en la columna izquierda del listado.</span>
            </p>

            <p>
                <label for="envfor_service_description"><strong>Descripción general:</strong></label><br>
                <textarea name="envfor_service_description" id="envfor_service_description" rows="5" style="width:100%;"><?php echo esc_textarea( $description ); ?></textarea>
            </p>

            <p>
                <label><strong>Imagen principal:</strong></label><br>
                <input type="hidden" name="envfor_service_image_id" id="envfor_service_image_id" value="<?php echo esc_attr( $image_id ); ?>">
                <div id="envfor_service_image_preview" style="max-width: 300px; margin-bottom: 10px;">
                    <?php if ( $image_url ) : ?>
                        <img src="<?php echo esc_url( $image_url ); ?>" style="max-width: 100%; height: auto; border: 1px solid #ccc;">
                    <?php endif; ?>
                </div>
                <button type="button" class="button" id="envfor_service_upload_image_btn">Seleccionar imagen</button>
                <button type="button" class="button" id="envfor_service_remove_image_btn" <?php echo $image_id ? '' : 'style="display:none;"'; ?>>Quitar imagen</button>
            </p>

            <p>
                <label><strong>Imagen Home:</strong></label><br>
                <input type="hidden" name="envfor_service_home_image_id" id="envfor_service_home_image_id" value="<?php echo esc_attr( $home_image_id ); ?>">
                <div id="envfor_service_home_image_preview" style="max-width: 300px; margin-bottom: 10px;">
                    <?php if ( $home_image_url ) : ?>
                        <img src="<?php echo esc_url( $home_image_url ); ?>" style="max-width: 100%; height: auto; border: 1px solid #ccc;">
                    <?php endif; ?>
                </div>
                <button type="button" class="button" id="envfor_service_upload_home_image_btn">Seleccionar imagen</button>
                <button type="button" class="button" id="envfor_service_remove_home_image_btn" <?php echo $home_image_id ? '' : 'style="display:none;"'; ?>>Quitar imagen</button>
                <span class="description">Imagen que aparece en el selector de Servicios del Home (independiente de la imagen principal).</span>
            </p>

            <hr>

            <p><strong>Grupos de servicios:</strong></p>
            <div id="envfor-service-groups" data-groups="<?php echo esc_attr( wp_json_encode( $groups ) ); ?>"></div>
            <p>
                <button type="button" class="button" id="envfor-add-group">Agregar grupo</button>
            </p>
            <input type="hidden" name="envfor_service_groups" id="envfor_service_groups" value="">
        </div>
        <?php
    }

    public static function save_meta_boxes( $post_id ) {
        if ( ! isset( $_POST['envfor_service_nonce'] ) || ! wp_verify_nonce( $_POST['envfor_service_nonce'], 'envfor_service_save' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['envfor_service_intro'] ) ) {
            update_post_meta( $post_id, '_envfor_service_intro', sanitize_textarea_field( wp_unslash( $_POST['envfor_service_intro'] ) ) );
        }

        if ( isset( $_POST['envfor_service_description'] ) ) {
            update_post_meta( $post_id, '_envfor_service_description', sanitize_textarea_field( wp_unslash( $_POST['envfor_service_description'] ) ) );
        }

        if ( isset( $_POST['envfor_service_image_id'] ) ) {
            $image_id = absint( $_POST['envfor_service_image_id'] );
            if ( $image_id && wp_attachment_is_image( $image_id ) ) {
                update_post_meta( $post_id, '_envfor_service_image_id', $image_id );
            } else {
                delete_post_meta( $post_id, '_envfor_service_image_id' );
            }
        } else {
            delete_post_meta( $post_id, '_envfor_service_image_id' );
        }

        if ( isset( $_POST['envfor_service_home_image_id'] ) ) {
            $home_image_id = absint( $_POST['envfor_service_home_image_id'] );
            if ( $home_image_id && wp_attachment_is_image( $home_image_id ) ) {
                update_post_meta( $post_id, '_envfor_service_home_image_id', $home_image_id );
            } else {
                delete_post_meta( $post_id, '_envfor_service_home_image_id' );
            }
        } else {
            delete_post_meta( $post_id, '_envfor_service_home_image_id' );
        }

        if ( isset( $_POST['envfor_service_groups'] ) ) {
            $groups = self::sanitize_groups( wp_unslash( $_POST['envfor_service_groups'] ) );
            update_post_meta( $post_id, '_envfor_service_groups', $groups );
        }
    }

    private static function sanitize_groups( $raw ) {
        if ( is_array( $raw ) ) {
            $decoded = $raw;
        } else {
            $decoded = json_decode( (string) $raw, true );
        }

        if ( ! is_array( $decoded ) ) {
            return array();
        }

        $groups = array();
        foreach ( $decoded as $group ) {
            if ( ! is_array( $group ) ) {
                continue;
            }

            $name = isset( $group['name'] ) ? sanitize_text_field( (string) $group['name'] ) : '';

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

    public static function enqueue_admin_scripts( $hook ) {
        global $typenow;
        if ( 'envfor_service' === $typenow && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            wp_enqueue_media();
            wp_enqueue_script(
                'envfor-admin-service-js',
                ENVFOR_HEADLESS_URL . 'assets/js/admin-service.js',
                array( 'jquery' ),
                ENVFOR_HEADLESS_VERSION,
                true
            );
        }
    }
}
