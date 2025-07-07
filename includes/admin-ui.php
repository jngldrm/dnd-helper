<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Beschränkt das Admin-Menü für Benutzer mit der Rolle "Editor".
 * Zeigt nur das Dashboard und den "D&D Charaktere"-Menüpunkt an.
 */
function dnd_restrict_admin_menu_for_editors() {
    // Prüfen, ob der aktuelle Benutzer Editor ist UND NICHT Administrator
    // ('edit_others_posts' ist eine Kernfähigkeit von Editoren, 'manage_options' von Admins)
    if ( current_user_can('editor') && !current_user_can('manage_options') ) {

        // Globale Menü-Arrays holen
        global $menu, $submenu;

        // Erlaubte Top-Level Menü-Slugs
        // 'index.php' = Dashboard
        // 'edit.php?post_type=dnd_character' = Unser CPT
        $allowed_top_level_slugs = [
            'index.php',
            'profile.php', // Profil sollte zugänglich bleiben
            'edit.php?post_type=dnd_character',
            'edit.php?post_type=dnd_campaign',
            'edit.php?post_type=dndt_session',
            'edit.php?post_type=dndt_mitspieler',
            'options-general.php', // Für D&D Helper Settings
        ];

        // Erlaubte Sub-Menü-Punkte unter dem CPT (optional, falls nötig)
        // Z.B. 'post-new.php?post_type=dnd_character' (Neuer Charakter erstellen)
        $allowed_cpt_submenu_slugs = [
             'edit.php?post_type=dnd_character', // Alle Charaktere
             'post-new.php?post_type=dnd_character', // Erstellen
             // Man könnte hier auch Taxonomie-Seiten erlauben, falls vorhanden
        ];

        // Filtere das Top-Level-Menü
        foreach ( $menu as $key => $menu_item ) {
            // $menu_item[2] enthält den Slug der Seite
            if ( ! in_array( $menu_item[2], $allowed_top_level_slugs ) ) {
                // Entferne den Menüpunkt komplett
                remove_menu_page( $menu_item[2] );
            }
        }

         // Filtere spezifische Sub-Menüs (optional, falls remove_menu_page nicht reicht)
         // Beispiel: Nur bestimmte Unterpunkte des CPT erlauben
         $cpt_menu_slug = 'edit.php?post_type=dnd_character';
         if (isset($submenu[$cpt_menu_slug])) {
              foreach ($submenu[$cpt_menu_slug] as $sub_key => $sub_item) {
                  if (!in_array($sub_item[2], $allowed_cpt_submenu_slugs)) {
                      // unset($submenu[$cpt_menu_slug][$sub_key]); // Alternative: remove_submenu_page()
                      remove_submenu_page($cpt_menu_slug, $sub_item[2]);
                  }
              }
         }
    }
}
// 'admin_menu' ist ein guter Hook dafür, oder 'admin_init'
add_action( 'admin_menu', 'dnd_restrict_admin_menu_for_editors', 999 ); // Hohe Priorität, damit es nach anderen läuft

/**
 * Fügt ein benutzerdefiniertes Widget zum Dashboard für Editoren hinzu.
 */
function dnd_add_editor_dashboard_widget() {
    // Nur für Editoren anzeigen, die keine Admins sind
    if ( current_user_can('editor') && !current_user_can('manage_options') ) {
        wp_add_dashboard_widget(
            'dnd_editor_instructions_widget',         // Eindeutige Widget-ID
            __( 'D&D Helper Anleitung', 'dnd-helper' ), // Widget-Titel
            'dnd_render_editor_instructions_widget' // Callback-Funktion für den Inhalt
        );
    }
}
add_action( 'wp_dashboard_setup', 'dnd_add_editor_dashboard_widget' );

/**
 * Registriert Settings-Seite für das D&D Helper Plugin.
 */
function dnd_register_settings_page() {
    add_options_page(
        __( 'D&D Helper Einstellungen', 'dnd-helper' ),
        __( 'D&D Helper', 'dnd-helper' ),
        'manage_options',
        'dnd-helper-settings',
        'dnd_render_settings_page'
    );
}
add_action( 'admin_menu', 'dnd_register_settings_page' );

