<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * D&D Session Management REST API
 */
class DNDT_Session_API {

    /**
     * Initialisiert die REST API Hooks.
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Registriert die REST API Routen.
     */
    public function register_routes() {
        $namespace = 'dnd-session/v1';

        // Endpoint zum Erstellen einer neuen Session
        register_rest_route( $namespace, '/neue-session', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'create_session' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Endpoint zum Abrufen der Mitspieler
        register_rest_route( $namespace, '/mitspieler', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_mitspieler' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );
    }

    /**
     * Überprüft die API-Berechtigung über Bearer Token.
     */
    public function check_api_permission( WP_REST_Request $request ) {
        $auth_header = $request->get_header( 'Authorization' );
        if ( ! $auth_header ) {
            return new WP_Error( 'dndt_rest_forbidden', __( 'Missing Authorization header.', 'dnd-helper' ), array( 'status' => 401 ) );
        }

        if ( ! preg_match( '/^Bearer\s+(.*)$/', $auth_header, $matches ) ) {
            return new WP_Error( 'dndt_rest_forbidden', __( 'Invalid Authorization header format.', 'dnd-helper' ), array( 'status' => 401 ) );
        }

        $submitted_key = $matches[1];
        $stored_key    = get_option( 'dndt_api_key' );

        if ( ! $stored_key || ! hash_equals( $stored_key, $submitted_key ) ) {
            return new WP_Error( 'dndt_rest_forbidden', __( 'Invalid API Key.', 'dnd-helper' ), array( 'status' => 401 ) );
        }

        return true;
    }

    /**
     * Erstellt eine neue D&D Session aus eingehenden Daten.
     */
    public function create_session( WP_REST_Request $request ) {
        $params = $request->get_json_params();

        // Validierung der erforderlichen Felder
        if ( empty( $params['processed_transcript'] ) && empty( $params['original_transkript'] ) ) {
            return new WP_Error( 'dndt_missing_params', __( 'Either processed_transcript or original_transkript is required.', 'dnd-helper' ), array( 'status' => 400 ) );
        }

        // Verarbeitung der strukturierten Transkript-Daten
        $session_content = '';
        $speakers_found = array();
        
        if ( ! empty( $params['processed_transcript'] ) && is_array( $params['processed_transcript'] ) ) {
            // Strukturierte Transkriptdaten mit Speaker-Informationen verarbeiten
            $formatted_content = '';
            
            foreach ( $params['processed_transcript'] as $entry ) {
                if ( isset( $entry['speaker'] ) && isset( $entry['text'] ) ) {
                    $speaker = sanitize_text_field( $entry['speaker'] );
                    $text = sanitize_textarea_field( $entry['text'] );
                    
                    // Speaker-Info für Metadaten extrahieren
                    $speakers_found[] = $speaker;
                    
                    // Als Dialog formatieren
                    $formatted_content .= "<p><strong>{$speaker}:</strong> {$text}</p>\n";
                }
            }
            
            $session_content = $formatted_content;
        } else {
            // Fallback auf Original-Transkript
            $session_content = ! empty( $params['original_transkript'] ) ? wp_kses_post( $params['original_transkript'] ) : '';
        }
        
        // Titel aus den übergebenen Daten generieren
        $post_title = ! empty( $params['titel'] ) ? sanitize_text_field( $params['titel'] ) : 
                      ( ! empty( $params['dateiname_originalaufnahme'] ) ? 
                       'D&D Session - ' . sanitize_text_field( $params['dateiname_originalaufnahme'] ) : 
                       'D&D Session - ' . current_time( 'Y-m-d H:i:s' ) );

        // Post erstellen
        $post_args = array(
            'post_type'    => 'dndt_session',
            'post_title'   => $post_title,
            'post_content' => $session_content,
            'post_status'  => 'publish',
        );

        $post_id = wp_insert_post( $post_args, true );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        // Zusätzliche Metadaten speichern
        if ( ! empty( $params['original_transkript'] ) ) {
            update_post_meta( $post_id, '_dndt_original_transcript', wp_kses_post( $params['original_transkript'] ) );
        }
        
        if ( ! empty( $params['processed_transcript'] ) ) {
            // Debug: Prüfe die rohen Daten vor dem Speichern
            error_log( 'DND Session API: Raw processed_transcript type: ' . gettype( $params['processed_transcript'] ) );
            error_log( 'DND Session API: Raw processed_transcript length: ' . ( is_string( $params['processed_transcript'] ) ? strlen( $params['processed_transcript'] ) : 'not_string' ) );
            error_log( 'DND Session API: Raw processed_transcript sample: ' . substr( print_r( $params['processed_transcript'], true ), 0, 1000 ) );
            
            // Strukturierte Transkriptdaten als JSON speichern
            if ( is_array( $params['processed_transcript'] ) ) {
                $json_data = wp_json_encode( $params['processed_transcript'] );
            } else {
                $json_data = $params['processed_transcript']; // Bereits JSON String
            }
            
            error_log( 'DND Session API: Final JSON length: ' . strlen( $json_data ) );
            error_log( 'DND Session API: Final JSON sample: ' . substr( $json_data, 0, 1000 ) );
            
            update_post_meta( $post_id, '_dndt_processed_transcript', $json_data );
        }
        
        if ( ! empty( $params['dateiname_originalaufnahme'] ) ) {
            update_post_meta( $post_id, '_dndt_original_filename', sanitize_text_field( $params['dateiname_originalaufnahme'] ) );
        }
        
        // Speaker-Informationen speichern
        if ( ! empty( $speakers_found ) ) {
            $unique_speakers = array_unique( $speakers_found );
            update_post_meta( $post_id, '_dndt_session_speakers', $unique_speakers );
        }

        // Session Tags basierend auf speaker_diarization Flag setzen
        $session_tag = 'normal';
        if ( isset( $params['speaker_diarization'] ) && $params['speaker_diarization'] === true ) {
            $session_tag = 'speakers';
        }
        
        wp_set_object_terms( $post_id, $session_tag, 'dndt_session_tag' );

        // Automatische Sprecher-Zuordnung auslösen (nur bei strukturierten Daten)
        error_log( 'DND Session API: Checking speaker mapping trigger for post ' . $post_id );
        error_log( 'DND Session API: processed_transcript present: ' . ( ! empty( $params['processed_transcript'] ) ? 'yes' : 'no' ) );
        error_log( 'DND Session API: processed_transcript type: ' . gettype( $params['processed_transcript'] ) );
        
        if ( ! empty( $params['processed_transcript'] ) ) {
            // Decode JSON string if necessary
            $transcript_data = $params['processed_transcript'];
            if ( is_string( $transcript_data ) ) {
                $transcript_data = json_decode( $transcript_data, true );
                error_log( 'DND Session API: Decoded JSON transcript for validation' );
            }
            
            if ( is_array( $transcript_data ) ) {
                error_log( 'DND Session API: Triggering automatic speaker mapping for post ' . $post_id );
                dnd_trigger_automatic_speaker_mapping( $post_id );
            } else {
                error_log( 'DND Session API: Transcript is not valid array after decoding' );
            }
        } else {
            error_log( 'DND Session API: No processed_transcript provided' );
        }

        return new WP_REST_Response( array(
            'success'  => true,
            'message'  => __( 'D&D Session created successfully.', 'dnd-helper' ),
            'post_id' => $post_id,
            'speakers_found' => count( $unique_speakers ?? array() ),
            'has_structured_data' => ! empty( $params['processed_transcript'] ) && is_array( $params['processed_transcript'] )
        ), 201 );
    }

    /**
     * Gibt eine Liste aller Mitspieler zurück.
     */
    public function get_mitspieler( WP_REST_Request $request ) {
        $mitspieler_posts = get_posts( array(
            'post_type' => 'dndt_mitspieler',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ) );
        
        $mitspieler = array();
        
        foreach ( $mitspieler_posts as $post ) {
            $mitspieler[] = array(
                'id' => $post->ID,
                'name' => $post->post_title,
            );
        }

        return new WP_REST_Response( array(
            'mitspieler' => $mitspieler,
        ), 200 );
    }
}

// Instanz der API-Klasse erstellen
new DNDT_Session_API();