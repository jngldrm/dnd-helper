<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registriert den Custom Post Type "D&D Session" und die zugehörige Taxonomie.
 */
function dndt_register_session_post_type() {
    // Session Post Type registrieren
    dndt_register_session_cpt();
    // Session Tag Taxonomie registrieren
    dndt_register_session_tag_taxonomy();
}
add_action( 'init', 'dndt_register_session_post_type', 0 );

/**
 * Registriert den Custom Post Type für D&D Sessions.
 */
function dndt_register_session_cpt() {
    $labels = array(
        'name'                  => _x( 'D&D Sessions', 'Post type general name', 'dnd-helper' ),
        'singular_name'         => _x( 'D&D Session', 'Post type singular name', 'dnd-helper' ),
        'menu_name'             => _x( 'D&D Sessions', 'Admin Menu text', 'dnd-helper' ),
        'name_admin_bar'        => _x( 'D&D Session', 'Add New on Toolbar', 'dnd-helper' ),
        'add_new'               => __( 'Neue Session', 'dnd-helper' ),
        'add_new_item'          => __( 'Neue D&D Session erstellen', 'dnd-helper' ),
        'new_item'              => __( 'Neue D&D Session', 'dnd-helper' ),
        'edit_item'             => __( 'D&D Session bearbeiten', 'dnd-helper' ),
        'view_item'             => __( 'D&D Session ansehen', 'dnd-helper' ),
        'all_items'             => __( 'Alle D&D Sessions', 'dnd-helper' ),
        'search_items'          => __( 'D&D Sessions suchen', 'dnd-helper' ),
        'parent_item_colon'     => __( 'Übergeordnete D&D Sessions:', 'dnd-helper' ),
        'not_found'             => __( 'Keine D&D Sessions gefunden.', 'dnd-helper' ),
        'not_found_in_trash'    => __( 'Keine D&D Sessions im Papierkorb gefunden.', 'dnd-helper' ),
        'featured_image'        => _x( 'Session Cover', 'Overrides the "Featured Image" phrase for this post type', 'dnd-helper' ),
        'set_featured_image'    => _x( 'Session Cover festlegen', 'Overrides the "Set featured image" phrase for this post type', 'dnd-helper' ),
        'remove_featured_image' => _x( 'Session Cover entfernen', 'Overrides the "Remove featured image" phrase for this post type', 'dnd-helper' ),
        'use_featured_image'    => _x( 'Als Session Cover verwenden', 'Overrides the "Use as featured image" phrase for this post type', 'dnd-helper' ),
        'archives'              => _x( 'Session Archive', 'The post type archive label used in nav menus', 'dnd-helper' ),
        'insert_into_item'      => _x( 'In Session einfügen', 'Overrides the "Insert into post"/"Insert into page" phrase', 'dnd-helper' ),
        'uploaded_to_this_item' => _x( 'Zu dieser Session hochgeladen', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase', 'dnd-helper' ),
        'filter_items_list'     => _x( 'Sessions filtern', 'Screen reader text for the filter links heading on the post type listing screen', 'dnd-helper' ),
        'items_list_navigation' => _x( 'Session Navigation', 'Screen reader text for the pagination heading on the post type listing screen', 'dnd-helper' ),
        'items_list'            => _x( 'Sessions Liste', 'Screen reader text for the items list heading on the post type listing screen', 'dnd-helper' ),
    );

    $args = array(
        'labels'             => $labels,
        'description'        => __( 'D&D Session Transkripte und Aufzeichnungen', 'dnd-helper' ),
        'public'             => false, // Nicht öffentlich, nur im Admin
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 22, // Nach D&D Kampagnen
        'menu_icon'          => 'dashicons-microphone', // Mikrofon-Icon
        'supports'           => array( 'title', 'custom-fields' ), // Editor entfernt
        'show_in_rest'       => true, // Für REST API
        'rewrite'            => array( 'slug' => 'sessions' ),
        'capabilities'       => array(
            'edit_post'          => 'do_not_allow',
            'read_post'          => 'read',
            'delete_post'        => 'do_not_allow',
            'edit_posts'         => 'read',
            'edit_others_posts'  => 'do_not_allow',
            'publish_posts'      => 'do_not_allow',
            'read_private_posts' => 'read'
        ),
        'map_meta_cap'       => false,
        'has_archive'        => false, // Kein öffentliches Archiv
        'publicly_queryable' => false, // Nicht öffentlich abfragbar
        'exclude_from_search' => true, // Aus Suche ausschließen
    );

    register_post_type( 'dndt_session', $args );
}

/**
 * Registriert die Taxonomie für Session Tags.
 */
function dndt_register_session_tag_taxonomy() {
    $labels = array(
        'name'              => _x( 'Session Tags', 'taxonomy general name', 'dnd-helper' ),
        'singular_name'     => _x( 'Session Tag', 'taxonomy singular name', 'dnd-helper' ),
        'search_items'      => __( 'Session Tags suchen', 'dnd-helper' ),
        'all_items'         => __( 'Alle Session Tags', 'dnd-helper' ),
        'parent_item'       => __( 'Übergeordneter Session Tag', 'dnd-helper' ),
        'parent_item_colon' => __( 'Übergeordneter Session Tag:', 'dnd-helper' ),
        'edit_item'         => __( 'Session Tag bearbeiten', 'dnd-helper' ),
        'update_item'       => __( 'Session Tag aktualisieren', 'dnd-helper' ),
        'add_new_item'      => __( 'Neuen Session Tag hinzufügen', 'dnd-helper' ),
        'new_item_name'     => __( 'Neuer Session Tag Name', 'dnd-helper' ),
        'menu_name'         => __( 'Session Tags', 'dnd-helper' ),
    );

    $args = array(
        'hierarchical'      => false, // Wie Post Tags, nicht wie Kategorien
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true, // In der Admin-Spalte anzeigen
        'query_var'         => true,
        'show_in_rest'      => true, // Für REST API
        'rewrite'           => array( 'slug' => 'session-tag' ),
    );

    register_taxonomy( 'dndt_session_tag', array( 'dndt_session' ), $args );

    // Standard-Tags beim ersten Laden erstellen
    add_action( 'init', 'dndt_create_default_session_tags', 20 );
}

/**
 * Erstellt Standard Session Tags beim Plugin-Start.
 */
function dndt_create_default_session_tags() {
    $default_tags = array(
        'normal' => __( 'Normal', 'dnd-helper' ),
        'speakers' => __( 'Speaker Diarization', 'dnd-helper' ),
        'ausgewertet' => __( 'KI-Analysiert', 'dnd-helper' ),
    );

    foreach ( $default_tags as $slug => $name ) {
        if ( ! term_exists( $slug, 'dndt_session_tag' ) ) {
            wp_insert_term( $name, 'dndt_session_tag', array( 'slug' => $slug ) );
        }
    }
}