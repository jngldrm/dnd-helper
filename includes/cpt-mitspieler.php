<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registriert den Custom Post Type "Mitspieler".
 */
function dndt_register_mitspieler_post_type() {
    $labels = array(
        'name'                  => _x( 'Mitspieler', 'Post type general name', 'dnd-helper' ),
        'singular_name'         => _x( 'Mitspieler', 'Post type singular name', 'dnd-helper' ),
        'menu_name'             => _x( 'Mitspieler', 'Admin Menu text', 'dnd-helper' ),
        'name_admin_bar'        => _x( 'Mitspieler', 'Add New on Toolbar', 'dnd-helper' ),
        'add_new'               => __( 'Hinzufügen', 'dnd-helper' ),
        'add_new_item'          => __( 'Neuen Mitspieler hinzufügen', 'dnd-helper' ),
        'new_item'              => __( 'Neuer Mitspieler', 'dnd-helper' ),
        'edit_item'             => __( 'Mitspieler bearbeiten', 'dnd-helper' ),
        'view_item'             => __( 'Mitspieler ansehen', 'dnd-helper' ),
        'all_items'             => __( 'Alle Mitspieler', 'dnd-helper' ),
        'search_items'          => __( 'Mitspieler suchen', 'dnd-helper' ),
        'parent_item_colon'     => __( 'Übergeordnete Mitspieler:', 'dnd-helper' ),
        'not_found'             => __( 'Keine Mitspieler gefunden.', 'dnd-helper' ),
        'not_found_in_trash'    => __( 'Keine Mitspieler im Papierkorb gefunden.', 'dnd-helper' ),
        'featured_image'        => _x( 'Spielerbild', 'Overrides the "Featured Image" phrase for this post type', 'dnd-helper' ),
        'set_featured_image'    => _x( 'Spielerbild festlegen', 'Overrides the "Set featured image" phrase for this post type', 'dnd-helper' ),
        'remove_featured_image' => _x( 'Spielerbild entfernen', 'Overrides the "Remove featured image" phrase for this post type', 'dnd-helper' ),
        'use_featured_image'    => _x( 'Als Spielerbild verwenden', 'Overrides the "Use as featured image" phrase for this post type', 'dnd-helper' ),
        'archives'              => _x( 'Mitspieler Archive', 'The post type archive label used in nav menus', 'dnd-helper' ),
        'insert_into_item'      => _x( 'In Mitspieler einfügen', 'Overrides the "Insert into post"/"Insert into page" phrase', 'dnd-helper' ),
        'uploaded_to_this_item' => _x( 'Zu diesem Mitspieler hochgeladen', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase', 'dnd-helper' ),
        'filter_items_list'     => _x( 'Mitspieler filtern', 'Screen reader text for the filter links heading on the post type listing screen', 'dnd-helper' ),
        'items_list_navigation' => _x( 'Mitspieler Navigation', 'Screen reader text for the pagination heading on the post type listing screen', 'dnd-helper' ),
        'items_list'            => _x( 'Mitspieler Liste', 'Screen reader text for the items list heading on the post type listing screen', 'dnd-helper' ),
    );

    $args = array(
        'labels'             => $labels,
        'description'        => __( 'D&D Mitspieler und Spieler-Profile', 'dnd-helper' ),
        'public'             => false, // Nicht öffentlich, nur im Admin
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 23, // Nach D&D Sessions
        'menu_icon'          => 'dashicons-groups', // Gruppen-Icon
        'supports'           => array( 'title', 'thumbnail' ), // Nur Titel und Bild
        'show_in_rest'       => true, // Für REST API
        'rewrite'            => array( 'slug' => 'players' ),
        'capability_type'    => 'post',
        'map_meta_cap'       => true,
        'has_archive'        => false, // Kein öffentliches Archiv
        'publicly_queryable' => false, // Nicht öffentlich abfragbar
        'exclude_from_search' => true, // Aus Suche ausschließen
    );

    register_post_type( 'dndt_mitspieler', $args );
}
add_action( 'init', 'dndt_register_mitspieler_post_type', 0 );