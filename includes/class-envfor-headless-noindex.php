<?php
/**
 * Marca el front-end del backend headless (wp.envfor.com.mx) como noindex.
 *
 * Compatible con REST API e imágenes: el filtro `wp_robots` solo emite la
 * etiqueta <meta name="robots"> en las respuestas HTML del front-end, por lo
 * que NO afecta a /wp-json/ (respuestas JSON) ni a los archivos de imagen
 * servidos directamente por el servidor web.
 *
 * Nota: no se usa `Disallow: /` en robots.txt porque eso bloquearía el rastreo
 * sin retirar URLs ya indexadas. noindex/nofollow es la señal correcta.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Envfor_Headless_Noindex {

    public static function init() {
        add_filter( 'wp_robots', array( __CLASS__, 'add_noindex' ), 20 );
    }

    public static function add_noindex( array $robots ) {
        $robots['noindex']  = true;
        $robots['nofollow'] = true;
        return $robots;
    }
}
