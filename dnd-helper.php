<?php
/**
 * Plugin Name:       D&D Helper
 * Plugin URI:        # (Optional: Deine Website)
 * Description:       Ein Plugin zur Unterstützung von Dungeons & Dragons Spielen, inkl. Charakterverwaltung und Würfel-Chat.
 * Version:           0.1.0
 * Author:            Dein Name & KI-Assistent
 * Author URI:        # (Optional: Deine Website)
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dnd-helper
 * Domain Path:       /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define Plugin Constants
define( 'DND_HELPER_VERSION', '0.1.0' );
define( 'DND_HELPER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
// define( 'DND_HELPER_PLUGIN_URL', plugin_dir_url( __FILE__ ) ); // Wird später nützlich sein

/**
 * Lädt die notwendigen Plugin-Dateien.
 */
function dnd_helper_load_includes() {
    require_once DND_HELPER_PLUGIN_DIR . 'includes/cpt-character.php';
    require_once DND_HELPER_PLUGIN_DIR . 'includes/cpt-campaign.php';
    require_once DND_HELPER_PLUGIN_DIR . 'includes/cpt-session.php';
    require_once DND_HELPER_PLUGIN_DIR . 'includes/cpt-mitspieler.php';
    require_once DND_HELPER_PLUGIN_DIR . 'includes/meta-boxes.php';
    require_once DND_HELPER_PLUGIN_DIR . 'includes/shortcodes.php';
    require_once DND_HELPER_PLUGIN_DIR . 'includes/ajax-handlers.php';
	require_once DND_HELPER_PLUGIN_DIR . 'includes/template-tags.php';
    require_once DND_HELPER_PLUGIN_DIR . 'includes/session-api.php';
    require_once DND_HELPER_PLUGIN_DIR . 'includes/session-analysis.php';
	    // Nur im Admin-Bereich laden:
    if ( is_admin() ) {
        require_once DND_HELPER_PLUGIN_DIR . 'includes/admin-ui.php';
    }
}
add_action( 'plugins_loaded', 'dnd_helper_load_includes' );

/**
 * Erstellt oder aktualisiert die Datenbanktabelle für Chatnachrichten.
 */
function dnd_helper_create_chat_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'dnd_chat_messages'; // z.B. wp_dnd_chat_messages
    $charset_collate = $wpdb->get_charset_collate();

    // SQL zum Erstellen der Tabelle
    $sql = "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        message_content text NOT NULL,
        message_type ENUM('message', 'roll', 'system') NOT NULL DEFAULT 'message', -- Typ der Nachricht
        roll_details text DEFAULT NULL, -- Optional: JSON-String mit Würfeldetails (z.B. input, rolls, total)
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id), -- Index für schnellere Suche nach User
        KEY timestamp (timestamp) -- Index für schnelles Abrufen nach Zeit
    ) $charset_collate;";

    // dbDelta ist eine WP-Funktion, die Tabellen erstellt oder aktualisiert, ohne Daten zu verlieren
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    // Optional: Version der DB-Struktur speichern, um spätere Updates zu erkennen
    update_option( 'dnd_helper_db_version', '1.0' );
}

/**
 * Aktionen bei Plugin-Aktivierung.
 * Erstellt DB-Tabelle und aktualisiert Rewrite Rules.
 */
function dnd_helper_activate() {
    // 1. Datenbanktabelle erstellen/aktualisieren
    dnd_helper_create_chat_table();

    // 2. Sicherstellen, dass die CPTs registriert sind, bevor die Regeln geflusht werden
    require_once DND_HELPER_PLUGIN_DIR . 'includes/cpt-character.php';
    dnd_register_character_post_type();
    require_once DND_HELPER_PLUGIN_DIR . 'includes/cpt-campaign.php';
    dnd_register_campaign_post_type();
    require_once DND_HELPER_PLUGIN_DIR . 'includes/cpt-session.php';
    dndt_register_session_post_type();
    require_once DND_HELPER_PLUGIN_DIR . 'includes/cpt-mitspieler.php';
    dndt_register_mitspieler_post_type();

    // 3. API Key für Session Management generieren
    if ( ! get_option( 'dndt_api_key' ) ) {
        $api_key = wp_generate_password( 64, false );
        update_option( 'dndt_api_key', $api_key );
    }

    // 4. Flush rewrite rules damit die CPT URLs korrekt funktionieren
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'dnd_helper_activate' );

/**
 * Aktionen bei Plugin-Deaktivierung.
 */
function dnd_helper_deactivate() {
    // Flush rewrite rules, um die CPT-Regeln zu entfernen
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'dnd_helper_deactivate' );

// Hinweis: Weitere Plugin-Funktionen (Enqueue Scripts, AJAX Handler etc.) werden hier oder in separaten Dateien hinzugefügt.