<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registriert den Custom Post Type "Charakter".
 */
function dnd_register_character_post_type() {

    $labels = array(
        'name'                  => _x( 'Charaktere', 'Post Type General Name', 'dnd-helper' ),
        'singular_name'         => _x( 'Charakter', 'Post Type Singular Name', 'dnd-helper' ),
        'menu_name'             => __( 'D&D Charaktere', 'dnd-helper' ),
        'name_admin_bar'        => __( 'Charakter', 'dnd-helper' ),
        'archives'              => __( 'Charakter-Archiv', 'dnd-helper' ),
        'attributes'            => __( 'Charakter-Attribute', 'dnd-helper' ),
        'parent_item_colon'     => __( 'Übergeordneter Charakter:', 'dnd-helper' ),
        'all_items'             => __( 'Alle Charaktere', 'dnd-helper' ),
        'add_new_item'          => __( 'Neuen Charakter erstellen', 'dnd-helper' ),
        'add_new'               => __( 'Erstellen', 'dnd-helper' ),
        'new_item'              => __( 'Neuer Charakter', 'dnd-helper' ),
        'edit_item'             => __( 'Charakter bearbeiten', 'dnd-helper' ),
        'update_item'           => __( 'Charakter aktualisieren', 'dnd-helper' ),
        'view_item'             => __( 'Charakter ansehen', 'dnd-helper' ),
        'view_items'            => __( 'Charaktere ansehen', 'dnd-helper' ),
        'search_items'          => __( 'Charaktere suchen', 'dnd-helper' ),
        'not_found'             => __( 'Keine Charaktere gefunden', 'dnd-helper' ),
        'not_found_in_trash'    => __( 'Keine Charaktere im Papierkorb gefunden', 'dnd-helper' ),
        'featured_image'        => __( 'Charakterbild', 'dnd-helper' ), // Optional: Charakterbild
        'set_featured_image'    => __( 'Charakterbild festlegen', 'dnd-helper' ),
        'remove_featured_image' => __( 'Charakterbild entfernen', 'dnd-helper' ),
        'use_featured_image'    => __( 'Als Charakterbild verwenden', 'dnd-helper' ),
        'insert_into_item'      => __( 'In Charakter einfügen', 'dnd-helper' ),
        'uploaded_to_this_item' => __( 'Zu diesem Charakter hochgeladen', 'dnd-helper' ),
        'items_list'            => __( 'Charakterliste', 'dnd-helper' ),
        'items_list_navigation' => __( 'Charakterlisten-Navigation', 'dnd-helper' ),
        'filter_items_list'     => __( 'Charakterliste filtern', 'dnd-helper' ),
    );
    $args = array(
        'label'                 => __( 'Charakter', 'dnd-helper' ),
        'description'           => __( 'Dungeons & Dragons Charaktere', 'dnd-helper' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'author', 'thumbnail' ), // Wir brauchen 'title'. 'author' ist gut für die Zuordnung. 'thumbnail' für ein optionales Bild. Den Editor brauchen wir *nicht* für die Charakterdaten.
        'taxonomies'            => array( /* 'campaign', 'character_status' */ ), // Optional: Eigene Taxonomien (z.B. Kampagne, Status) könnten später hinzugefügt werden.
        'hierarchical'          => false,
        'public'                => true, // Sichtbar im Frontend (URLs), aber Zugriff kann eingeschränkt werden.
        'show_ui'               => true, // Im Admin-Menü anzeigen
        'show_in_menu'          => true, // Im Hauptmenü anzeigen
        'menu_position'         => 20, // Position im Menü (unter Seiten)
        'menu_icon'             => 'dashicons-id', // Ein passendes Icon
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true, // Kann exportiert werden
        'has_archive'           => 'charaktere', // Slug für das Archiv (z.B. deineseite.de/charaktere)
        'exclude_from_search'   => false, // Sollen Charaktere in der WP-Suche gefunden werden?
        'publicly_queryable'    => true,
        'rewrite'               => array( 'slug' => 'charaktere', 'with_front' => false ), // URL-Struktur
        'capability_type'       => 'post', // Standard Berechtigungstyp ('post', 'page'). Wichtig für die Rollen-Zuweisung.
        'map_meta_cap'          => true, // Wichtig, damit WordPress die Capabilities korrekt auf Rollen mappt (z.B. edit_others_posts für Editoren)
        // 'capabilities' => array(...) // Hier könnten wir sehr spezifische Rechte definieren, aber 'capability_type' => 'post' und map_meta_cap = true sollte für den Editor-Zugriff reichen.
        'show_in_rest'          => true, // Wichtig, falls wir später die REST API nutzen wollen (z.B. für JS-basierte Bearbeitung)
    );
    register_post_type( 'dnd_character', $args );

}
add_action( 'init', 'dnd_register_character_post_type', 0 );

?>