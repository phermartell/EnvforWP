<?php
/**
 * Registro del endpoint REST de Contacto y envío de correo del lead.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Envfor_Contact_Rest {

    const NAMESPACE = 'envfor/v1';
    const ROUTE     = '/contact';

    const ALLOWED_SERVICES = array(
        'Medio ambiente',
        'Energías renovables',
        'Gestión ambiental',
        'Trámites',
    );

    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes() {
        register_rest_route(
            self::NAMESPACE,
            self::ROUTE,
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( __CLASS__, 'handle_contact' ),
                'permission_callback' => array( __CLASS__, 'check_permission' ),
            )
        );
    }

    /**
     * Verifica el secreto compartido mediante el header Authorization: Bearer <secret>.
     */
    public static function check_permission( WP_REST_Request $request ) {
        $secret = self::get_shared_secret();

        if ( '' === $secret ) {
            return new WP_Error(
                'envfor_contact_secret_missing',
                'Secreto compartido no configurado.',
                array( 'status' => 500 )
            );
        }

        $auth = $request->get_header( 'Authorization' );
        if ( empty( $auth ) ) {
            return new WP_Error(
                'envfor_contact_unauthorized',
                'No autorizado.',
                array( 'status' => 401 )
            );
        }

        $expected = 'Bearer ' . $secret;
        if ( ! hash_equals( $expected, $auth ) ) {
            return new WP_Error(
                'envfor_contact_unauthorized',
                'No autorizado.',
                array( 'status' => 401 )
            );
        }

        return true;
    }

    private static function get_shared_secret() {
        return defined( 'ENVFOR_CONTACT_SECRET' ) ? (string) ENVFOR_CONTACT_SECRET : '';
    }

    public static function handle_contact( WP_REST_Request $request ) {
        $submission_id = sanitize_text_field( (string) $request->get_param( 'submissionId' ) );
        $type          = sanitize_text_field( (string) $request->get_param( 'type' ) );
        $name          = sanitize_text_field( (string) $request->get_param( 'name' ) );
        $phone         = sanitize_text_field( (string) $request->get_param( 'phone' ) );
        $email         = sanitize_email( (string) $request->get_param( 'email' ) );
        $service       = sanitize_text_field( (string) $request->get_param( 'service' ) );
        $details       = sanitize_textarea_field( (string) $request->get_param( 'details' ) );
        $recipients    = self::sanitize_recipients( $request->get_param( 'recipients' ) );
        $metadata      = self::sanitize_metadata( $request->get_param( 'metadata' ) );

        if ( ! in_array( $type, array( 'contact', 'whatsapp' ), true ) ) {
            return new WP_Error( 'envfor_contact_invalid_type', 'Tipo no válido.', array( 'status' => 400 ) );
        }

        if ( '' === $name ) {
            return new WP_Error( 'envfor_contact_invalid_name', 'Nombre requerido.', array( 'status' => 400 ) );
        }

        $phone_digits = preg_replace( '/\D/', '', $phone );
        if ( strlen( $phone_digits ) < 10 ) {
            return new WP_Error( 'envfor_contact_invalid_phone', 'Teléfono no válido.', array( 'status' => 400 ) );
        }

        if ( ! in_array( $service, self::ALLOWED_SERVICES, true ) ) {
            return new WP_Error( 'envfor_contact_invalid_service', 'Servicio no válido.', array( 'status' => 400 ) );
        }

        if ( 'contact' === $type && '' === $email ) {
            return new WP_Error( 'envfor_contact_invalid_email', 'Correo no válido.', array( 'status' => 400 ) );
        }

        if ( 'contact' === $type && '' === $details ) {
            return new WP_Error( 'envfor_contact_invalid_details', 'Detalles requeridos.', array( 'status' => 400 ) );
        }

        if ( empty( $recipients ) ) {
            return new WP_Error( 'envfor_contact_no_recipients', 'Destinatarios no válidos.', array( 'status' => 400 ) );
        }

        // Si el cliente no envió submissionId (payload antiguo), generamos uno
        if ( '' === $submission_id ) {
            $submission_id = wp_generate_uuid4();
        }

        $lead_payload = array(
            'submission_id'    => $submission_id,
            'type'             => $type,
            'form'             => isset( $metadata['form'] ) ? $metadata['form'] : 'no determinada',
            'channel'          => isset( $metadata['channel'] ) ? $metadata['channel'] : 'no determinada',
            'name'             => $name,
            'phone'            => $phone,
            'email'            => $email,
            'service'          => $service,
            'details'          => $details,
            'server_timestamp' => isset( $metadata['timestamp'] ) ? $metadata['timestamp'] : '',
            'user_agent'       => isset( $metadata['userAgent'] ) ? $metadata['userAgent'] : '',
            'ip'               => isset( $metadata['ip'] ) ? $metadata['ip'] : 'no determinada',
            'visitor_id'       => isset( $metadata['visitorId'] ) ? $metadata['visitorId'] : 'no determinada',
            'page_url'         => isset( $metadata['pageUrl'] ) ? $metadata['pageUrl'] : '',
            'first_touch'      => isset( $metadata['firstTouch'] ) ? $metadata['firstTouch'] : array(),
            'last_touch'       => isset( $metadata['lastTouch'] ) ? $metadata['lastTouch'] : array(),
        );

        $db_result = Envfor_Leads_DB::insert_or_get_lead( $lead_payload );

        if ( ! $db_result['success'] ) {
            if ( isset( $db_result['error'] ) && 'submission_id_conflict' === $db_result['error'] ) {
                return new WP_Error(
                    'envfor_contact_conflict',
                    'Conflicto en el identificador de envío.',
                    array( 'status' => 409 )
                );
            }

            return new WP_Error(
                'envfor_contact_db_error',
                'No se pudo respaldar el lead en la base de datos.',
                array( 'status' => 500 )
            );
        }

        // Si era un duplicado idéntico ya respaldado en la BD
        if ( 'duplicate' === $db_result['action'] ) {
            return rest_ensure_response( array(
                'success'     => true,
                'saved'       => true,
                'duplicated'  => true,
                'mail_status' => $db_result['mail_status'],
            ) );
        }

        // Si fue una inserción nueva, solo esta petición intenta enviar el correo por wp_mail
        $lead_id = $db_result['lead_id'];

        if ( 'contact' === $type ) {
            $subject = 'Nueva solicitud web — ' . $service;
            $body    = "Nueva solicitud desde envfor.com.mx\n\n"
                . 'Nombre: ' . $name . "\n"
                . 'Teléfono: ' . $phone . "\n"
                . 'Email: ' . $email . "\n"
                . 'Servicio: ' . $service . "\n\n"
                . "Detalles del proyecto:\n" . $details;
        } else {
            $subject = 'Nuevo contacto por WhatsApp — ' . $service;
            $body    = "Un visitante inició el flujo de contacto por WhatsApp.\n\n"
                . 'Nombre: ' . $name . "\n"
                . 'Teléfono: ' . $phone . "\n"
                . 'Servicio: ' . $service . "\n\n"
                . 'Este lead fue registrado antes de redirigir al usuario a WhatsApp.';
        }

        $metadata_block = self::build_metadata_block( $metadata );
        if ( '' !== $metadata_block ) {
            $body .= "\n\n" . $metadata_block;
        }

        $sent = false;
        try {
            $sent = wp_mail( $recipients, $subject, $body );
        } catch ( Exception $e ) {
            $sent = false;
        }

        if ( $sent ) {
            Envfor_Leads_DB::update_mail_status( $lead_id, 'accepted' );
            return rest_ensure_response( array(
                'success'     => true,
                'saved'       => true,
                'mail_status' => 'accepted',
            ) );
        }

        Envfor_Leads_DB::update_mail_status( $lead_id, 'failed', 'mail_failed' );
        return rest_ensure_response( array(
            'success'     => true,
            'saved'       => true,
            'mail_status' => 'failed',
            'warning'     => 'Correo no enviado',
        ) );
    }

    private static function sanitize_metadata( $raw ) {
        if ( ! is_array( $raw ) ) {
            return array();
        }

        return array(
            'timestamp'  => self::sanitize_meta_text( isset( $raw['timestamp'] ) ? $raw['timestamp'] : '', 40 ),
            'userAgent'  => self::sanitize_meta_text( isset( $raw['userAgent'] ) ? $raw['userAgent'] : '', 512 ),
            'ip'         => self::sanitize_meta_text( isset( $raw['ip'] ) ? $raw['ip'] : '', 64 ),
            'visitorId'  => self::sanitize_meta_text( isset( $raw['visitorId'] ) ? $raw['visitorId'] : '', 64 ),
            'recurrence' => self::sanitize_meta_text( isset( $raw['recurrence'] ) ? $raw['recurrence'] : 'no determinada', 32 ),
            'form'       => self::sanitize_meta_text( isset( $raw['form'] ) ? $raw['form'] : '', 128 ),
            'channel'    => self::sanitize_meta_text( isset( $raw['channel'] ) ? $raw['channel'] : '', 40 ),
            'pageUrl'    => self::sanitize_meta_url( isset( $raw['pageUrl'] ) ? $raw['pageUrl'] : '', 2048 ),
            'firstTouch' => self::sanitize_touch( isset( $raw['firstTouch'] ) ? $raw['firstTouch'] : null ),
            'lastTouch'  => self::sanitize_touch( isset( $raw['lastTouch'] ) ? $raw['lastTouch'] : null ),
        );
    }

    private static function sanitize_touch( $raw ) {
        if ( ! is_array( $raw ) ) {
            return array();
        }

        return array(
            'source'      => self::sanitize_meta_text( isset( $raw['source'] ) ? $raw['source'] : '', 200 ),
            'medium'      => self::sanitize_meta_text( isset( $raw['medium'] ) ? $raw['medium'] : '', 100 ),
            'campaign'    => self::sanitize_meta_text( isset( $raw['campaign'] ) ? $raw['campaign'] : '', 200 ),
            'term'        => self::sanitize_meta_text( isset( $raw['term'] ) ? $raw['term'] : '', 200 ),
            'content'     => self::sanitize_meta_text( isset( $raw['content'] ) ? $raw['content'] : '', 200 ),
            'fbclid'      => self::sanitize_meta_text( isset( $raw['fbclid'] ) ? $raw['fbclid'] : '', 300 ),
            'referrer'    => self::sanitize_meta_url( isset( $raw['referrer'] ) ? $raw['referrer'] : '', 2048 ),
            'landingPage' => self::sanitize_meta_url( isset( $raw['landingPage'] ) ? $raw['landingPage'] : '', 2048 ),
        );
    }

    private static function sanitize_meta_text( $value, $max ) {
        if ( ! is_string( $value ) ) {
            return '';
        }

        $value = sanitize_text_field( $value );

        if ( function_exists( 'mb_strlen' ) && mb_strlen( $value ) > $max ) {
            $value = mb_substr( $value, 0, $max );
        } elseif ( strlen( $value ) > $max ) {
            $value = substr( $value, 0, $max );
        }

        return $value;
    }

    private static function sanitize_meta_url( $value, $max ) {
        if ( ! is_string( $value ) ) {
            return '';
        }

        $value = esc_url_raw( $value, array( 'http', 'https' ) );

        if ( '' !== $value && function_exists( 'mb_strlen' ) && mb_strlen( $value ) > $max ) {
            $value = mb_substr( $value, 0, $max );
        } elseif ( '' !== $value && strlen( $value ) > $max ) {
            $value = substr( $value, 0, $max );
        }

        return $value;
    }

    private static function meta_value( $data, $key ) {
        if ( is_array( $data ) && isset( $data[ $key ] ) && is_string( $data[ $key ] ) && '' !== trim( $data[ $key ] ) ) {
            return $data[ $key ];
        }

        return 'no determinada';
    }

    private static function touch_lines( $touch ) {
        $lines   = array();
        $lines[] = '  Fuente: ' . self::meta_value( $touch, 'source' );
        $lines[] = '  Medio: ' . self::meta_value( $touch, 'medium' );
        $lines[] = '  Campaña: ' . self::meta_value( $touch, 'campaign' );
        $lines[] = '  Término: ' . self::meta_value( $touch, 'term' );
        $lines[] = '  Contenido: ' . self::meta_value( $touch, 'content' );
        $lines[] = '  fbclid: ' . self::meta_value( $touch, 'fbclid' );
        $lines[] = '  Referrer: ' . self::meta_value( $touch, 'referrer' );
        $lines[] = '  Página de aterrizaje: ' . self::meta_value( $touch, 'landingPage' );

        return implode( "\n", $lines );
    }

    private static function build_metadata_block( $metadata ) {
        if ( ! is_array( $metadata ) ) {
            $metadata = array();
        }

        $ft = isset( $metadata['firstTouch'] ) && is_array( $metadata['firstTouch'] ) ? $metadata['firstTouch'] : array();
        $lt = isset( $metadata['lastTouch'] ) && is_array( $metadata['lastTouch'] ) ? $metadata['lastTouch'] : array();

        $lines   = array();
        $lines[] = 'Atribución y metadatos';
        $lines[] = '----------------------';
        $lines[] = 'Fecha y hora: ' . self::meta_value( $metadata, 'timestamp' );
        $lines[] = 'User-Agent: ' . self::meta_value( $metadata, 'userAgent' );
        $lines[] = 'IP: ' . self::meta_value( $metadata, 'ip' );
        $lines[] = 'Visitor ID: ' . self::meta_value( $metadata, 'visitorId' );
        $lines[] = 'Recurrencia: ' . self::meta_value( $metadata, 'recurrence' );
        $lines[] = 'Formulario: ' . self::meta_value( $metadata, 'form' );
        $lines[] = 'Canal: ' . self::meta_value( $metadata, 'channel' );
        $lines[] = 'Página de envío: ' . self::meta_value( $metadata, 'pageUrl' );

        $lines[] = '';
        $lines[] = 'Primer contacto (FT):';
        $lines[] = self::touch_lines( $ft );

        $lines[] = '';
        $lines[] = 'Último contacto (LT):';
        $lines[] = self::touch_lines( $lt );

        return implode( "\n", $lines );
    }

    private static function sanitize_recipients( $recipients ) {
        if ( ! is_array( $recipients ) ) {
            return array();
        }

        $valid = array();
        foreach ( $recipients as $recipient ) {
            $email = sanitize_email( (string) $recipient );
            if ( is_email( $email ) ) {
                $valid[] = $email;
            }
        }

        return array_values( array_unique( $valid ) );
    }
}
