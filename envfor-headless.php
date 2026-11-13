<?php
/**
 * Plugin Name: ENVFOR Headless
 * Description: Proporciona la funcionalidad Headless CMS para ENVFOR (Next.js).
 * Version: 1.6.0
 * Author: ENVFOR Consultoría
 * Text Domain: envfor-headless
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define( 'ENVFOR_HEADLESS_VERSION', '1.6.0' );
define( 'ENVFOR_HEADLESS_DIR', plugin_dir_path( __FILE__ ) );
define( 'ENVFOR_HEADLESS_URL', plugin_dir_url( __FILE__ ) );

// Includes
require_once ENVFOR_HEADLESS_DIR . 'includes/cpt-hero.php';
require_once ENVFOR_HEADLESS_DIR . 'includes/rest-api.php';
require_once ENVFOR_HEADLESS_DIR . 'includes/class-envfor-leads-db.php';
require_once ENVFOR_HEADLESS_DIR . 'includes/class-envfor-leads-admin.php';
require_once ENVFOR_HEADLESS_DIR . 'includes/class-envfor-contact-rest.php';
require_once ENVFOR_HEADLESS_DIR . 'includes/class-envfor-headless-noindex.php';
require_once ENVFOR_HEADLESS_DIR . 'includes/cpt-service.php';
require_once ENVFOR_HEADLESS_DIR . 'includes/class-envfor-service-rest.php';
require_once ENVFOR_HEADLESS_DIR . 'includes/cpt-project.php';
require_once ENVFOR_HEADLESS_DIR . 'includes/class-envfor-project-rest.php';
require_once ENVFOR_HEADLESS_DIR . 'includes/cpt-sector.php';
require_once ENVFOR_HEADLESS_DIR . 'includes/class-envfor-sector-rest.php';
require_once ENVFOR_HEADLESS_DIR . 'includes/class-envfor-about-admin.php';
require_once ENVFOR_HEADLESS_DIR . 'includes/class-envfor-about-rest.php';

// Initialize
function envfor_headless_init() {
    Envfor_Hero_CPT::init();
    Envfor_Rest_API::init();
    Envfor_Leads_DB::init();
    Envfor_Leads_Admin::init();
    Envfor_Contact_Rest::init();
    Envfor_Headless_Noindex::init();
    Envfor_Service_CPT::init();
    Envfor_Service_Rest::init();
    Envfor_Project_CPT::init();
    Envfor_Project_Rest::init();
    Envfor_Sector_CPT::init();
    Envfor_Sector_Rest::init();
    Envfor_About_Admin::init();
    Envfor_About_Rest::init();
}
add_action( 'plugins_loaded', 'envfor_headless_init' );
