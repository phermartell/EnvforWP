<?php
/**
 * Gestión de la base de datos de leads de ENVFOR.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Envfor_Leads_DB {

    const DB_VERSION = '1.0.0';

    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'envfor_leads';
    }

    public static function init() {
        self::check_db_version();
    }

    public static function check_db_version() {
        $installed_ver = get_option( 'envfor_leads_db_version' );
        if ( $installed_ver !== self::DB_VERSION || ! self::verify_table_structure() ) {
            self::migrate();
        }
    }

    public static function verify_table_structure() {
        global $wpdb;
        $table_name = self::get_table_name();

        $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;
        if ( ! $table_exists ) {
            return false;
        }

        $index_results = $wpdb->get_results( "SHOW INDEX FROM `{$table_name}` WHERE Key_name = 'submission_id'" );
        return ! empty( $index_results );
    }

    public static function migrate() {
        global $wpdb;
        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            submission_id varchar(64) NOT NULL,
            payload_hash varchar(64) NOT NULL,
            type varchar(20) NOT NULL,
            form varchar(128) NOT NULL,
            channel varchar(40) NOT NULL,
            name varchar(200) NOT NULL,
            phone varchar(30) NOT NULL,
            email varchar(200) DEFAULT NULL,
            service varchar(100) NOT NULL,
            details text DEFAULT NULL,
            mail_status varchar(20) NOT NULL DEFAULT 'pending',
            mail_error text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            server_timestamp varchar(60) NOT NULL,
            user_agent varchar(512) NOT NULL,
            ip varchar(64) NOT NULL,
            visitor_id varchar(64) NOT NULL,
            page_url varchar(2048) NOT NULL,
            first_touch longtext DEFAULT NULL,
            last_touch longtext DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY submission_id (submission_id),
            KEY type_created (type, created_at),
            KEY channel_created (channel, created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        if ( self::verify_table_structure() ) {
            update_option( 'envfor_leads_db_version', self::DB_VERSION );
        }
    }

    public static function compute_payload_hash( array $data ) {
        $normalized = array(
            'type'    => trim( isset( $data['type'] ) ? (string) $data['type'] : '' ),
            'form'    => trim( isset( $data['form'] ) ? (string) $data['form'] : '' ),
            'channel' => trim( isset( $data['channel'] ) ? (string) $data['channel'] : '' ),
            'name'    => trim( isset( $data['name'] ) ? (string) $data['name'] : '' ),
            'phone'   => preg_replace( '/\D/', '', isset( $data['phone'] ) ? (string) $data['phone'] : '' ),
            'email'   => strtolower( trim( isset( $data['email'] ) ? (string) $data['email'] : '' ) ),
            'service' => trim( isset( $data['service'] ) ? (string) $data['service'] : '' ),
            'details' => trim( isset( $data['details'] ) ? (string) $data['details'] : '' ),
        );

        $json = function_exists( 'wp_json_encode' ) ? wp_json_encode( $normalized ) : json_encode( $normalized );
        return hash( 'sha256', $json );
    }

    public static function insert_or_get_lead( array $data ) {
        global $wpdb;
        $table_name = self::get_table_name();

        if ( ! self::verify_table_structure() ) {
            return array(
                'success' => false,
                'saved'   => false,
                'error'   => 'db_table_missing',
            );
        }

        $submission_id = isset( $data['submission_id'] ) ? (string) $data['submission_id'] : '';
        if ( '' === $submission_id ) {
            $submission_id = wp_generate_uuid4();
        }

        $payload_hash = self::compute_payload_hash( $data );

        // Buscar si el submission_id ya existe
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, submission_id, payload_hash, mail_status FROM `{$table_name}` WHERE submission_id = %s LIMIT 1",
                $submission_id
            )
        );

        if ( $existing ) {
            if ( hash_equals( (string) $existing->payload_hash, $payload_hash ) ) {
                return array(
                    'success'     => true,
                    'saved'       => true,
                    'action'      => 'duplicate',
                    'lead_id'     => (int) $existing->id,
                    'mail_status' => (string) $existing->mail_status,
                );
            }

            return array(
                'success' => false,
                'saved'   => false,
                'error'   => 'submission_id_conflict',
            );
        }

        $first_touch_raw = isset( $data['first_touch'] ) ? $data['first_touch'] : array();
        $last_touch_raw  = isset( $data['last_touch'] ) ? $data['last_touch'] : array();

        $first_touch_json = is_array( $first_touch_raw ) ? wp_json_encode( $first_touch_raw ) : '';
        $last_touch_json  = is_array( $last_touch_raw ) ? wp_json_encode( $last_touch_raw ) : '';

        $insert_data = array(
            'submission_id'    => $submission_id,
            'payload_hash'     => $payload_hash,
            'type'             => isset( $data['type'] ) ? (string) $data['type'] : '',
            'form'             => isset( $data['form'] ) ? (string) $data['form'] : '',
            'channel'          => isset( $data['channel'] ) ? (string) $data['channel'] : '',
            'name'             => isset( $data['name'] ) ? (string) $data['name'] : '',
            'phone'            => isset( $data['phone'] ) ? (string) $data['phone'] : '',
            'email'            => ! empty( $data['email'] ) ? (string) $data['email'] : null,
            'service'          => isset( $data['service'] ) ? (string) $data['service'] : '',
            'details'          => ! empty( $data['details'] ) ? (string) $data['details'] : null,
            'mail_status'      => 'pending',
            'mail_error'       => null,
            'created_at'       => gmdate( 'Y-m-d H:i:s' ),
            'server_timestamp' => isset( $data['server_timestamp'] ) ? (string) $data['server_timestamp'] : '',
            'user_agent'       => isset( $data['user_agent'] ) ? (string) $data['user_agent'] : '',
            'ip'               => isset( $data['ip'] ) ? (string) $data['ip'] : 'no determinada',
            'visitor_id'       => isset( $data['visitor_id'] ) ? (string) $data['visitor_id'] : 'no determinada',
            'page_url'         => isset( $data['page_url'] ) ? (string) $data['page_url'] : '',
            'first_touch'      => $first_touch_json,
            'last_touch'       => $last_touch_json,
        );

        $inserted = $wpdb->insert( $table_name, $insert_data );

        if ( false === $inserted ) {
            // Verificar si fue una carrera de concurrencia
            $recheck = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id, submission_id, payload_hash, mail_status FROM `{$table_name}` WHERE submission_id = %s LIMIT 1",
                    $submission_id
                )
            );

            if ( $recheck ) {
                if ( hash_equals( (string) $recheck->payload_hash, $payload_hash ) ) {
                    return array(
                        'success'     => true,
                        'saved'       => true,
                        'action'      => 'duplicate',
                        'lead_id'     => (int) $recheck->id,
                        'mail_status' => (string) $recheck->mail_status,
                    );
                }

                return array(
                    'success' => false,
                    'saved'   => false,
                    'error'   => 'submission_id_conflict',
                );
            }

            return array(
                'success' => false,
                'saved'   => false,
                'error'   => 'db_insert_failed',
            );
        }

        return array(
            'success'     => true,
            'saved'       => true,
            'action'      => 'inserted',
            'lead_id'     => (int) $wpdb->insert_id,
            'mail_status' => 'pending',
        );
    }

    public static function update_mail_status( $lead_id, $status, $error_msg = null ) {
        global $wpdb;
        $table_name = self::get_table_name();

        $update_data = array( 'mail_status' => (string) $status );
        if ( null !== $error_msg ) {
            $update_data['mail_error'] = (string) $error_msg;
        }

        $wpdb->update(
            $table_name,
            $update_data,
            array( 'id' => (int) $lead_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
    }

    public static function format_local_date( $utc_date_str ) {
        if ( empty( $utc_date_str ) ) {
            return '';
        }

        try {
            $tz_utc   = new DateTimeZone( 'UTC' );
            $tz_local = new DateTimeZone( 'America/Monterrey' );
            $dt       = new DateTimeImmutable( $utc_date_str, $tz_utc );
            return $dt->setTimezone( $tz_local )->format( 'Y-m-d H:i:s' );
        } catch ( Exception $e ) {
            return $utc_date_str;
        }
    }

    public static function get_filtered_query( array $filters ) {
        global $wpdb;
        $table_name = self::get_table_name();

        $where = array( '1=1' );
        $args  = array();

        if ( ! empty( $filters['channel'] ) && 'all' !== $filters['channel'] ) {
            $where[] = 'channel = %s';
            $args[]  = sanitize_text_field( $filters['channel'] );
        }

        $tz_local = new DateTimeZone( 'America/Monterrey' );
        $tz_utc   = new DateTimeZone( 'UTC' );

        if ( ! empty( $filters['date_from'] ) ) {
            try {
                $dt = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $filters['date_from'] . ' 00:00:00', $tz_local );
                if ( $dt ) {
                    $where[] = 'created_at >= %s';
                    $args[]  = $dt->setTimezone( $tz_utc )->format( 'Y-m-d H:i:s' );
                }
            } catch ( Exception $e ) {
                // Si la fecha es inválida se ignora
            }
        }

        if ( ! empty( $filters['date_to'] ) ) {
            try {
                $dt = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $filters['date_to'] . ' 23:59:59', $tz_local );
                if ( $dt ) {
                    $where[] = 'created_at <= %s';
                    $args[]  = $dt->setTimezone( $tz_utc )->format( 'Y-m-d H:i:s' );
                }
            } catch ( Exception $e ) {
                // Si la fecha es inválida se ignora
            }
        }

        $where_sql = implode( ' AND ', $where );
        return array(
            'where_sql' => $where_sql,
            'args'      => $args,
        );
    }

    public static function count_leads( array $filters ) {
        global $wpdb;
        $table_name = self::get_table_name();
        $q          = self::get_filtered_query( $filters );

        $sql = "SELECT COUNT(*) FROM `{$table_name}` WHERE " . $q['where_sql'];
        if ( ! empty( $q['args'] ) ) {
            $sql = $wpdb->prepare( $sql, $q['args'] );
        }

        return (int) $wpdb->get_var( $sql );
    }

    public static function get_leads( array $filters, $limit = 20, $offset = 0 ) {
        global $wpdb;
        $table_name = self::get_table_name();
        $q          = self::get_filtered_query( $filters );

        $sql = "SELECT * FROM `{$table_name}` WHERE " . $q['where_sql'] . " ORDER BY id DESC LIMIT %d OFFSET %d";
        $args = array_merge( $q['args'], array( (int) $limit, (int) $offset ) );

        return $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
    }
}
