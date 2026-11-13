<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Administración de los Testimoniales del Home en wp-admin.
 * Ubicación: Inicio → Testimoniales
 */
class Envfor_Testimonials_Admin {

    const OPTION_NAME = 'envfor_home_testimonials';

    const INITIAL_TESTIMONIALS = array(
        array(
            'id'        => 'cigsa',
            'company'   => 'CIGSA',
            'quote'     => 'El acompañamiento de ENVFOR nos permitió abordar los requerimientos ambientales del proyecto con claridad, atención oportuna y una visión enfocada en el cumplimiento.',
            'image_id'  => 0,
            'image_url' => '/assets/images/testimoniales/CIGSA.webp',
            'image_alt' => 'CIGSA',
            'visible'   => true,
        ),
        array(
            'id'        => 'entia',
            'company'   => 'ENTIA',
            'quote'     => 'Encontramos en ENVFOR un equipo capaz de entender las necesidades del proyecto y traducirlas en soluciones ambientales prácticas, con comunicación cercana durante todo el proceso.',
            'image_id'  => 0,
            'image_url' => '/assets/images/testimoniales/entia.webp',
            'image_alt' => 'ENTIA',
            'visible'   => true,
        ),
        array(
            'id'        => 'irca',
            'company'   => 'IRCA',
            'quote'     => 'La coordinación y experiencia técnica de ENVFOR facilitaron la ejecución de las actividades ambientales en campo, manteniendo siempre una atención profesional y ordenada.',
            'image_id'  => 0,
            'image_url' => '/assets/images/testimoniales/IRCA.webp',
            'image_alt' => 'IRCA',
            'visible'   => true,
        ),
        array(
            'id'        => 'hr-electro',
            'company'   => 'HR ELECTRO',
            'quote'     => 'ENVFOR nos brindó acompañamiento técnico para integrar los requerimientos ambientales a las necesidades del proyecto, con un enfoque claro y orientado a resultados.',
            'image_id'  => 0,
            'image_url' => '/assets/images/testimoniales/HRElectro.webp',
            'image_alt' => 'HR ELECTRO',
            'visible'   => true,
        ),
    );

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'handle_save' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );
    }

    public static function register_admin_menu() {
        add_submenu_page(
            'envfor-home-about',
            'Testimoniales - Inicio',
            'Testimoniales',
            'edit_pages',
            'envfor-home-testimonials',
            array( __CLASS__, 'render_admin_page' )
        );
    }

    public static function enqueue_admin_scripts( $hook ) {
        if ( false === strpos( $hook, 'envfor-home-testimonials' ) ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script(
            'envfor-admin-testimonials-js',
            ENVFOR_HEADLESS_URL . 'assets/js/admin-testimonials.js',
            array( 'jquery' ),
            ENVFOR_HEADLESS_VERSION,
            true
        );
    }

    public static function handle_save() {
        if ( ! isset( $_POST['envfor_testimonials_submit'] ) ) {
            return;
        }

        if ( ! isset( $_POST['envfor_testimonials_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['envfor_testimonials_nonce'] ) ), 'envfor_testimonials_save' ) ) {
            wp_die( esc_html__( 'Error de validación del formulario.', 'envfor-headless' ) );
        }

        if ( ! current_user_can( 'edit_pages' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'No tienes permisos suficientes para realizar esta acción.', 'envfor-headless' ) );
        }

        $raw_json = isset( $_POST['envfor_testimonials_json'] ) ? wp_unslash( $_POST['envfor_testimonials_json'] ) : '[]';
        $items = json_decode( $raw_json, true );
        if ( ! is_array( $items ) ) {
            $items = array();
        }

        $sanitized = array();
        $validation_error = false;

        foreach ( $items as $idx => $item ) {
            $id = isset( $item['id'] ) && '' !== trim( (string) $item['id'] )
                ? sanitize_key( $item['id'] )
                : 'testimonio_' . ( $idx + 1 ) . '_' . time();

            $company = isset( $item['company'] ) ? sanitize_text_field( $item['company'] ) : '';
            $quote   = isset( $item['quote'] ) ? sanitize_textarea_field( $item['quote'] ) : '';
            $visible = ! empty( $item['visible'] );

            // Validación: para registros visibles, empresa y cita son obligatorias
            if ( $visible && ( '' === trim( $company ) || '' === trim( $quote ) ) ) {
                $visible = false;
                $validation_error = true;
            }

            $image_id = ! empty( $item['image_id'] ) ? absint( $item['image_id'] ) : 0;
            $image_url = '';
            $image_alt = '';

            if ( $image_id > 0 && wp_attachment_is_image( $image_id ) ) {
                $src = wp_get_attachment_image_src( $image_id, 'full' );
                $image_url = $src ? $src[0] : '';
                // ALT propio del adjunto seleccionado. Nunca hereda de la foto anterior.
                $image_alt = (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true );
            } elseif ( isset( $item['image_url'] ) && is_string( $item['image_url'] ) && '' !== trim( $item['image_url'] ) ) {
                // Preservar rutas de assets locales si no es un attachment de WP
                $clean_url = sanitize_text_field( $item['image_url'] );
                if ( 0 === strpos( $clean_url, '/assets/' ) || 0 === strpos( $clean_url, 'http://' ) || 0 === strpos( $clean_url, 'https://' ) ) {
                    $image_url = $clean_url;
                    $image_alt = isset( $item['image_alt'] ) ? sanitize_text_field( $item['image_alt'] ) : $company;
                }
            }

            $sanitized[] = array(
                'id'        => $id,
                'company'   => $company,
                'quote'     => $quote,
                'image_id'  => $image_id,
                'image_url' => $image_url,
                'image_alt' => $image_alt,
                'visible'   => $visible,
            );
        }

        update_option( self::OPTION_NAME, $sanitized );

        $redirect_args = array(
            'page'    => 'envfor-home-testimonials',
            'updated' => '1',
        );
        if ( $validation_error ) {
            $redirect_args['validation_warning'] = '1';
        }

        wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function render_admin_page() {
        if ( ! current_user_can( 'edit_pages' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'No tienes permisos suficientes para acceder a esta página.', 'envfor-headless' ) );
        }

        $saved = get_option( self::OPTION_NAME, null );
        $is_configured = ( null !== $saved && false !== $saved );
        $testimonials = $is_configured && is_array( $saved ) ? $saved : self::INITIAL_TESTIMONIALS;

        // Actualizar URLs de previews si se trata de adjuntos de medios
        foreach ( $testimonials as &$item ) {
            if ( ! empty( $item['image_id'] ) && wp_attachment_is_image( $item['image_id'] ) ) {
                $src = wp_get_attachment_image_src( $item['image_id'], 'medium' );
                if ( $src ) {
                    $item['image_preview'] = $src[0];
                }
                $item['image_alt'] = (string) get_post_meta( $item['image_id'], '_wp_attachment_image_alt', true );
            } elseif ( ! empty( $item['image_url'] ) ) {
                $item['image_preview'] = $item['image_url'];
            } else {
                $item['image_preview'] = '';
            }
        }
        unset( $item );

        $testimonials_json = wp_json_encode( $testimonials );
        ?>
        <div class="wrap" style="max-width: 1000px;">
            <h1>Inicio &rsaquo; Testimoniales</h1>
            <p class="description">
                Administra los testimoniales que se muestran en el Home de ENVFOR. Puedes agregar, editar, reordenar (subir/bajar) y ocultar cada testimonio.
                Para que un testimonio visible se publique, debe tener empresa y cita válidas.
            </p>

            <?php if ( isset( $_GET['updated'] ) && '1' === $_GET['updated'] ) : ?>
                <div class="notice notice-success is-dismissible" style="margin-top: 15px;">
                    <p><strong>Testimoniales guardados correctamente.</strong></p>
                </div>
            <?php endif; ?>

            <?php if ( isset( $_GET['validation_warning'] ) && '1' === $_GET['validation_warning'] ) : ?>
                <div class="notice notice-warning is-dismissible" style="margin-top: 15px;">
                    <p><strong>Nota:</strong> Algunos testimonios marcados como visibles no tenían empresa o cita completa, por lo que se guardaron como ocultos.</p>
                </div>
            <?php endif; ?>

            <form method="post" action="" id="envfor-testimonials-form" style="margin-top: 20px;">
                <?php wp_nonce_field( 'envfor_testimonials_save', 'envfor_testimonials_nonce' ); ?>
                <input type="hidden" name="envfor_testimonials_json" id="envfor_testimonials_json" value="<?php echo esc_attr( $testimonials_json ); ?>">

                <div id="envfor-testimonials-list" data-testimonials="<?php echo esc_attr( $testimonials_json ); ?>">
                    <!-- Renderizado dinámico vía admin-testimonials.js -->
                </div>

                <div style="margin-top: 16px; display: flex; gap: 12px; align-items: center;">
                    <button type="button" class="button button-secondary" id="envfor-add-testimonial-btn">
                        + Añadir testimonio
                    </button>
                    <span class="description">Agrega un nuevo testimonio al final de la lista.</span>
                </div>

                <p class="submit" style="margin-top: 30px; border-top: 1px solid #ccd0d4; padding-top: 18px;">
                    <input type="submit" name="envfor_testimonials_submit" id="submit" class="button button-primary button-large" value="Guardar testimoniales">
                </p>
            </form>
        </div>
        <?php
    }
}
