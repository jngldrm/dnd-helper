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
        
        // Formatiere das Transkript mit Sprecher-Zuordnung
        $formatted_content = dnd_format_transcript_with_speakers( $post_id );
        
        wp_send_json_success( array(
            'title' => get_the_title( $post_id ),
            'content' => apply_filters( 'the_content', $formatted_content ),
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
        $model = get_option( 'dndt_gemini_model', 'gemini-2.0-flash-thinking-exp' );
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $api_key;
        
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
        
        // Debug: Log the actual API response structure
        error_log( 'Gemini API Response (Speaker Mapping): ' . print_r( $data, true ) );
        
        if ( ! isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
            error_log( 'Gemini API Error: Expected structure not found in response' );
            return new WP_Error( 'gemini_api_error', 'Unerwartete API-Antwort. Response: ' . substr( $response_body, 0, 500 ) );
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

// =========================================================================
// == PROMPT TEMPLATE HELPER FUNCTIONS
// =========================================================================

/**
 * Lädt eine Prompt-Vorlage aus dem prompts/ Verzeichnis.
 * 
 * @param string $filename Der Dateiname der Prompt-Datei (z.B. 'summarize.md')
 * @return string|false Der Inhalt der Prompt-Datei oder false bei Fehler
 */
function dnd_get_prompt_template( $filename ) {
    // Sicherheit: Nur erlaubte Dateinamen
    $allowed_files = array( 'summarize.md', 'merge.md' );
    if ( ! in_array( $filename, $allowed_files ) ) {
        return false;
    }
    
    // Pfad zur Prompt-Datei erstellen
    $prompt_path = DND_HELPER_PLUGIN_DIR . 'prompts/' . $filename;
    
    // Prüfen ob Datei existiert und lesbar ist
    if ( ! file_exists( $prompt_path ) || ! is_readable( $prompt_path ) ) {
        return false;
    }
    
    // Datei einlesen
    $content = file_get_contents( $prompt_path );
    
    // Bei Fehlern false zurückgeben
    if ( $content === false ) {
        return false;
    }
    
    return $content;
}

/**
 * Ersetzt Platzhalter in einem Prompt-Template.
 * 
 * @param string $template Das Template mit Platzhaltern
 * @param array $replacements Assoziatives Array mit Platzhalter => Wert
 * @return string Das Template mit ersetzten Platzhaltern
 */
function dnd_replace_prompt_placeholders( $template, $replacements ) {
    foreach ( $replacements as $placeholder => $value ) {
        // Platzhalter in {{}} Format ersetzen
        $template = str_replace( '{{' . $placeholder . '}}', $value, $template );
    }
    return $template;
}

// =========================================================================
// == SPEAKER MAPPING FUNCTIONS
// =========================================================================

/**
 * Führt eine automatische Sprecher-Zuordnung mit LLM durch.
 * 
 * @param int $session_id ID der Session
 * @return bool|WP_Error True bei Erfolg, WP_Error bei Fehler
 */
function dnd_llm_map_speakers( $session_id ) {
    error_log( 'DND Speaker Mapping: Starting for Session ' . $session_id );
    
    // Gemini API Key abrufen
    $api_key = get_option( 'dndt_gemini_api_key' );
    if ( empty( $api_key ) ) {
        error_log( 'DND Speaker Mapping: No API key configured' );
        return new WP_Error( 'no_api_key', 'Gemini AI API Key ist nicht konfiguriert.' );
    }
    
    // Structured Transcript abrufen
    $transcript_data = get_post_meta( $session_id, '_dndt_processed_transcript', true );
    if ( empty( $transcript_data ) ) {
        error_log( 'DND Speaker Mapping: No processed transcript found for Session ' . $session_id );
        return new WP_Error( 'no_transcript', 'Kein strukturiertes Transkript gefunden.' );
    }
    
    error_log( 'DND Speaker Mapping: Transcript data type: ' . gettype( $transcript_data ) );
    error_log( 'DND Speaker Mapping: Transcript data length from DB: ' . ( is_string( $transcript_data ) ? strlen( $transcript_data ) : 'not_string' ) );
    
    // Decode JSON if needed
    if ( is_string( $transcript_data ) ) {
        error_log( 'DND Speaker Mapping: Raw JSON string: ' . substr( $transcript_data, 0, 500 ) . '...' );
        
        // Fix UTF-8 encoding issues
        $transcript_data = html_entity_decode( $transcript_data, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        
        // Try to fix incomplete JSON by ensuring it ends properly
        $transcript_data = trim( $transcript_data );
        if ( ! empty( $transcript_data ) ) {
            // If it starts with [ but doesn't end with ], try to fix it
            if ( substr( $transcript_data, 0, 1 ) === '[' && substr( $transcript_data, -1 ) !== ']' ) {
                // Find the last complete object
                $last_complete = strrpos( $transcript_data, '}' );
                if ( $last_complete !== false ) {
                    $transcript_data = substr( $transcript_data, 0, $last_complete + 1 ) . ']';
                }
            }
        }
        
        error_log( 'DND Speaker Mapping: Fixed JSON string: ' . substr( $transcript_data, 0, 500 ) . '...' );
        
        $transcript_data = json_decode( $transcript_data, true );
        error_log( 'DND Speaker Mapping: Decoded JSON transcript' );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            error_log( 'DND Speaker Mapping: JSON decode error: ' . json_last_error_msg() );
        }
    }
    
    error_log( 'DND Speaker Mapping: Transcript data after decoding - type: ' . gettype( $transcript_data ) . ', content: ' . print_r( $transcript_data, true ) );
    
    if ( ! is_array( $transcript_data ) ) {
        error_log( 'DND Speaker Mapping: Invalid transcript format after decoding' );
        return new WP_Error( 'invalid_transcript', 'Ungültiges Transkript-Format.' );
    }
    
    // Unique speakers extrahieren
    $speakers = array();
    foreach ( $transcript_data as $entry ) {
        if ( isset( $entry['speaker'] ) && ! in_array( $entry['speaker'], $speakers ) ) {
            $speakers[] = $entry['speaker'];
        }
    }
    
    error_log( 'DND Speaker Mapping: Found speakers: ' . implode( ', ', $speakers ) );
    
    if ( empty( $speakers ) ) {
        error_log( 'DND Speaker Mapping: No speakers found in transcript' );
        return new WP_Error( 'no_speakers', 'Keine Sprecher im Transkript gefunden.' );
    }
    
    // Mitspieler abrufen
    $mitspieler_query = new WP_Query( array(
        'post_type' => 'dndt_mitspieler',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ) );
    
    $mitspieler_list = array();
    if ( $mitspieler_query->have_posts() ) {
        while ( $mitspieler_query->have_posts() ) {
            $mitspieler_query->the_post();
            $mitspieler_list[] = get_the_title();
        }
    }
    wp_reset_postdata();
    
    error_log( 'DND Speaker Mapping: Found mitspieler: ' . implode( ', ', $mitspieler_list ) );
    
    if ( empty( $mitspieler_list ) ) {
        error_log( 'DND Speaker Mapping: No mitspieler found' );
        return new WP_Error( 'no_mitspieler', 'Keine Mitspieler gefunden.' );
    }
    
    // Transkript-Auszug für LLM erstellen (erste 10-15 Zeilen)
    error_log( 'DND Speaker Mapping: Creating transcript excerpt...' );
    $transcript_excerpt = '';
    $line_count = 0;
    foreach ( $transcript_data as $entry ) {
        if ( $line_count >= 15 ) break;
        
        if ( isset( $entry['speaker'] ) && isset( $entry['text'] ) ) {
            $transcript_excerpt .= $entry['speaker'] . ': ' . $entry['text'] . "\n";
            $line_count++;
        }
    }
    error_log( 'DND Speaker Mapping: Transcript excerpt created, lines: ' . $line_count );
    
    // Prompt für LLM erstellen
    $prompt = "Du bist ein Assistent für Dungeons & Dragons. Deine Aufgabe ist es, Sprecher aus einem Transkript den bekannten Spielern zuzuordnen.\n\n";
    $prompt .= "Hier sind die bekannten Spieler in der Gruppe:\n";
    foreach ( $mitspieler_list as $mitspieler ) {
        $prompt .= "- " . $mitspieler . "\n";
    }
    $prompt .= "\nHier sind die Sprecher, die im Transkript identifiziert wurden:\n";
    foreach ( $speakers as $speaker ) {
        $prompt .= "- " . $speaker . "\n";
    }
    $prompt .= "\nBasierend auf dem folgenden Transkript-Auszug, ordne jedem Sprecher einen Spieler zu. Gib deine Antwort NUR als valides JSON-Objekt zurück, das die Sprecher-ID auf den Spieler-Namen mappt.\n\n";
    $prompt .= "Beispiel: {\"Speaker 0\": \"Anna (Thalion)\", \"Speaker 1\": \"Max (Spielleiter)\"}\n\n";
    $prompt .= "Transkript-Auszug:\n\"\"\"\n" . $transcript_excerpt . "\"\"\"\n\n";
    $prompt .= "Antwort (NUR JSON):";
    
    // LLM API aufrufen
    error_log( 'DND Speaker Mapping: Calling Gemini API...' );
    error_log( 'DND Speaker Mapping: Prompt length: ' . strlen( $prompt ) );
    $ai_response = dnd_call_gemini_api( $api_key, $prompt );
    
    if ( is_wp_error( $ai_response ) ) {
        error_log( 'DND Speaker Mapping: Gemini API error: ' . $ai_response->get_error_message() );
        return $ai_response;
    }
    
    error_log( 'DND Speaker Mapping: Gemini API response received, length: ' . strlen( $ai_response ) );
    
    // JSON-Antwort parsen - Gemini kann zusätzlichen Text vor/nach JSON haben
    $ai_response = trim( $ai_response );
    
    // Versuche JSON zwischen {} zu extrahieren
    if ( preg_match( '/(\{.*\})/s', $ai_response, $matches ) ) {
        $json_part = $matches[1];
    } else {
        $json_part = $ai_response;
    }
    
    error_log( 'DND Speaker Mapping: Parsing JSON response: ' . $ai_response );
    
    $mapping_data = json_decode( $json_part, true );
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        error_log( 'DND Speaker Mapping JSON Error: ' . json_last_error_msg() . ' - Response: ' . $ai_response );
        return new WP_Error( 'invalid_json', 'LLM gab ungültiges JSON zurück: ' . $ai_response );
    }
    
    error_log( 'DND Speaker Mapping: Parsed mapping data: ' . print_r( $mapping_data, true ) );
    
    // Spieler-Namen zu Post-IDs konvertieren
    error_log( 'DND Speaker Mapping: Converting names to IDs...' );
    $speaker_mapping = array();
    foreach ( $mapping_data as $speaker => $player_name ) {
        error_log( 'DND Speaker Mapping: Looking for player: ' . $player_name . ' for speaker: ' . $speaker );
        
        // Mitspieler-Post-ID für Namen finden
        $mitspieler_posts = get_posts( array(
            'post_type' => 'dndt_mitspieler',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ) );
        
        // Manuell durch Titel filtern (case-insensitive und flexibel)
        $found = false;
        foreach ( $mitspieler_posts as $post ) {
            $post_title = trim( $post->post_title );
            $player_name_clean = trim( $player_name );
            
            // Exact match first
            if ( strcasecmp( $post_title, $player_name_clean ) === 0 ) {
                $speaker_mapping[ $speaker ] = $post->ID;
                error_log( 'DND Speaker Mapping: Exact match ' . $speaker . ' to ' . $player_name . ' (ID: ' . $post->ID . ')' );
                $found = true;
                break;
            }
            
            // Flexible matching: Check if player name contains character name or vice versa
            if ( stripos( $post_title, $player_name_clean ) !== false || stripos( $player_name_clean, $post_title ) !== false ) {
                $speaker_mapping[ $speaker ] = $post->ID;
                error_log( 'DND Speaker Mapping: Flexible match ' . $speaker . ' to ' . $player_name . ' (ID: ' . $post->ID . ')' );
                $found = true;
                break;
            }
            
            // Extract character name and player name from Gemini response
            if ( preg_match( '/(.+?)\s*\((.+?)\)/', $player_name_clean, $matches ) ) {
                $gemini_player = trim( $matches[1] );
                $gemini_char = trim( $matches[2] );
                
                // Check if post title contains either player name or character name
                if ( stripos( $post_title, $gemini_player ) !== false || stripos( $post_title, $gemini_char ) !== false ) {
                    $speaker_mapping[ $speaker ] = $post->ID;
                    error_log( 'DND Speaker Mapping: Pattern match ' . $speaker . ' to ' . $player_name . ' (ID: ' . $post->ID . ')' );
                    $found = true;
                    break;
                }
            }
        }
        
        if ( ! $found ) {
            error_log( 'DND Speaker Mapping: No match found for player: ' . $player_name );
        }
    }
    
    error_log( 'DND Speaker Mapping: Final mapping: ' . print_r( $speaker_mapping, true ) );
    
    // Speaker mapping speichern
    update_post_meta( $session_id, '_dndt_speaker_mapping', wp_json_encode( $speaker_mapping ) );
    
    error_log( 'DND Speaker Mapping: Successfully completed for Session ' . $session_id );
    return true;
}

/**
 * Trigger-Funktion für automatische Sprecher-Zuordnung nach Session-Erstellung.
 * 
 * @param int $session_id ID der neu erstellten Session
 */
function dnd_trigger_automatic_speaker_mapping( $session_id ) {
    // Prüfen ob Session existiert und Typ stimmt
    $post = get_post( $session_id );
    if ( ! $post || $post->post_type !== 'dndt_session' ) {
        return;
    }
    
    // Prüfen ob bereits ein Mapping existiert
    $existing_mapping = get_post_meta( $session_id, '_dndt_speaker_mapping', true );
    if ( ! empty( $existing_mapping ) ) {
        return; // Bereits vorhanden, nicht überschreiben
    }
    
    // Automatische Zuordnung ausführen
    $result = dnd_llm_map_speakers( $session_id );
    
    // Optional: Fehler loggen
    if ( is_wp_error( $result ) ) {
        error_log( 'DND Speaker Mapping Error for Session ' . $session_id . ': ' . $result->get_error_message() );
    }
}

/**
 * Hilfsfunktion für Gemini API Aufrufe (wiederverwendbar).
 * 
 * @param string $api_key Gemini API Key
 * @param string $prompt Der Prompt für die AI
 * @return string|WP_Error AI-Antwort oder WP_Error bei Fehler
 */
function dnd_call_gemini_api( $api_key, $prompt ) {
    $model = get_option( 'dndt_gemini_model', 'gemini-2.0-flash-thinking-exp' );
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $api_key;
    
    // Set timeout based on model type
    $timeout = 300; // Default 5 minutes
    if ( strpos( $model, '2.5-pro' ) !== false ) {
        $timeout = 600; // 10 minutes for Pro models
    } elseif ( strpos( $model, 'thinking' ) !== false ) {
        $timeout = 480; // 8 minutes for thinking models
    }
    
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
            'maxOutputTokens' => 32768,
        )
    );
    
    $response = wp_remote_post( $url, array(
        'headers' => array(
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode( $body ),
        'timeout' => $timeout
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
    
    // Debug: Log the actual API response structure
    error_log( 'Gemini API Response (Campaign Update): ' . print_r( $data, true ) );
    
    if ( ! isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
        error_log( 'Gemini API Error: Expected structure not found in response' );
        return new WP_Error( 'gemini_api_error', 'Unerwartete API-Antwort. Response: ' . substr( $response_body, 0, 500 ) );
    }
    
    // Check if response was truncated due to token limits
    if ( isset( $data['candidates'][0]['finishReason'] ) && $data['candidates'][0]['finishReason'] === 'MAX_TOKENS' ) {
        error_log( 'Gemini API Warning: Response was truncated due to MAX_TOKENS limit' );
        return new WP_Error( 'gemini_api_error', 'KI-Antwort wurde wegen Token-Limit abgeschnitten. Bitte versuchen Sie es mit kürzeren Eingaben erneut.' );
    }
    
    return $data['candidates'][0]['content']['parts'][0]['text'];
}

/**
 * Formatiert ein Transkript mit Sprecher-Zuordnung.
 * 
 * @param int $session_id ID der Session
 * @return string Formatiertes Transkript mit Charakternamen
 */
function dnd_format_transcript_with_speakers( $session_id ) {
    // Strukturierte Transkriptdaten abrufen
    $transcript_data = get_post_meta( $session_id, '_dndt_processed_transcript', true );
    
    if ( empty( $transcript_data ) ) {
        // Fallback auf originalen Content
        $session = get_post( $session_id );
        return $session ? $session->post_content : '';
    }
    
    // Decode JSON if needed
    if ( is_string( $transcript_data ) ) {
        $transcript_data = json_decode( $transcript_data, true );
    }
    
    if ( ! is_array( $transcript_data ) ) {
        // Fallback auf originalen Content
        $session = get_post( $session_id );
        return $session ? $session->post_content : '';
    }
    
    // Sprecher-Zuordnung abrufen
    $speaker_mapping = get_post_meta( $session_id, '_dndt_speaker_mapping', true );
    if ( is_string( $speaker_mapping ) && ! empty( $speaker_mapping ) ) {
        $speaker_mapping = json_decode( $speaker_mapping, true );
    }
    if ( ! is_array( $speaker_mapping ) ) {
        $speaker_mapping = array();
    }
    
    // Transkript formatieren
    $formatted_content = '';
    foreach ( $transcript_data as $entry ) {
        if ( isset( $entry['speaker'] ) && isset( $entry['text'] ) ) {
            $speaker_raw = $entry['speaker'];
            $text = $entry['text'];
            
            // Normalisiere Speaker-Namen: "A" → "Speaker A", "B" → "Speaker B"
            $speaker_normalized = $speaker_raw;
            if ( preg_match( '/^[A-Z]$/', $speaker_raw ) ) {
                $speaker_normalized = 'Speaker ' . $speaker_raw;
            }
            
            // Prüfe ob eine Zuordnung existiert
            if ( isset( $speaker_mapping[ $speaker_normalized ] ) ) {
                $mitspieler_id = $speaker_mapping[ $speaker_normalized ];
                $mitspieler = get_post( $mitspieler_id );
                
                if ( $mitspieler ) {
                    // Verwende den Charakternamen aus der Zuordnung
                    $display_name = $mitspieler->post_title;
                } else {
                    // Fallback auf normalisierten Speaker-Namen
                    $display_name = $speaker_normalized;
                }
            } else {
                // Fallback auf normalisierten Speaker-Namen
                $display_name = $speaker_normalized;
            }
            
            // Als Dialog formatieren
            $formatted_content .= "<p><strong>{$display_name}:</strong> " . esc_html( $text ) . "</p>\n";
        }
    }
    
    return $formatted_content;
}