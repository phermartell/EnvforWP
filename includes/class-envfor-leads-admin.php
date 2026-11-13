<?php
/**
 * Pantalla de administración y exportación CSV de leads en wp-admin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Envfor_Leads_Admin {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
        add_action( 'admin_post_envfor_export_leads', array( __CLASS__, 'handle_csv_export' ) );
    }

    public static function register_admin_menu() {
        add_menu_page(
            'Leads ENVFOR',
            'Leads ENVFOR',
            'manage_options',
            'envfor-leads',
            array( __CLASS__, 'render_page' ),
            'dashicons-email-alt',
            26
        );
    }

    public static function sanitize_csv_field( $value ) {
        if ( null === $value || '' === $value ) {
            return '';
        }
        $str = (string) $value;
        // Neutraliza fórmulas si el primer carácter (tras espacios o controles) es =, +, -, @, tab o CR
        if ( preg_match( '/^[\s\x00-\x1F]*[=\+\-@\t\r]/', $str ) ) {
            return "'" . $str;
        }
        return $str;
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'No tienes permisos suficientes para acceder a esta página.', 'envfor-headless' ) );
        }

        $date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
        $date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';
        $channel   = isset( $_GET['channel'] ) ? sanitize_text_field( wp_unslash( $_GET['channel'] ) ) : '';
        $paged     = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;

        $filters = array(
            'date_from' => $date_from,
            'date_to'   => $date_to,
            'channel'   => $channel,
        );

        $limit       = 20;
        $offset      = ( $paged - 1 ) * $limit;
        $total_leads = Envfor_Leads_DB::count_leads( $filters );
        $leads       = Envfor_Leads_DB::get_leads( $filters, $limit, $offset );
        $total_pages = ceil( $total_leads / $limit );

        $view_id = isset( $_GET['view_id'] ) ? intval( $_GET['view_id'] ) : 0;
        $selected_lead = null;

        if ( $view_id > 0 ) {
            foreach ( $leads as $lead ) {
                if ( intval( $lead->id ) === $view_id ) {
                    $selected_lead = $lead;
                    break;
                }
            }
            if ( ! $selected_lead && ! empty( $leads ) ) {
                global $wpdb;
                $table_name = Envfor_Leads_DB::get_table_name();
                $selected_lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table_name}` WHERE id = %d", $view_id ) );
            }
        }

        $export_url = add_query_arg(
            array(
                'action'       => 'envfor_export_leads',
                'date_from'    => $date_from,
                'date_to'      => $date_to,
                'channel'      => $channel,
                'envfor_nonce' => wp_create_nonce( 'envfor_export_leads' ),
            ),
            admin_url( 'admin-post.php' )
        );

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Leads ENVFOR</h1>
            <hr class="wp-header-end">

            <!-- Filtros y Exportación -->
            <form method="get" style="margin-top: 15px; margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                <input type="hidden" name="page" value="envfor-leads">

                <div>
                    <label for="date_from" style="display: block; font-weight: 600; margin-bottom: 4px;">Desde:</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr( $date_from ); ?>">
                </div>

                <div>
                    <label for="date_to" style="display: block; font-weight: 600; margin-bottom: 4px;">Hasta:</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr( $date_to ); ?>">
                </div>

                <div>
                    <label for="channel" style="display: block; font-weight: 600; margin-bottom: 4px;">Canal:</label>
                    <select id="channel" name="channel">
                        <option value="">Todos los canales</option>
                        <option value="contacto" <?php selected( $channel, 'contacto' ); ?>>Contacto</option>
                        <option value="whatsapp" <?php selected( $channel, 'whatsapp' ); ?>>WhatsApp</option>
                    </select>
                </div>

                <div>
                    <button type="submit" class="button button-secondary">Filtrar</button>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=envfor-leads' ) ); ?>" class="button button-link">Limpiar</a>
                </div>

                <div style="margin-left: auto;">
                    <a href="<?php echo esc_url( $export_url ); ?>" class="button button-primary">Exportar CSV filtrado</a>
                </div>
            </form>

            <?php if ( $selected_lead ) : ?>
                <!-- Panel de Detalle del Lead -->
                <div class="card" style="max-width: 100%; margin-bottom: 20px; padding: 20px; border-left: 4px solid #007cba;">
                    <h2>Detalle del Lead #<?php echo esc_html( $selected_lead->id ); ?> (<?php echo esc_html( $selected_lead->submission_id ); ?>)</h2>
                    <p><strong>Fecha (America/Monterrey):</strong> <?php echo esc_html( Envfor_Leads_DB::format_local_date( $selected_lead->created_at ) ); ?></p>
                    <p><strong>Nombre:</strong> <?php echo esc_html( $selected_lead->name ); ?></p>
                    <p><strong>Teléfono:</strong> <?php echo esc_html( $selected_lead->phone ); ?></p>
                    <p><strong>Correo:</strong> <?php echo esc_html( $selected_lead->email ? $selected_lead->email : 'N/A' ); ?></p>
                    <p><strong>Servicio:</strong> <?php echo esc_html( $selected_lead->service ); ?></p>
                    <p><strong>Canal / Formulario:</strong> <?php echo esc_html( $selected_lead->channel ); ?> / <?php echo esc_html( $selected_lead->form ); ?></p>
                    <p><strong>Estado del Correo:</strong> <?php echo esc_html( $selected_lead->mail_status ); ?> <?php echo $selected_lead->mail_error ? '(' . esc_html( $selected_lead->mail_error ) . ')' : ''; ?></p>
                    <p><strong>IP:</strong> <?php echo esc_html( $selected_lead->ip ); ?> | <strong>Visitor ID:</strong> <?php echo esc_html( $selected_lead->visitor_id ); ?></p>
                    <p><strong>Página de envío:</strong> <a href="<?php echo esc_url( $selected_lead->page_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $selected_lead->page_url ); ?></a></p>

                    <?php if ( ! empty( $selected_lead->details ) ) : ?>
                        <div style="background: #f6f7f7; padding: 12px; border: 1px solid #c3c4c7; margin-top: 10px;">
                            <strong>Detalles del Proyecto:</strong>
                            <p style="white-space: pre-wrap; margin-top: 5px;"><?php echo esc_html( $selected_lead->details ); ?></p>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; gap: 20px; margin-top: 15px;">
                        <div style="flex: 1; background: #fff; border: 1px solid #c3c4c7; padding: 10px;">
                            <h4 style="margin-top:0;">Primer Contacto (FT)</h4>
                            <pre style="font-size: 11px; white-space: pre-wrap; margin:0;"><?php echo esc_html( print_r( json_decode( $selected_lead->first_touch, true ), true ) ); ?></pre>
                        </div>
                        <div style="flex: 1; background: #fff; border: 1px solid #c3c4c7; padding: 10px;">
                            <h4 style="margin-top:0;">Último Contacto (LT)</h4>
                            <pre style="font-size: 11px; white-space: pre-wrap; margin:0;"><?php echo esc_html( print_r( json_decode( $selected_lead->last_touch, true ), true ) ); ?></pre>
                        </div>
                    </div>

                    <p style="margin-top: 15px;">
                        <a href="<?php echo esc_url( remove_query_arg( 'view_id' ) ); ?>" class="button button-secondary">Cerrar detalle</a>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Tabla principal -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th style="width: 170px;">Fecha (Monterrey)</th>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Servicio</th>
                        <th style="width: 100px;">Canal</th>
                        <th style="width: 100px;">Estado Correo</th>
                        <th style="width: 80px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $leads ) ) : ?>
                        <tr>
                            <td colspan="9">No se encontraron leads registrados.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $leads as $lead ) : ?>
                            <tr>
                                <td><?php echo esc_html( $lead->id ); ?></td>
                                <td><?php echo esc_html( Envfor_Leads_DB::format_local_date( $lead->created_at ) ); ?></td>
                                <td><strong><?php echo esc_html( $lead->name ); ?></strong></td>
                                <td><?php echo esc_html( $lead->phone ); ?></td>
                                <td><?php echo esc_html( $lead->email ? $lead->email : '—' ); ?></td>
                                <td><?php echo esc_html( $lead->service ); ?></td>
                                <td><span class="post-state"><?php echo esc_html( $lead->channel ); ?></span></td>
                                <td>
                                    <?php
                                    $status_class = 'accepted' === $lead->mail_status ? 'notice-success' : ( 'failed' === $lead->mail_status ? 'notice-error' : 'notice-warning' );
                                    ?>
                                    <span class="inline-notice <?php echo esc_attr( $status_class ); ?>" style="padding: 2px 6px; border-radius: 3px; font-size: 11px;">
                                        <?php echo esc_html( $lead->mail_status ); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url( add_query_arg( 'view_id', $lead->id ) ); ?>" class="button button-small">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Paginación -->
            <?php if ( $total_pages > 1 ) : ?>
                <div class="tablenav" style="margin-top: 15px;">
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo esc_html( $total_leads ); ?> elementos</span>
                        <?php
                        echo paginate_links( array(
                            'base'      => add_query_arg( 'paged', '%#%' ),
                            'format'    => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total'     => $total_pages,
                            'current'   => $paged,
                        ) );
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function handle_csv_export() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'No tienes permisos suficientes.', 'envfor-headless' ) );
        }

        check_admin_referer( 'envfor_export_leads', 'envfor_nonce' );

        $date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
        $date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';
        $channel   = isset( $_GET['channel'] ) ? sanitize_text_field( wp_unslash( $_GET['channel'] ) ) : '';

        $filters = array(
            'date_from' => $date_from,
            'date_to'   => $date_to,
            'channel'   => $channel,
        );

        $filename = 'leads-envfor-' . gmdate( 'Y-m-d' ) . '.csv';

        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $output = fopen( 'php://output', 'w' );

        // BOM para compatibilidad UTF-8 en Excel
        fwrite( $output, "\xEF\xBB\xBF" );

        // Cabecera CSV
        fputcsv( $output, array(
            'ID',
            'Submission ID',
            'Fecha (America/Monterrey)',
            'Tipo',
            'Formulario',
            'Canal',
            'Nombre',
            'Teléfono',
            'Correo',
            'Servicio',
            'Detalles',
            'Estado Correo',
            'Error Correo',
            'IP',
            'User Agent',
            'Visitor ID',
            'Página de Envío',
            'FT Fuente',
            'FT Medio',
            'FT Campaña',
            'FT Término',
            'FT Contenido',
            'FT Referrer',
            'FT Landing',
            'LT Fuente',
            'LT Medio',
            'LT Campaña',
            'LT Término',
            'LT Contenido',
            'LT Referrer',
            'LT Landing',
        ) );

        $batch_size = 500;
        $offset     = 0;

        while ( true ) {
            $leads = Envfor_Leads_DB::get_leads( $filters, $batch_size, $offset );
            if ( empty( $leads ) ) {
                break;
            }

            foreach ( $leads as $lead ) {
                $ft = is_string( $lead->first_touch ) ? json_decode( $lead->first_touch, true ) : array();
                $lt = is_string( $lead->last_touch ) ? json_decode( $lead->last_touch, true ) : array();

                if ( ! is_array( $ft ) ) {
                    $ft = array();
                }
                if ( ! is_array( $lt ) ) {
                    $lt = array();
                }

                $row = array(
                    $lead->id,
                    self::sanitize_csv_field( $lead->submission_id ),
                    Envfor_Leads_DB::format_local_date( $lead->created_at ),
                    self::sanitize_csv_field( $lead->type ),
                    self::sanitize_csv_field( $lead->form ),
                    self::sanitize_csv_field( $lead->channel ),
                    self::sanitize_csv_field( $lead->name ),
                    self::sanitize_csv_field( $lead->phone ),
                    self::sanitize_csv_field( $lead->email ),
                    self::sanitize_csv_field( $lead->service ),
                    self::sanitize_csv_field( $lead->details ),
                    self::sanitize_csv_field( $lead->mail_status ),
                    self::sanitize_csv_field( $lead->mail_error ),
                    self::sanitize_csv_field( $lead->ip ),
                    self::sanitize_csv_field( $lead->user_agent ),
                    self::sanitize_csv_field( $lead->visitor_id ),
                    self::sanitize_csv_field( $lead->page_url ),
                    self::sanitize_csv_field( isset( $ft['source'] ) ? $ft['source'] : '' ),
                    self::sanitize_csv_field( isset( $ft['medium'] ) ? $ft['medium'] : '' ),
                    self::sanitize_csv_field( isset( $ft['campaign'] ) ? $ft['campaign'] : '' ),
                    self::sanitize_csv_field( isset( $ft['term'] ) ? $ft['term'] : '' ),
                    self::sanitize_csv_field( isset( $ft['content'] ) ? $ft['content'] : '' ),
                    self::sanitize_csv_field( isset( $ft['referrer'] ) ? $ft['referrer'] : '' ),
                    self::sanitize_csv_field( isset( $ft['landingPage'] ) ? $ft['landingPage'] : '' ),
                    self::sanitize_csv_field( isset( $lt['source'] ) ? $lt['source'] : '' ),
                    self::sanitize_csv_field( isset( $lt['medium'] ) ? $lt['medium'] : '' ),
                    self::sanitize_csv_field( isset( $lt['campaign'] ) ? $lt['campaign'] : '' ),
                    self::sanitize_csv_field( isset( $lt['term'] ) ? $lt['term'] : '' ),
                    self::sanitize_csv_field( isset( $lt['content'] ) ? $lt['content'] : '' ),
                    self::sanitize_csv_field( isset( $lt['referrer'] ) ? $lt['referrer'] : '' ),
                    self::sanitize_csv_field( isset( $lt['landingPage'] ) ? $lt['landingPage'] : '' ),
                );

                fputcsv( $output, $row );
            }

            $offset += $batch_size;
        }

        fclose( $output );
        exit;
    }
}
