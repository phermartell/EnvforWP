<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Envfor_Hero_CPT {
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_cpt' ) );
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'save_post', array( __CLASS__, 'save_meta_boxes' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );
    }

    public static function register_cpt() {
        $labels = array(
            'name'               => 'Hero Slides',
            'singular_name'      => 'Hero Slide',
            'menu_name'          => 'Hero Slides',
            'add_new'            => 'Añadir Nuevo',
            'add_new_item'       => 'Añadir Nuevo Slide',
            'edit_item'          => 'Editar Slide',
            'new_item'           => 'Nuevo Slide',
            'view_item'          => 'Ver Slide',
            'search_items'       => 'Buscar Slides',
            'not_found'          => 'No se encontraron slides',
            'not_found_in_trash' => 'No hay slides en la papelera'
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 20,
            'menu_icon'           => 'dashicons-images-alt2',
            'supports'            => array( 'title', 'page-attributes' ),
            'hierarchical'        => false,
            'show_in_rest'        => false, // We use a custom endpoint
        );

        register_post_type( 'envfor_slide', $args );
    }

    public static function add_meta_boxes() {
        add_meta_box(
            'envfor_slide_meta',
            'Configuración del Slide',
            array( __CLASS__, 'render_meta_box' ),
            'envfor_slide',
            'normal',
            'high'
        );
    }

    public static function render_meta_box( $post ) {
        wp_nonce_field( 'envfor_slide_save', 'envfor_slide_nonce' );

        $description = get_post_meta( $post->ID, '_envfor_description', true );
        $image_id = get_post_meta( $post->ID, '_envfor_image_id', true );
        $image_url = $image_id ? wp_get_attachment_url( $image_id ) : '';
        $cta_label = get_post_meta( $post->ID, '_envfor_cta_label', true );
        $cta_url = get_post_meta( $post->ID, '_envfor_cta_url', true );
        $focal_x = get_post_meta( $post->ID, '_envfor_focal_x', true );
        $focal_y = get_post_meta( $post->ID, '_envfor_focal_y', true );
        
        // Defaults (numbers only, 0-100)
        if ( $focal_x === '' ) $focal_x = 50;
        if ( $focal_y === '' ) $focal_y = 50;
        ?>
        <div style="display: flex; flex-direction: column; gap: 15px; padding: 10px 0;">
            <p>
                <label for="envfor_description"><strong>Descripción:</strong></label><br>
                <textarea name="envfor_description" id="envfor_description" rows="4" style="width:100%;"><?php echo esc_textarea( $description ); ?></textarea>
            </p>

            <p>
                <label><strong>Imagen de Fondo:</strong></label><br>
                <input type="hidden" name="envfor_image_id" id="envfor_image_id" value="<?php echo esc_attr( $image_id ); ?>">
                <div id="envfor_image_preview" style="max-width: 300px; margin-bottom: 10px;">
                    <?php if ( $image_url ) : ?>
                        <img src="<?php echo esc_url( $image_url ); ?>" style="max-width: 100%; height: auto; border: 1px solid #ccc;">
                    <?php endif; ?>
                </div>
                <button type="button" class="button" id="envfor_upload_image_btn">Seleccionar Imagen de Biblioteca</button>
                <button type="button" class="button" id="envfor_remove_image_btn" <?php echo empty( $image_id ) ? 'style="display:none;"' : ''; ?>>Quitar Imagen</button>
            </p>

            <p>
                <label for="envfor_cta_label"><strong>Texto del CTA (Botón):</strong></label><br>
                <input type="text" name="envfor_cta_label" id="envfor_cta_label" value="<?php echo esc_attr( $cta_label ); ?>" style="width:100%;">
            </p>

            <p>
                <label for="envfor_cta_url"><strong>URL del CTA:</strong></label><br>
                <input type="text" name="envfor_cta_url" id="envfor_cta_url" value="<?php echo esc_attr( $cta_url ); ?>" style="width:100%;">
            </p>

            <div style="display: flex; gap: 20px;">
                <p style="flex:1;">
                    <label for="envfor_focal_x"><strong>Posición Horizontal (Focal X, de 0 a 100):</strong></label><br>
                    <input type="number" name="envfor_focal_x" id="envfor_focal_x" min="0" max="100" step="1" value="<?php echo esc_attr( $focal_x ); ?>" style="width:100%;">
                </p>
                <p style="flex:1;">
                    <label for="envfor_focal_y"><strong>Posición Vertical (Focal Y, de 0 a 100):</strong></label><br>
                    <input type="number" name="envfor_focal_y" id="envfor_focal_y" min="0" max="100" step="1" value="<?php echo esc_attr( $focal_y ); ?>" style="width:100%;">
                </p>
            </div>
            <p class="description"><strong>Orden:</strong> Para reordenar los slides, utiliza la caja nativa lateral "Atributos de página" y cambia el número de "Orden".</p>
        </div>
        <?php
    }

    public static function save_meta_boxes( $post_id ) {
        if ( ! isset( $_POST['envfor_slide_nonce'] ) || ! wp_verify_nonce( $_POST['envfor_slide_nonce'], 'envfor_slide_save' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['envfor_description'] ) ) {
            update_post_meta( $post_id, '_envfor_description', sanitize_textarea_field( wp_unslash( $_POST['envfor_description'] ) ) );
        }

        if ( isset( $_POST['envfor_image_id'] ) ) {
            $image_id = absint( $_POST['envfor_image_id'] );
            if ( $image_id && wp_attachment_is_image( $image_id ) ) {
                update_post_meta( $post_id, '_envfor_image_id', $image_id );
            } else {
                delete_post_meta( $post_id, '_envfor_image_id' );
            }
        } else {
            delete_post_meta( $post_id, '_envfor_image_id' );
        }

        if ( isset( $_POST['envfor_cta_label'] ) ) {
            update_post_meta( $post_id, '_envfor_cta_label', sanitize_text_field( wp_unslash( $_POST['envfor_cta_label'] ) ) );
        }

        if ( isset( $_POST['envfor_cta_url'] ) ) {
            $url = trim( sanitize_text_field( wp_unslash( $_POST['envfor_cta_url'] ) ) );
            if ( empty( $url ) ) {
                update_post_meta( $post_id, '_envfor_cta_url', '' );
            } elseif ( preg_match( '/^[\/\#\?]/', $url ) ) {
                // Valid internal route
                update_post_meta( $post_id, '_envfor_cta_url', $url );
            } else {
                $parsed = parse_url( $url );
                if ( isset( $parsed['scheme'] ) && in_array( strtolower( $parsed['scheme'] ), array( 'http', 'https', 'mailto', 'tel' ), true ) ) {
                    update_post_meta( $post_id, '_envfor_cta_url', esc_url_raw( $url ) );
                } else {
                    update_post_meta( $post_id, '_envfor_cta_url', '' );
                }
            }
        }

        if ( isset( $_POST['envfor_focal_x'] ) ) {
            $focal_x = absint( $_POST['envfor_focal_x'] );
            $focal_x = max( 0, min( 100, $focal_x ) ); // Ensure between 0 and 100
            update_post_meta( $post_id, '_envfor_focal_x', $focal_x );
        }

        if ( isset( $_POST['envfor_focal_y'] ) ) {
            $focal_y = absint( $_POST['envfor_focal_y'] );
            $focal_y = max( 0, min( 100, $focal_y ) ); // Ensure between 0 and 100
            update_post_meta( $post_id, '_envfor_focal_y', $focal_y );
        }
    }

    public static function enqueue_admin_scripts( $hook ) {
        global $typenow;
        if ( 'envfor_slide' === $typenow && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            wp_enqueue_media();
            wp_enqueue_script(
                'envfor-admin-js',
                ENVFOR_HEADLESS_URL . 'assets/js/admin.js',
                array( 'jquery' ),
                ENVFOR_HEADLESS_VERSION,
                true
            );
        }
    }
}
