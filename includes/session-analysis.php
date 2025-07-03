<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * D&D Session KI-Analyse Funktionalität
 */
class DNDT_Session_Analysis {

    /**
     * Initialisiert die AJAX Hooks.
     */
    public function __construct() {
        // AJAX-Handler für Session-Analyse
        add_action( 'wp_ajax_dndt_analyze_session', array( $this, 'ajax_analyze_session' ) );
        add_action( 'wp_ajax_nopriv_dndt_analyze_session', array( $this, 'ajax_analyze_session' ) );
        
        // AJAX-Handler für vollständige Session-Anzeige
        add_action( 'wp_ajax_dndt_get_full_session', array( $this, 'ajax_get_full_session' ) );
        add_action( 'wp_ajax_nopriv_dndt_get_full_session', array( $this, 'ajax_get_full_session' ) );
        
        // AJAX-Handler für Mitspieler
        add_action( 'wp_ajax_dndt_get_mitspieler', array( $this, 'ajax_get_mitspieler' ) );
        add_action( 'wp_ajax_nopriv_dndt_get_mitspieler', array( $this, 'ajax_get_mitspieler' ) );
    }

    /**
     * AJAX Handler zum Abrufen der vollständigen Session-Daten.
     */
    public function ajax_get_full_session() {
        // Sicherheit: Nonce prüfen
        check_ajax_referer( 'dnd_chat_nonce', 'nonce' );
        
        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        
        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => 'Ungültige Post-ID' ) );
            return;
        }
        
        $post = get_post( $post_id );
        
        if ( ! $post || $post->post_type !== 'dndt_session' ) {
            wp_send_json_error( array( 'message' => 'Session nicht gefunden' ) );
            return;
        }
        
        // Session-Tags abrufen
        $session_tags = wp_get_object_terms( $post_id, 'dndt_session_tag', array( 'fields' => 'slugs' ) );
        $has_speaker_diarization = in_array( 'speakers', $session_tags );
        $has_ausgewertet = in_array( 'ausgewertet', $session_tags );
        
        // Strukturierte Daten abrufen
        $structured_data = get_post_meta( $post_id, '_dndt_processed_transcript', true );
        $speakers = get_post_meta( $post_id, '_dndt_session_speakers', true );
        
        // Existierende AI-Analyse abrufen
        $existing_analysis = get_post_meta( $post_id, '_dndt_ai_auswertung', true );
        
        wp_send_json_success( array(
            'title' => get_the_title( $post_id ),
            'content' => apply_filters( 'the_content', $post->post_content ),
            'date' => get_the_date( 'd.m.Y H:i', $post_id ),
            'has_speaker_diarization' => $has_speaker_diarization,
            'has_analysis' => $has_ausgewertet,
            'existing_analysis' => $existing_analysis,
            'speakers' => $speakers,
            'has_structured_data' => ! empty( $structured_data )
        ) );
    }

    /**
     * AJAX Handler für die KI-Analyse einer Session.
     */
    public function ajax_analyze_session() {
        // Sicherheit: Nonce prüfen
        check_ajax_referer( 'dnd_chat_nonce', 'nonce' );
        
        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( $_POST['prompt'] ) : '';
        
        if ( ! $post_id || ! $prompt ) {
            wp_send_json_error( array( 'message' => 'Ungültige Parameter' ) );
            return;
        }
        
        $post = get_post( $post_id );
        
        if ( ! $post || $post->post_type !== 'dndt_session' ) {
            wp_send_json_error( array( 'message' => 'Session nicht gefunden' ) );
            return;
        }
        
        // Gemini API Key abrufen
        $api_key = get_option( 'dndt_gemini_api_key' );
        if ( empty( $api_key ) ) {
            wp_send_json_error( array( 'message' => 'Gemini AI API Key ist nicht konfiguriert. Bitte in den Einstellungen hinterlegen.' ) );
            return;
        }
        
        // Content für AI-Analyse vorbereiten
        $session_content = strip_tags( $post->post_content );
        $full_prompt = $prompt . "\n\n" . $session_content;
        
        // Gemini AI API aufrufen
        $ai_response = $this->call_gemini_api( $api_key, $full_prompt );
        
        if ( is_wp_error( $ai_response ) ) {
            wp_send_json_error( array( 'message' => 'Fehler bei der KI-Analyse: ' . $ai_response->get_error_message() ) );
            return;
        }
        
        // AI-Analyse als Custom Field speichern
        update_post_meta( $post_id, '_dndt_ai_auswertung', $ai_response );
        
        // Automatisch "ausgewertet" Tag hinzufügen
        $existing_tags = wp_get_object_terms( $post_id, 'dndt_session_tag', array( 'fields' => 'slugs' ) );
        if ( ! in_array( 'ausgewertet', $existing_tags ) ) {
            wp_set_object_terms( $post_id, array_merge( $existing_tags, array( 'ausgewertet' ) ), 'dndt_session_tag' );
        }
        
        wp_send_json_success( array(
            'analysis' => $ai_response
        ) );
    }

    /**
     * Ruft die Gemini AI API auf.
     */
    private function call_gemini_api( $api_key, $prompt ) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-05-20:generateContent?key=' . $api_key;
        
        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => $prompt
                        )
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
            )
        );
        
        $response = wp_remote_post( $url, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode( $body ),
            'timeout' => 120
        ) );
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        
        if ( $response_code !== 200 ) {
            return new WP_Error( 'gemini_api_error', 'API Fehler: ' . $response_code . ' - ' . $response_body );
        }
        
        $data = json_decode( $response_body, true );
        
        if ( ! isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
            return new WP_Error( 'gemini_api_error', 'Unerwartete API-Antwort' );
        }
        
        return $data['candidates'][0]['content']['parts'][0]['text'];
    }

    /**
     * AJAX Handler zum Abrufen der Mitspieler.
     */
    public function ajax_get_mitspieler() {
        // Sicherheit: Nonce prüfen
        check_ajax_referer( 'dnd_chat_nonce', 'nonce' );
        
        $mitspieler_query = new WP_Query( array(
            'post_type' => 'dndt_mitspieler',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ) );
        
        $mitspieler = array();
        if ( $mitspieler_query->have_posts() ) {
            while ( $mitspieler_query->have_posts() ) {
                $mitspieler_query->the_post();
                $mitspieler[] = array(
                    'id' => get_the_ID(),
                    'name' => get_the_title()
                );
            }
        }
        wp_reset_postdata();
        
        wp_send_json_success( array(
            'mitspieler' => $mitspieler
        ) );
    }
}

// Instanz der Analyse-Klasse erstellen
new DNDT_Session_Analysis();