/**
 * Registriert die Settings für das Plugin.
 */
function dnd_register_settings() {
    // Session Management API Key
    register_setting( 'dnd_helper_settings', 'dndt_api_key' );
    register_setting( 'dnd_helper_settings', 'dndt_gemini_api_key' );
    register_setting( 'dnd_helper_settings', 'dndt_gemini_model' );

    // Settings Sektion
    add_settings_section(
        'dnd_session_management_section',
        __( 'Session Management', 'dnd-helper' ),
        'dnd_session_management_section_callback',
        'dnd-helper-settings'
    );

    // API Key Felder
    add_settings_field(
        'dndt_api_key',
        __( 'Session API Key', 'dnd-helper' ),
        'dnd_api_key_field_callback',
        'dnd-helper-settings',
        'dnd_session_management_section'
    );

    add_settings_field(
        'dndt_gemini_api_key',
        __( 'Gemini AI API Key', 'dnd-helper' ),
        'dnd_gemini_api_key_field_callback',
        'dnd-helper-settings',
        'dnd_session_management_section'
    );

    add_settings_field(
        'dndt_gemini_model',
        __( 'AI Model', 'dnd-helper' ),
        'dnd_gemini_model_field_callback',
        'dnd-helper-settings',
        'dnd_session_management_section'
    );
}
add_action( 'admin_init', 'dnd_register_settings' );

/**
 * Callback für die Session Management Sektion.
 */
function dnd_session_management_section_callback() {
    echo '<p>' . esc_html__( 'Konfiguration für die Session-Verwaltung und KI-Analyse.', 'dnd-helper' ) . '</p>';
}

/**
 * Callback für das API Key Feld.
 */
