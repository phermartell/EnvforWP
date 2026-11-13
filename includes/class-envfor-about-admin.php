<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Administración de la sección «Nosotros» del Home en wp-admin.
 * Ubicación: Inicio → Nosotros
 */
class Envfor_About_Admin {

    const OPTION_NAME = 'envfor_home_about';

    const DEFAULTS = array(
        'eyebrow'              => 'SOBRE NOSOTROS',
        'title'                => 'Impulsamos proyectos responsables integrando conocimiento técnico, experiencia y compromiso con el entorno.',
        'description'          => 'Acompañamos a nuestros clientes en la planeación y desarrollo de sus proyectos, aportando soluciones técnicas y estratégicas que responden a sus necesidades y a las particularidades de cada entorno.',
        'feature1_title'       => 'Experiencia y conocimiento',
        'feature1_description' => 'Nuestra experiencia nos permite analizar cada proyecto desde una perspectiva integral, identificando necesidades y desarrollando soluciones sustentadas en conocimiento técnico y criterios ambientales.',
        'feature2_title'       => 'Soluciones a la medida',
        'feature2_description' => 'Cada proyecto presenta retos particulares. Trabajamos de cerca con nuestros clientes para desarrollar estrategias que respondan a sus objetivos y a las características específicas de su entorno.',
        'feature3_title'       => 'Compromiso en cada proyecto',
        'feature3_description' => 'Acompañamos cada etapa con responsabilidad, claridad y atención al detalle, aportando el respaldo técnico necesario para avanzar con confianza.',
        'cta_label'            => 'CONOCE ENVFOR',
        'cta_href'             => '/nosotros',
        'image1_url'           => '/assets/images/about/about-bottom-left.webp',
        'image1_alt'           => 'Técnicos con cascos de seguridad y chalecos reflejantes sembrando un brote de árbol en terreno con vegetación',
        'image2_url'           => '/assets/images/about/about-top-right.webp',
        'image2_alt'           => 'Técnicos de campo con cascos naranjas y chalecos reflejantes realizando labores en zona con cactáceas y matorrales',
    );

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'handle_save' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );
    }

    public static function register_admin_menu() {
        add_menu_page(
            'Inicio - Nosotros',
            'Inicio',
            'edit_pages',
            'envfor-home-about',
            array( __CLASS__, 'render_admin_page' ),
            'dashicons-admin-home',
            19
        );

        add_submenu_page(
            'envfor-home-about',
            'Nosotros',
            'Nosotros',
            'edit_pages',
            'envfor-home-about',
            array( __CLASS__, 'render_admin_page' )
        );
    }

    public static function enqueue_admin_scripts( $hook ) {
        if ( false === strpos( $hook, 'envfor-home-about' ) ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script(
            'envfor-admin-about-js',
            ENVFOR_HEADLESS_URL . 'assets/js/admin-about.js',
            array( 'jquery' ),
            ENVFOR_HEADLESS_VERSION,
            true
        );
    }

    public static function handle_save() {
        if ( ! isset( $_POST['envfor_about_submit'] ) ) {
            return;
        }

        if ( ! isset( $_POST['envfor_about_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['envfor_about_nonce'] ) ), 'envfor_about_save' ) ) {
            wp_die( esc_html__( 'Error de validación del formulario.', 'envfor-headless' ) );
        }

        if ( ! current_user_can( 'edit_pages' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'No tienes permisos suficientes para realizar esta acción.', 'envfor-headless' ) );
        }

        $sanitized = array(
            'eyebrow'              => isset( $_POST['envfor_about_eyebrow'] ) ? sanitize_text_field( wp_unslash( $_POST['envfor_about_eyebrow'] ) ) : '',
            'title'                => isset( $_POST['envfor_about_title'] ) ? sanitize_text_field( wp_unslash( $_POST['envfor_about_title'] ) ) : '',
            'description'          => isset( $_POST['envfor_about_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['envfor_about_description'] ) ) : '',
            'feature1_title'       => isset( $_POST['envfor_about_feature1_title'] ) ? sanitize_text_field( wp_unslash( $_POST['envfor_about_feature1_title'] ) ) : '',
            'feature1_description' => isset( $_POST['envfor_about_feature1_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['envfor_about_feature1_description'] ) ) : '',
            'feature2_title'       => isset( $_POST['envfor_about_feature2_title'] ) ? sanitize_text_field( wp_unslash( $_POST['envfor_about_feature2_title'] ) ) : '',
            'feature2_description' => isset( $_POST['envfor_about_feature2_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['envfor_about_feature2_description'] ) ) : '',
            'feature3_title'       => isset( $_POST['envfor_about_feature3_title'] ) ? sanitize_text_field( wp_unslash( $_POST['envfor_about_feature3_title'] ) ) : '',
            'feature3_description' => isset( $_POST['envfor_about_feature3_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['envfor_about_feature3_description'] ) ) : '',
            'cta_label'            => isset( $_POST['envfor_about_cta_label'] ) ? sanitize_text_field( wp_unslash( $_POST['envfor_about_cta_label'] ) ) : '',
            'image1_id'            => 0,
            'image2_id'            => 0,
        );

        if ( isset( $_POST['envfor_about_image1_id'] ) ) {
            $id1 = absint( $_POST['envfor_about_image1_id'] );
            if ( $id1 > 0 && wp_attachment_is_image( $id1 ) ) {
                $sanitized['image1_id'] = $id1;
            }
        }

        if ( isset( $_POST['envfor_about_image2_id'] ) ) {
            $id2 = absint( $_POST['envfor_about_image2_id'] );
            if ( $id2 > 0 && wp_attachment_is_image( $id2 ) ) {
                $sanitized['image2_id'] = $id2;
            }
        }

        update_option( self::OPTION_NAME, $sanitized );

        wp_safe_redirect( add_query_arg( array(
            'page'    => 'envfor-home-about',
            'updated' => '1',
        ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function render_admin_page() {
        if ( ! current_user_can( 'edit_pages' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'No tienes permisos suficientes para acceder a esta página.', 'envfor-headless' ) );
        }

        $saved = get_option( self::OPTION_NAME, null );

        $eyebrow     = ( isset( $saved['eyebrow'] ) && '' !== $saved['eyebrow'] ) ? $saved['eyebrow'] : self::DEFAULTS['eyebrow'];
        $title       = ( isset( $saved['title'] ) && '' !== $saved['title'] ) ? $saved['title'] : self::DEFAULTS['title'];
        $description = ( isset( $saved['description'] ) && '' !== $saved['description'] ) ? $saved['description'] : self::DEFAULTS['description'];

        $f1_title = ( isset( $saved['feature1_title'] ) && '' !== $saved['feature1_title'] ) ? $saved['feature1_title'] : self::DEFAULTS['feature1_title'];
        $f1_desc  = ( isset( $saved['feature1_description'] ) && '' !== $saved['feature1_description'] ) ? $saved['feature1_description'] : self::DEFAULTS['feature1_description'];

        $f2_title = ( isset( $saved['feature2_title'] ) && '' !== $saved['feature2_title'] ) ? $saved['feature2_title'] : self::DEFAULTS['feature2_title'];
        $f2_desc  = ( isset( $saved['feature2_description'] ) && '' !== $saved['feature2_description'] ) ? $saved['feature2_description'] : self::DEFAULTS['feature2_description'];

        $f3_title = ( isset( $saved['feature3_title'] ) && '' !== $saved['feature3_title'] ) ? $saved['feature3_title'] : self::DEFAULTS['feature3_title'];
        $f3_desc  = ( isset( $saved['feature3_description'] ) && '' !== $saved['feature3_description'] ) ? $saved['feature3_description'] : self::DEFAULTS['feature3_description'];

        $cta_label = ( isset( $saved['cta_label'] ) && '' !== $saved['cta_label'] ) ? $saved['cta_label'] : self::DEFAULTS['cta_label'];

        $image1_id  = ! empty( $saved['image1_id'] ) ? absint( $saved['image1_id'] ) : 0;
        $image1_url = '';
        $image1_alt = '';
        if ( $image1_id && wp_attachment_is_image( $image1_id ) ) {
            $src = wp_get_attachment_image_src( $image1_id, 'medium' );
            $image1_url = $src ? $src[0] : '';
            $image1_alt = (string) get_post_meta( $image1_id, '_wp_attachment_image_alt', true );
        }

        $image2_id  = ! empty( $saved['image2_id'] ) ? absint( $saved['image2_id'] ) : 0;
        $image2_url = '';
        $image2_alt = '';
        if ( $image2_id && wp_attachment_is_image( $image2_id ) ) {
            $src = wp_get_attachment_image_src( $image2_id, 'medium' );
            $image2_url = $src ? $src[0] : '';
            $image2_alt = (string) get_post_meta( $image2_id, '_wp_attachment_image_alt', true );
        }
        ?>
        <div class="wrap" style="max-width: 960px;">
            <h1>Inicio &rsaquo; Nosotros</h1>
            <p class="description">Edita los textos y las imágenes de la sección «Nosotros» del Home de ENVFOR. Si dejas algún campo vacío o restableces una foto, se utilizará el contenido predeterminado del sitio.</p>

            <?php if ( isset( $_GET['updated'] ) && '1' === $_GET['updated'] ) : ?>
                <div class="notice notice-success is-dismissible" style="margin-top: 15px;">
                    <p><strong>Configuración de la sección «Nosotros» guardada correctamente.</strong></p>
                </div>
            <?php endif; ?>

            <form method="post" action="" style="margin-top: 20px;">
                <?php wp_nonce_field( 'envfor_about_save', 'envfor_about_nonce' ); ?>

                <!-- SECCIÓN: Encabezado y textos principales -->
                <div class="postbox" style="padding: 20px; margin-bottom: 24px;">
                    <h2 style="font-size: 1.3em; margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #ccd0d4;">1. Encabezado y Textos Principales</h2>

                    <p style="margin-bottom: 18px;">
                        <label for="envfor_about_eyebrow"><strong>Subtítulo superior (Eyebrow):</strong></label><br>
                        <input type="text" name="envfor_about_eyebrow" id="envfor_about_eyebrow" value="<?php echo esc_attr( $eyebrow ); ?>" style="width: 100%; max-width: 500px;" placeholder="<?php echo esc_attr( self::DEFAULTS['eyebrow'] ); ?>">
                        <br><span class="description">Texto en mayúsculas sobre la línea azul (ej. SOBRE NOSOTROS).</span>
                    </p>

                    <p style="margin-bottom: 18px;">
                        <label for="envfor_about_title"><strong>Título principal de la sección (H2):</strong></label><br>
                        <textarea name="envfor_about_title" id="envfor_about_title" rows="3" style="width: 100%;" placeholder="<?php echo esc_attr( self::DEFAULTS['title'] ); ?>"><?php echo esc_textarea( $title ); ?></textarea>
                        <br><span class="description">Mensaje principal en tipografía grande destacada.</span>
                    </p>

                    <p style="margin-bottom: 10px;">
                        <label for="envfor_about_description"><strong>Párrafo descriptivo de introducción:</strong></label><br>
                        <textarea name="envfor_about_description" id="envfor_about_description" rows="4" style="width: 100%;" placeholder="<?php echo esc_attr( self::DEFAULTS['description'] ); ?>"><?php echo esc_textarea( $description ); ?></textarea>
                        <br><span class="description">Párrafo que explica el acompañamiento integral a clientes.</span>
                    </p>
                </div>

                <!-- SECCIÓN: Pilares / Puntos clave -->
                <div class="postbox" style="padding: 20px; margin-bottom: 24px;">
                    <h2 style="font-size: 1.3em; margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #ccd0d4;">2. Pilares de Valor (3 Puntos Destacados)</h2>

                    <!-- Pilar 1 -->
                    <div style="background: #f9f9f9; padding: 15px; border: 1px solid #e5e5e5; margin-bottom: 16px;">
                        <h3 style="margin-top: 0;">Pilar 1</h3>
                        <p>
                            <label for="envfor_about_feature1_title"><strong>Título:</strong></label><br>
                            <input type="text" name="envfor_about_feature1_title" id="envfor_about_feature1_title" value="<?php echo esc_attr( $f1_title ); ?>" style="width: 100%; max-width: 500px;">
                        </p>
                        <p style="margin-bottom: 0;">
                            <label for="envfor_about_feature1_description"><strong>Descripción:</strong></label><br>
                            <textarea name="envfor_about_feature1_description" id="envfor_about_feature1_description" rows="3" style="width: 100%;"><?php echo esc_textarea( $f1_desc ); ?></textarea>
                        </p>
                    </div>

                    <!-- Pilar 2 -->
                    <div style="background: #f9f9f9; padding: 15px; border: 1px solid #e5e5e5; margin-bottom: 16px;">
                        <h3 style="margin-top: 0;">Pilar 2</h3>
                        <p>
                            <label for="envfor_about_feature2_title"><strong>Título:</strong></label><br>
                            <input type="text" name="envfor_about_feature2_title" id="envfor_about_feature2_title" value="<?php echo esc_attr( $f2_title ); ?>" style="width: 100%; max-width: 500px;">
                        </p>
                        <p style="margin-bottom: 0;">
                            <label for="envfor_about_feature2_description"><strong>Descripción:</strong></label><br>
                            <textarea name="envfor_about_feature2_description" id="envfor_about_feature2_description" rows="3" style="width: 100%;"><?php echo esc_textarea( $f2_desc ); ?></textarea>
                        </p>
                    </div>

                    <!-- Pilar 3 -->
                    <div style="background: #f9f9f9; padding: 15px; border: 1px solid #e5e5e5; margin-bottom: 0;">
                        <h3 style="margin-top: 0;">Pilar 3</h3>
                        <p>
                            <label for="envfor_about_feature3_title"><strong>Título:</strong></label><br>
                            <input type="text" name="envfor_about_feature3_title" id="envfor_about_feature3_title" value="<?php echo esc_attr( $f3_title ); ?>" style="width: 100%; max-width: 500px;">
                        </p>
                        <p style="margin-bottom: 0;">
                            <label for="envfor_about_feature3_description"><strong>Descripción:</strong></label><br>
                            <textarea name="envfor_about_feature3_description" id="envfor_about_feature3_description" rows="3" style="width: 100%;"><?php echo esc_textarea( $f3_desc ); ?></textarea>
                        </p>
                    </div>
                </div>

                <!-- SECCIÓN: Botón de Llamado a la Acción (CTA) -->
                <div class="postbox" style="padding: 20px; margin-bottom: 24px;">
                    <h2 style="font-size: 1.3em; margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #ccd0d4;">3. Botón de Enlace (CTA)</h2>
                    <p style="margin-bottom: 12px;">
                        <label for="envfor_about_cta_label"><strong>Texto de la etiqueta del botón:</strong></label><br>
                        <input type="text" name="envfor_about_cta_label" id="envfor_about_cta_label" value="<?php echo esc_attr( $cta_label ); ?>" style="width: 100%; max-width: 400px;" placeholder="<?php echo esc_attr( self::DEFAULTS['cta_label'] ); ?>">
                    </p>
                    <p style="margin-bottom: 0; color: #555;">
                        <strong>Destino del botón:</strong> <code>/nosotros</code> (fijo hacia la página institucional de Nosotros).
                    </p>
                </div>

                <!-- SECCIÓN: Imágenes -->
                <div class="postbox" style="padding: 20px; margin-bottom: 24px;">
                    <h2 style="font-size: 1.3em; margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #ccd0d4;">4. Fotografías de la Sección</h2>
                    <p class="description" style="margin-bottom: 20px;">Cada posición utiliza una sola imagen que se adapta a las vistas de escritorio y móvil. El texto alternativo (ALT) se obtiene automáticamente del archivo seleccionado en la Biblioteca de medios.</p>

                    <!-- Imagen 1: Inferior izquierda en escritorio / Primera en móvil -->
                    <div style="background: #f9f9f9; padding: 18px; border: 1px solid #e5e5e5; margin-bottom: 20px;">
                        <h3 style="margin-top: 0;">Foto 1: Inferior Izquierda (Escritorio) / Primera Foto (Móvil)</h3>
                        <p class="description" style="margin-top: 0;">Reemplaza la fotografía de la columna izquierda (actualmente: técnicos sembrando brote de árbol).</p>

                        <input type="hidden" name="envfor_about_image1_id" id="envfor_about_image1_id" value="<?php echo esc_attr( $image1_id ); ?>">

                        <div id="envfor_about_image1_preview" style="margin: 12px 0;">
                            <?php if ( $image1_url ) : ?>
                                <img src="<?php echo esc_url( $image1_url ); ?>" style="max-width: 280px; height: auto; display: block; border: 1px solid #ccd0d4; border-radius: 2px;">
                            <?php else : ?>
                                <p style="font-style: italic; color: #666; margin: 4px 0;">(Actualmente usando imagen predeterminada del sitio)</p>
                            <?php endif; ?>
                        </div>

                        <div id="envfor_about_image1_alt_box" style="margin-bottom: 12px; font-size: 0.9em; color: #444;">
                            <?php if ( $image1_url ) : ?>
                                <strong>Texto alternativo (ALT):</strong> <span id="envfor_about_image1_alt_text"><?php echo $image1_alt !== '' ? esc_html( $image1_alt ) : '<em>(Sin texto alternativo configurado en medios)</em>'; ?></span>
                            <?php else : ?>
                                <strong>Texto ALT predeterminado:</strong> <span id="envfor_about_image1_alt_text"><?php echo esc_html( self::DEFAULTS['image1_alt'] ); ?></span>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; gap: 10px; align-items: center;">
                            <button type="button" class="button button-secondary" id="envfor_about_image1_btn">Seleccionar imagen de Biblioteca</button>
                            <button type="button" class="button" id="envfor_about_image1_remove" <?php echo empty( $image1_id ) ? 'style="display:none;"' : ''; ?>>Restablecer a imagen predeterminada</button>
                        </div>
                    </div>

                    <!-- Imagen 2: Superior derecha en escritorio / Segunda en móvil -->
                    <div style="background: #f9f9f9; padding: 18px; border: 1px solid #e5e5e5; margin-bottom: 0;">
                        <h3 style="margin-top: 0;">Foto 2: Superior Derecha (Escritorio) / Segunda Foto (Móvil)</h3>
                        <p class="description" style="margin-top: 0;">Reemplaza la fotografía de la columna derecha (actualmente: técnicos en zona de matorral con cactáceas).</p>

                        <input type="hidden" name="envfor_about_image2_id" id="envfor_about_image2_id" value="<?php echo esc_attr( $image2_id ); ?>">

                        <div id="envfor_about_image2_preview" style="margin: 12px 0;">
                            <?php if ( $image2_url ) : ?>
                                <img src="<?php echo esc_url( $image2_url ); ?>" style="max-width: 280px; height: auto; display: block; border: 1px solid #ccd0d4; border-radius: 2px;">
                            <?php else : ?>
                                <p style="font-style: italic; color: #666; margin: 4px 0;">(Actualmente usando imagen predeterminada del sitio)</p>
                            <?php endif; ?>
                        </div>

                        <div id="envfor_about_image2_alt_box" style="margin-bottom: 12px; font-size: 0.9em; color: #444;">
                            <?php if ( $image2_url ) : ?>
                                <strong>Texto alternativo (ALT):</strong> <span id="envfor_about_image2_alt_text"><?php echo $image2_alt !== '' ? esc_html( $image2_alt ) : '<em>(Sin texto alternativo configurado en medios)</em>'; ?></span>
                            <?php else : ?>
                                <strong>Texto ALT predeterminado:</strong> <span id="envfor_about_image2_alt_text"><?php echo esc_html( self::DEFAULTS['image2_alt'] ); ?></span>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; gap: 10px; align-items: center;">
                            <button type="button" class="button button-secondary" id="envfor_about_image2_btn">Seleccionar imagen de Biblioteca</button>
                            <button type="button" class="button" id="envfor_about_image2_remove" <?php echo empty( $image2_id ) ? 'style="display:none;"' : ''; ?>>Restablecer a imagen predeterminada</button>
                        </div>
                    </div>
                </div>

                <p class="submit" style="margin-top: 24px;">
                    <input type="submit" name="envfor_about_submit" id="submit" class="button button-primary button-large" value="Guardar cambios de Nosotros">
                </p>
            </form>
        </div>
        <?php
    }
}
