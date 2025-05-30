<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registriert den Custom Post Type "Kampagne".
 */
function dnd_register_campaign_post_type() {

    $labels = array(
        'name'                  => _x( 'Kampagnen', 'Post Type General Name', 'dnd-helper' ),
        'singular_name'         => _x( 'Kampagne', 'Post Type Singular Name', 'dnd-helper' ),
        'menu_name'             => __( 'D&D Kampagnen', 'dnd-helper' ),
        'name_admin_bar'        => __( 'Kampagne', 'dnd-helper' ),
        'archives'              => __( 'Kampagnen-Archiv', 'dnd-helper' ),
        'attributes'            => __( 'Kampagnen-Attribute', 'dnd-helper' ),
        'parent_item_colon'     => __( 'Übergeordnete Kampagne:', 'dnd-helper' ),
        'all_items'             => __( 'Alle Kampagnen', 'dnd-helper' ),
        'add_new_item'          => __( 'Neue Kampagne erstellen', 'dnd-helper' ),
        'add_new'               => __( 'Erstellen', 'dnd-helper' ),
        'new_item'              => __( 'Neue Kampagne', 'dnd-helper' ),
        'edit_item'             => __( 'Kampagne bearbeiten', 'dnd-helper' ),
        'update_item'           => __( 'Kampagne aktualisieren', 'dnd-helper' ),
        'view_item'             => __( 'Kampagne ansehen', 'dnd-helper' ),
        'view_items'            => __( 'Kampagnen ansehen', 'dnd-helper' ),
        'search_items'          => __( 'Kampagnen suchen', 'dnd-helper' ),
        'not_found'             => __( 'Keine Kampagnen gefunden', 'dnd-helper' ),
        'not_found_in_trash'    => __( 'Keine Kampagnen im Papierkorb gefunden', 'dnd-helper' ),
        'insert_into_item'      => __( 'In Kampagne einfügen', 'dnd-helper' ),
        'uploaded_to_this_item' => __( 'Zu dieser Kampagne hochgeladen', 'dnd-helper' ),
        'items_list'            => __( 'Kampagnenliste', 'dnd-helper' ),
        'items_list_navigation' => __( 'Kampagnenlisten-Navigation', 'dnd-helper' ),
        'filter_items_list'     => __( 'Kampagnenliste filtern', 'dnd-helper' ),
        'featured_image'        => __( 'Kampagnenbild', 'dnd-helper' ), // Optional: Charakterbild
        'set_featured_image'    => __( 'Kampagnenbild festlegen', 'dnd-helper' ),
        'remove_featured_image' => __( 'Kampagnenbild entfernen', 'dnd-helper' ),
        'use_featured_image'    => __( 'Als Kampagnenbild verwenden', 'dnd-helper' ),
    );
    $args = array(
        'label'                 => __( 'Kampagne', 'dnd-helper' ),
        'description'           => __( 'Dungeons & Dragons Kampagnenzusammenfassungen', 'dnd-helper' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'author', 'thumbnail' ), // Nur Titel und Autor
        'hierarchical'          => false,
        'public'                => true, // Kann im Frontend angezeigt werden (wenn man Detailseiten wollte)
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 21, // Direkt nach D&D Charakteren
        'menu_icon'             => 'dashicons-book-alt', // Ein Buch-Icon
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => 'kampagnen', // z.B. deineseite.de/kampagnen
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'rewrite'               => array( 'slug' => 'kampagnen', 'with_front' => false ),
        'capability_type'       => 'post', // Standard-Berechtigungen (Editoren können verwalten)
        'map_meta_cap'          => true,
        'show_in_rest'          => true, // Für zukünftige REST API Nutzung
    );
    register_post_type( 'dnd_campaign', $args );

}
add_action( 'init', 'dnd_register_campaign_post_type', 0 );

?>