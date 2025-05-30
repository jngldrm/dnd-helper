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
    </ul>
    <p>
        <em><?php _e('Bei Fragen oder Problemen wende dich bitte an einen Administrator.', 'dnd-helper'); ?></em>
    </p>
    <?php
}

?>