function dnd_api_key_field_callback() {
    $api_key = get_option( 'dndt_api_key' );
    echo '<input type="text" id="dndt_api_key" name="dndt_api_key" value="' . esc_attr( $api_key ) . '" class="regular-text" readonly />';
    echo '<p class="description">' . esc_html__( 'API Key für externe Session-Erstellung. Wird automatisch generiert.', 'dnd-helper' ) . '</p>';
    echo '<button type="button" id="regenerate-api-key" class="button">' . esc_html__( 'Neuen Key generieren', 'dnd-helper' ) . '</button>';
    ?>
    <script>
    document.getElementById('regenerate-api-key').addEventListener('click', function() {
        if (confirm('<?php esc_html_e( 'Möchten Sie wirklich einen neuen API Key generieren? Der alte wird ungültig.', 'dnd-helper' ); ?>')) {
            // Neuen Key generieren
            var newKey = '';
            var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            for (var i = 0; i < 64; i++) {
                newKey += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('dndt_api_key').value = newKey;
        }
    });
    </script>
    <?php
}

/**
 * Callback für das Gemini API Key Feld.
 */
function dnd_gemini_api_key_field_callback() {
    $api_key = get_option( 'dndt_gemini_api_key' );
    echo '<input type="password" id="dndt_gemini_api_key" name="dndt_gemini_api_key" value="' . esc_attr( $api_key ) . '" class="regular-text" />';
    echo '<p class="description">' . esc_html__( 'Gemini AI API Key für die automatische Session-Analyse. Kostenlos erhältlich bei Google AI Studio.', 'dnd-helper' ) . '</p>';
}

/**
 * Callback für das Gemini Model Auswahl-Feld.
 */
function dnd_gemini_model_field_callback() {
    $current_model = get_option( 'dndt_gemini_model', 'gemini-2.0-flash-thinking-exp' );
    
    $available_models = array(
        'gemini-2.5-pro' => 'Gemini 2.5 Pro',
        'gemini-2.0-flash-thinking-exp' => 'Gemini 2.0 Flash Thinking (Experimental)',
        'gemini-2.0-flash-exp' => 'Gemini 2.0 Flash (Experimental)', 
        'gemini-1.5-flash' => 'Gemini 1.5 Flash',
        'gemini-1.5-flash-8b' => 'Gemini 1.5 Flash 8B',
        'gemini-1.5-pro' => 'Gemini 1.5 Pro'
    );
    
    echo '<select id="dndt_gemini_model" name="dndt_gemini_model">';
    foreach ( $available_models as $model_id => $model_name ) {
        $selected = selected( $current_model, $model_id, false );
        echo '<option value="' . esc_attr( $model_id ) . '"' . $selected . '>' . esc_html( $model_name ) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">' . esc_html__( 'Wählen Sie das Gemini AI Modell für die Session-Analyse. Experimentelle Modelle bieten bessere Leistung, können aber instabil sein.', 'dnd-helper' ) . '</p>';
}

/**
 * Rendert die Settings-Seite.
 */
function dnd_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'D&D Helper Einstellungen', 'dnd-helper' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'dnd_helper_settings' );
            do_settings_sections( 'dnd-helper-settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Gibt den Inhalt für das Editor-Anleitungs-Widget aus.
 */
function dnd_render_editor_instructions_widget() {
    // Hier den HTML-Inhalt für die Anleitung einfügen.
    ?>
    <h4><?php _e('Willkommen beim D&D Helper!', 'dnd-helper'); ?></h4>
    <p>
        <?php _e('Als Editor kannst du hier Dungeons & Dragons Charaktere verwalten.', 'dnd-helper'); ?>
    </p>
    <ul>
        <li>
            <strong><?php _e('Charaktere anzeigen/bearbeiten:', 'dnd-helper'); ?></strong>
            <?php printf(
                /* Translators: %s is the link to the character list. */
                esc_html__( 'Gehe zum Menüpunkt %s, um alle Charaktere zu sehen oder zu bearbeiten.', 'dnd-helper' ),
                '<a href="' . esc_url( admin_url('edit.php?post_type=dnd_character') ) . '">' . esc_html__('D&D Charaktere', 'dnd-helper') . '</a>'
            ); ?>
        </li>
        <li>
            <strong><?php _e('Neuen Charakter erstellen:', 'dnd-helper'); ?></strong>
             <?php printf(
                /* Translators: %s is the link to add a new character. */
                esc_html__( 'Klicke %s, um einen neuen Charakter anzulegen.', 'dnd-helper' ),
                '<a href="' . esc_url( admin_url('post-new.php?post_type=dnd_character') ) . '">' . esc_html__('hier', 'dnd-helper') . '</a>'
            ); ?> <?php _e('Vergiss nicht, den korrekten Spieler als "Autor" zuzuweisen!', 'dnd-helper'); ?>
        </li>
         <li>
            <strong><?php _e('JSON Import:', 'dnd-helper'); ?></strong>
            <?php _e('Beim Bearbeiten eines Charakters kannst du eine Charakterdatei im JSON-Format hochladen, um die Daten schnell zu importieren.', 'dnd-helper'); ?>
        </li>
         <li>
            <strong><?php _e('Charakter im Frontend:', 'dnd-helper'); ?></strong>
            <?php _e('Wenn einem Spieler (Benutzer) ein Charakter zugewiesen ist, kann er diesen auf der Spielseite über den Shortcode [dnd_character_sheet] sehen und ggf. Werte wie Trefferpunkte direkt ändern.', 'dnd-helper'); ?>
        </li>
         <li>
            <strong><?php _e('Chat:', 'dnd-helper'); ?></strong>
            <?php _e('Der Shortcode [dnd_chat] auf der Spielseite ermöglicht das gemeinsame Würfeln und Chatten.', 'dnd-helper'); ?>
        </li>
        <li>
            <strong><?php _e('Session Management:', 'dnd-helper'); ?></strong>
            <?php _e('D&D Sessions können über die REST API automatisch importiert und mit KI analysiert werden.', 'dnd-helper'); ?>
        </li>
        <li>
            <strong><?php _e('Mitspieler:', 'dnd-helper'); ?></strong>
            <?php _e('Verwalten Sie Spieler-Profile für die Speaker-Zuordnung in Session-Transkripten.', 'dnd-helper'); ?>
        </li>
    </ul>
    <p>
        <em><?php _e('Bei Fragen oder Problemen wende dich bitte an einen Administrator.', 'dnd-helper'); ?></em>
    </p>
    <?php
}

?>