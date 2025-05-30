<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX Handler zum Senden einer Chat-Nachricht oder eines Würfelwurfs.
 */
function dnd_ajax_send_message() {
    // 1. Sicherheit prüfen: Nonce und eingeloggt?
    check_ajax_referer( 'dnd_chat_nonce', '_ajax_nonce' ); // Prüft Nonce und bricht bei Fehler ab
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => __( 'Fehler: Nicht eingeloggt.', 'dnd-helper' ) ), 403 ); // 403 Forbidden
    }

    // 2. Nachricht aus POST-Daten holen und bereinigen
    if ( ! isset( $_POST['message'] ) || empty( trim( $_POST['message'] ) ) ) {
         wp_send_json_error( array( 'message' => __( 'Fehler: Leere Nachricht gesendet.', 'dnd-helper' ) ), 400 ); // 400 Bad Request
    }
    // trim() entfernt Leerzeichen am Anfang/Ende
    $raw_message = trim( $_POST['message'] );
    // Zuerst prüfen wir auf Würfelbefehl, dann ggf. sanitizen

    $user_id = get_current_user_id();
    $user_info = get_userdata( $user_id );
    $user_display_name = $user_info ? $user_info->display_name : __( 'Unbekannt', 'dnd-helper' );

    $message_type = 'message'; // Standardtyp
    $message_content = '';
    $roll_details_json = null;

    // 3. Prüfen, ob es ein Würfelbefehl ist (z.B. /roll 1d20+5)
    // Wir verwenden hier absichtlich einen einfachen Prefix, der in dndChatData definiert ist
    // TODO: Lade den Prefix aus dndChatData (technisch schwierig hier, evtl. fix definieren)
    $roll_prefix = '/roll '; // Feste Definition hier, muss zu JS passen
    $roll_prefix_short = '/r '; // Alternative

    if ( strpos( $raw_message, $roll_prefix ) === 0 || strpos( $raw_message, $roll_prefix_short ) === 0 ) {
        $is_roll = true;
        $roll_string = '';
        if (strpos( $raw_message, $roll_prefix ) === 0) {
             $roll_string = trim( substr( $raw_message, strlen( $roll_prefix ) ) );
        } else {
             $roll_string = trim( substr( $raw_message, strlen( $roll_prefix_short ) ) );
        }


        if ( empty( $roll_string ) ) {
             wp_send_json_error( array( 'message' => __( 'Fehler: Bitte gib an, was gewürfelt werden soll (z.B. /roll 1d20+2).', 'dnd-helper' ) ), 400 );
        }

        // 4. Würfelbefehl parsen und ausführen (Funktion kommt als nächstes)
        $roll_result = dnd_parse_and_roll_dice( $roll_string );

        if ( $roll_result['success'] ) {
            $message_type = 'roll';
            // Formatierten Output für den Chat verwenden
            $message_content = sprintf(
                '%s %s %s: %s',
                esc_html( $user_display_name ),
                __( 'würfelt', 'dnd-helper' ),
                esc_html( $roll_result['input'] ), // Zeige, was der User eingegeben hat
                $roll_result['formatted_output'] // Zeige das Ergebnis
            );
            // Details als JSON speichern
            $roll_details_json = wp_json_encode( $roll_result ); // Speichert input, rolls, modifier, total etc.

        } else {
            // Fehler beim Parsen/Würfeln
             wp_send_json_error( array( 'message' => $roll_result['error'] ), 400 );
        }

    } else {
    // 5. Normale Nachricht: Sanitize!
    $message_type = 'message';
    $sanitized_text = sanitize_textarea_field( $raw_message );

    if ( empty( $sanitized_text ) ) {
         wp_send_json_error( array( 'message' => __( 'Fehler: Leere oder ungültige Nachricht.', 'dnd-helper' ) ), 400 );
    }
     // Baue den HTML-String manuell zusammen, um %%-Problem zu vermeiden
     $message_content = '<span class="message-meta"><strong>' . esc_html($user_display_name) . '</strong> <span class="timestamp">%%TIMESTAMP%%</span></span><span class="message-content">' . nl2br(esc_html($sanitized_text)) . '</span>'; // nl2br + esc_html statt sanitize_textarea? Oder umgekehrt? Teste was besser passt.

}

    // 6. Nachricht in die Datenbank speichern
    global $wpdb;
    $table_name = $wpdb->prefix . 'dnd_chat_messages';

    $insert_result = $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'message_content' => $message_content, // Der formatierte String
            'message_type' => $message_type,
            'roll_details' => $roll_details_json, // JSON String oder NULL
            'timestamp' => current_time( 'mysql', 1 ) // GMT Zeit für Konsistenz
        ),
        array(
            '%d', // user_id
            '%s', // message_content
            '%s', // message_type
            '%s', // roll_details (kann lang sein, %s ist ok)
            '%s'  // timestamp
        )
    );

    // 7. Ergebnis zurückgeben
    if ( $insert_result === false ) {
        error_log( 'DND Helper DB Error: Konnte Chatnachricht nicht einfügen - ' . $wpdb->last_error );
        wp_send_json_error( array( 'message' => __( 'Fehler: Nachricht konnte nicht in der Datenbank gespeichert werden.', 'dnd-helper' ) ), 500 ); // 500 Internal Server Error
    } else {
        // Erfolg! Wir müssen dem Client eigentlich nichts zurückgeben, außer dass es geklappt hat.
        // Die Nachricht wird sowieso über das Polling abgefragt.
         wp_send_json_success( array( 'message' => 'Nachricht gesendet.' ) ); // Einfache Erfolgsbestätigung
    }
}
// Hook für eingeloggte Benutzer (Standard WordPress AJAX)
add_action( 'wp_ajax_dnd_send_message', 'dnd_ajax_send_message' );
// Optional: Hook für nicht eingeloggte Benutzer (falls man das erlauben wollte)
// add_action( 'wp_ajax_nopriv_dnd_send_message', 'dnd_ajax_send_message' );


/**
 * Parst einen komplexeren Würfelstring (z.B. "1d8+1d6+5", "2d10 - 1d4 + 3")
 * und führt den Wurf aus.
 *
 * @param string $roll_string Der zu parsende String.
 * @return array Mit 'success' (bool), und bei Erfolg 'input', 'parts' (array of details), 'total' (int), 'formatted_output' (string). Bei Fehler 'error' (string).
 */
function dnd_parse_and_roll_dice( $roll_string ) {
    // Bereinigen: Kleinschreibung, überflüssige Leerzeichen entfernen
    $input_string = trim( preg_replace( '/\s+/', '', strtolower( $roll_string ) ) );

    // Initiales Ergebnis-Array
    $result = [
        'success' => false,
        'input'   => $roll_string, // Original-Input des Users (für die Anzeige)
        'parsed'  => $input_string, // Bereinigter String für Debugging
        'parts'   => [],           // Details zu jedem Teil (Würfelwurf oder Modifikator)
        'total'   => 0,
        'formatted_output' => '',
        'error'   => __( 'Ungültiges Würfelformat oder Ausdruck.', 'dnd-helper' )
    ];

    // Regex, um alle Teile zu finden: (+ oder - optional), dann (XdY oder nur eine Zahl)
    // Beispiel: +1d8, -5, 2d6
    // Pattern Explanation:
    // ([+-])?      : Group 1: Optional + or - sign
    // (?:          : Start Non-capturing group for dice OR number
    //   (\d+)? d (\d+) : Dice part: Group 2 (Optional count X), Group 3 (Type Y)
    //   |          : OR
    //   (\d+)      : Number part: Group 4 (Plain number Z)
    // )           : End Non-capturing group
    $pattern = '/([+-])?(?:(\d+)?d(\d+)|(\d+))/i';

    if ( ! preg_match_all( $pattern, $input_string, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
        // Wenn gar nichts dem Muster entspricht (z.B. nur "abc")
        return $result; // Gibt Standardfehler zurück
    }

    // --- Verarbeite die gefundenen Teile ---
    $current_total = 0;
    $output_parts = []; // Teile für die formatierte Ausgabe
    $last_offset_end = 0; // Um zu prüfen, ob der ganze String verarbeitet wurde

    foreach ( $matches as $match ) {
        // Prüfen ob der Anfang des Matches direkt am Ende des letzten liegt
        $current_offset_start = $match[0][1];
        if ($current_offset_start !== $last_offset_end) {
             // Da ist ein Zeichen zwischen den Matches, das nicht erlaubt ist (z.B. "1d8*5")
             $result['error'] = __('Ungültige Zeichen im Würfelausdruck gefunden.', 'dnd-helper');
             return $result;
        }
        $last_offset_end = $current_offset_start + strlen($match[0][0]);

        $sign = '+';
        // $match[1] ist der optionale Operator (+ oder -)
        if ( isset( $match[1] ) && $match[1][0] === '-' ) {
            $sign = '-';
        }

        $part_value = 0;
        $part_details = ['text' => $match[0][0]]; // Der gesamte gefundene Teil (z.B. "+1d8")

        // Ist es ein Würfelwurf (XdY)? Gruppe 3 (Y) wäre gesetzt.
        if ( isset( $match[3] ) && $match[3][0] !== '' && $match[3][1] !== -1 ) { // Check auf Offset -1 für leere optionale Gruppe
            $num_dice = ( isset( $match[2] ) && $match[2][0] !== '' && $match[2][1] !== -1 ) ? intval( $match[2][0] ) : 1;
            $die_type = intval( $match[3][0] );
            $part_details['type'] = 'dice';
            $part_details['num_dice'] = $num_dice;
            $part_details['die_type'] = $die_type;

            // Validierung
            if ( $num_dice <= 0 || $num_dice > 100 ) {
                $result['error'] = __( 'Fehler: Ungültige Anzahl Würfel (1-100 erlaubt).', 'dnd-helper' ); return $result;
            }
            if ( $die_type <= 1 || $die_type > 1000 ) {
                 $result['error'] = __( 'Fehler: Ungültiger Würfeltyp (d2-d1000 erlaubt).', 'dnd-helper' ); return $result;
            }

            // Würfeln
            $rolls = [];
            $sum = 0;
            for ( $i = 0; $i < $num_dice; $i++ ) {
                try {
                    $roll = random_int( 1, $die_type );
                    $rolls[] = $roll;
                    $sum += $roll;
                } catch ( Exception $e ) {
                    error_log( 'DND Helper Dice Error: random_int failed - ' . $e->getMessage() );
                    $result['error'] = __( 'Fehler beim Generieren der Zufallszahlen.', 'dnd-helper' ); return $result;
                }
            }
            $part_value = $sum;
            $part_details['rolls'] = $rolls;
            $part_details['sum'] = $sum;
            $output_parts[] = $sign . $part_details['num_dice'] . 'd' . $part_details['die_type'] . '[' . implode( ',', $rolls ) . ']';

        }
        // Ist es eine Zahl (Modifikator)? Gruppe 4 wäre gesetzt.
        elseif ( isset( $match[4] ) && $match[4][0] !== '' && $match[4][1] !== -1 ) {
            $part_value = intval( $match[4][0] );
            $part_details['type'] = 'modifier';
            $part_details['value'] = $part_value;
            $output_parts[] = $sign . $part_value;

        } else {
             // Sollte nicht passieren, wenn preg_match_all erfolgreich war und das Pattern stimmt
             $result['error'] = __( 'Interner Parsing-Fehler.', 'dnd-helper' ); return $result;
        }

        // Wert zum Gesamtergebnis addieren/subtrahieren
        if ( $sign === '-' ) {
            $current_total -= $part_value;
            $part_details['signed_value'] = -$part_value;
        } else {
            $current_total += $part_value;
            $part_details['signed_value'] = +$part_value;
        }
        $result['parts'][] = $part_details;

    } // End foreach match

    // Prüfen, ob der gesamte String verarbeitet wurde
     if ($last_offset_end !== strlen($input_string)) {
         $result['error'] = __('Ungültige Zeichen am Ende des Würfelausdrucks gefunden.', 'dnd-helper');
         return $result;
     }


    // --- Ergebnis aufbereiten ---
    $result['success'] = true;
    $result['total'] = $current_total;
    unset( $result['error'] );

    // Formatierten Output bauen (z.B. "+1d8[5] +1d6[3] +5 = 13")
    // Ersten Operator entfernen, wenn es '+' ist
    $formatted_output = ltrim( implode( ' ', $output_parts ), '+' );
    $result['formatted_output'] = $formatted_output . ' = <strong>' . $current_total . '</strong>';


    return $result;
}

/**
 * AJAX Handler zum Abrufen neuer Chat-Nachrichten seit einem bestimmten Zeitstempel/ID.
 */
function dnd_ajax_get_new_messages() {
    // 1. Sicherheit
    check_ajax_referer( 'dnd_chat_nonce', '_ajax_nonce' );
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => __( 'Fehler: Nicht eingeloggt.', 'dnd-helper' ) ), 403 );
    }

    // 2. Letzte bekannte ID oder Zeitstempel holen (wir verwenden ID, da genauer als Timestamp)
    $last_id = isset( $_REQUEST['since_id'] ) ? intval( $_REQUEST['since_id'] ) : 0; // 'since_id' statt 'since'

    // 3. Nachrichten aus der DB holen
    global $wpdb;
    $table_name = $wpdb->prefix . 'dnd_chat_messages';
    $current_user_id = get_current_user_id();

    // Hole Nachrichten, die neuer (größere ID) als die letzte bekannte ID sind.
    // Hole auch User Display Name direkt mit einem JOIN.
    $sql = $wpdb->prepare(
        "SELECT chat.id, chat.user_id, chat.message_content, chat.message_type, chat.roll_details, chat.timestamp, users.display_name
         FROM $table_name AS chat
         LEFT JOIN {$wpdb->users} AS users ON chat.user_id = users.ID
         WHERE chat.id > %d
         ORDER BY chat.id ASC
         LIMIT 50", // Limit, um Server nicht zu überlasten
        $last_id
    );

    $new_messages_raw = $wpdb->get_results( $sql, ARRAY_A ); // Ergebnis als assoziatives Array

    if ( $new_messages_raw === null ) {
        // DB Fehler
        error_log("DND Helper DB Error: Fehler beim Abrufen neuer Nachrichten - " . $wpdb->last_error);
        wp_send_json_error( array( 'message' => __( 'Datenbankfehler beim Abrufen der Nachrichten.', 'dnd-helper' ) ), 500 );
    }

    // 4. Nachrichten für JS aufbereiten
    $messages_for_js = [];
    $highest_id = $last_id; // Wichtig für den nächsten Request

    foreach ( $new_messages_raw as $msg ) {
        $roll_details_data = null;
        if ($msg['message_type'] === 'roll' && !empty($msg['roll_details'])) {
            $roll_details_data = json_decode($msg['roll_details'], true);
            // Fehler beim Dekodieren ignorieren wir hier mal, sollte nicht passieren
        }

        // Zeitstempel formatieren (optional, kann auch JS machen)
        // $formatted_timestamp = mysql2date( get_option('date_format') . ' ' . get_option('time_format'), $msg['timestamp'] );
        // Wir übergeben den GMT-Timestamp, JS kann ihn lokal formatieren

        $messages_for_js[] = array(
            'id' => intval($msg['id']),
            'user_id' => intval($msg['user_id']),
            'user_name' => $msg['display_name'] ?? __('Unbekannt', 'dnd-helper'),
            'content' => $msg['message_content'], // Enthält bereits formatierten String (mit HTML für User/Zeit)
            'type' => $msg['message_type'],
            'timestamp_gmt' => $msg['timestamp'], // GMT Timestamp übergeben
            'roll_details' => $roll_details_data, // Dekodiertes Array oder null
            'is_own' => (intval($msg['user_id']) === $current_user_id) // Flag für eigene Nachrichten
        );
        if (intval($msg['id']) > $highest_id) {
             $highest_id = intval($msg['id']);
        }
    }

    // 5. Ergebnis senden
    wp_send_json_success( array(
        'messages' => $messages_for_js,
        'last_id' => $highest_id // Die höchste ID der gerade gesendeten Nachrichten
    ) );
}
add_action( 'wp_ajax_dnd_get_new_messages', 'dnd_ajax_get_new_messages' );


/**
 * AJAX Handler zum initialen Laden der letzten X Nachrichten.
 */
function dnd_ajax_get_initial_messages() {
     // 1. Sicherheit
    check_ajax_referer( 'dnd_chat_nonce', '_ajax_nonce' );
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => __( 'Fehler: Nicht eingeloggt.', 'dnd-helper' ) ), 403 );
    }

    // 2. Anzahl holen
    $count = isset( $_REQUEST['count'] ) ? intval( $_REQUEST['count'] ) : 20; // Standard 20
    $count = max(1, min(100, $count)); // Begrenzen zwischen 1 und 100

    // 3. Nachrichten aus der DB holen (die letzten X)
    global $wpdb;
    $table_name = $wpdb->prefix . 'dnd_chat_messages';
    $current_user_id = get_current_user_id();

    // Hole die letzten X Nachrichten (höchste IDs) in aufsteigender Reihenfolge (älteste zuerst)
    // Subquery, um die letzten IDs zu bekommen, dann die Daten dazu holen.
    $sql = $wpdb->prepare(
        "SELECT chat.id, chat.user_id, chat.message_content, chat.message_type, chat.roll_details, chat.timestamp, users.display_name
         FROM $table_name AS chat
         LEFT JOIN {$wpdb->users} AS users ON chat.user_id = users.ID
         WHERE chat.id IN (
             SELECT id FROM (
                 SELECT id FROM $table_name ORDER BY id DESC LIMIT %d
             ) AS last_ids
         )
         ORDER BY chat.id ASC",
        $count
    );

    $initial_messages_raw = $wpdb->get_results( $sql, ARRAY_A );

     if ( $initial_messages_raw === null ) {
        error_log("DND Helper DB Error: Fehler beim initialen Laden der Nachrichten - " . $wpdb->last_error);
        wp_send_json_error( array( 'message' => __( 'Datenbankfehler beim initialen Laden der Nachrichten.', 'dnd-helper' ) ), 500 );
    }

     // 4. Nachrichten für JS aufbereiten (Code wie oben)
    $messages_for_js = [];
    $highest_id = 0;

    foreach ( $initial_messages_raw as $msg ) {
        $roll_details_data = null;
        if ($msg['message_type'] === 'roll' && !empty($msg['roll_details'])) {
            $roll_details_data = json_decode($msg['roll_details'], true);
        }

        $messages_for_js[] = array(
            'id' => intval($msg['id']),
            'user_id' => intval($msg['user_id']),
            'user_name' => $msg['display_name'] ?? __('Unbekannt', 'dnd-helper'),
            'content' => $msg['message_content'],
            'type' => $msg['message_type'],
            'timestamp_gmt' => $msg['timestamp'],
            'roll_details' => $roll_details_data,
            'is_own' => (intval($msg['user_id']) === $current_user_id)
        );
         if (intval($msg['id']) > $highest_id) {
             $highest_id = intval($msg['id']);
        }
    }

     // 5. Ergebnis senden
    wp_send_json_success( array(
        'messages' => $messages_for_js,
        'last_id' => $highest_id
    ) );
}
add_action( 'wp_ajax_dnd_get_initial_messages', 'dnd_ajax_get_initial_messages' );

/**
 * AJAX Handler zum Aktualisieren eines einzelnen dedizierten Charakter-Meta-Feldes.
 */
function dnd_ajax_update_character_field() {
    // 1. Sicherheit: Nonce prüfen (eigene Nonce dafür!)
    check_ajax_referer( 'dnd_update_field_nonce', '_ajax_nonce' );

    // 2. Eingaben holen und validieren
    if ( ! isset( $_POST['character_id'], $_POST['meta_key'], $_POST['new_value'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Fehler: Unvollständige Daten.', 'dnd-helper' ) ), 400 );
    }

    $character_id = intval( $_POST['character_id'] );
    $meta_key = sanitize_key( $_POST['meta_key'] ); // Meta-Key bereinigen (nur erlaubte Zeichen)
    $new_value_raw = $_POST['new_value'];

    // 3. Berechtigungsprüfung
    if ( ! current_user_can( 'edit_post', $character_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Fehler: Keine Berechtigung.', 'dnd-helper' ) ), 403 );
    }

    // 4. Post-Typ-Prüfung (optional aber gut)
    if ( get_post_type( $character_id ) !== 'dnd_character' ) {
         wp_send_json_error( array( 'message' => __( 'Fehler: Ungültige Charakter-ID.', 'dnd-helper' ) ), 404 );
    }

    // 5. Whitelist für erlaubte Meta Keys (SEHR WICHTIG!)
    $allowed_meta_keys = [
        '_dnd_hp_current',
        '_dnd_hp_temporary',
        '_dnd_hp_max',
        '_dnd_hitdice_current',
		'_dnd_deathsaves_successes',
        '_dnd_deathsaves_failures',
        '_dnd_deathsaves_successes',
        '_dnd_deathsaves_failures',
        '_dnd_currency_gp',
        '_dnd_xp',
    ];
    if ( ! in_array( $meta_key, $allowed_meta_keys, true ) || strpos($meta_key, '_dnd_') !== 0) { // Sicherstellen, dass es unser Prefix hat
         wp_send_json_error( array( 'message' => __( 'Fehler: Dieses Feld darf nicht direkt bearbeitet werden.', 'dnd-helper' ) . ' Key: ' . $meta_key ), 403 );
    }

    // 6. Wert validieren/bereinigen (basierend auf Key oder Typ?)
    // Einfache Methode: Für die meisten hier definierten Felder ist intval() ok.
    $sanitized_value = null; // Default
	if (strpos($meta_key, '_dnd_deathsaves_') === 0) { // Prüfe auf Death Save Keys
        $sanitized_value = intval($new_value_raw);
        $sanitized_value = max(0, min(3, $sanitized_value)); // Auf 0-3 begrenzen
    }

	elseif ( in_array($meta_key, ['_dnd_hp_current', '_dnd_hp_temporary', '_dnd_hp_max', '_dnd_hitdice_current', '_dnd_xp', '_dnd_currency_gp']) ) { // Beispiel für andere Integer-Felder
         $sanitized_value = intval($new_value_raw);
         if ($meta_key === '_dnd_hp_temporary' && $sanitized_value < 0) {
             $sanitized_value = 0; // Keine negativen Temp HP
         }
         // Ggf. weitere Checks (z.B. Währung nicht negativ)
         if (strpos($meta_key, '_dnd_currency_') === 0 && $sanitized_value < 0) {
             $sanitized_value = 0;
         }
    }
	else {
         // Fallback oder spezifischer Fehler für unbekannte Keys in der Whitelist
         // Fürs Erste nehmen wir intval als Default für die Whitelist-Keys
          $sanitized_value = intval($new_value_raw);
    }
    // 7. Meta-Feld aktualisieren
    if ( update_post_meta( $character_id, $meta_key, $sanitized_value ) ) {
        // Erfolg! Sende den gespeicherten Wert zurück
        wp_send_json_success( array(
            'message' => __('Wert gespeichert.', 'dnd-helper'),
            'meta_key' => $meta_key,
            'new_value' => $sanitized_value // Der bereinigte, gespeicherte Wert
        ) );
    } else {
        // Fehler ODER Wert war identisch (update_post_meta gibt false bei identischem Wert zurück)
        // Um zu unterscheiden, hole den aktuellen Wert und vergleiche
        $current_value = get_post_meta( $character_id, $meta_key, true );
         if ($current_value == $sanitized_value) { // Typunsicherer Vergleich kann hier ok sein
             // Wert war schon korrekt, kein Fehler
             wp_send_json_success( array(
                'message' => __('Wert war bereits aktuell.', 'dnd-helper'),
                'meta_key' => $meta_key,
                'new_value' => $sanitized_value
            ) );
         } else {
            // Echter Fehler beim Speichern
            error_log("DND AJAX Update Error: update_post_meta failed for Post $character_id, Key $meta_key");
            wp_send_json_error( array( 'message' => __( 'Fehler: Wert konnte nicht gespeichert werden.', 'dnd-helper' ) ), 500 );
         }
    }
}
add_action( 'wp_ajax_dnd_update_character_field', 'dnd_ajax_update_character_field' );

/**
 * AJAX Handler zum Abrufen des gerenderten HTML für ein Charakterblatt.
 */
function dnd_ajax_get_character_sheet_html() {
    // 1. Sicherheit: Nonce prüfen
    check_ajax_referer( 'dnd_load_sheet_nonce', '_ajax_nonce' ); // Eigene Nonce für diese Aktion

    // 2. Eingaben holen
    if ( ! isset( $_POST['character_id'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Fehler: Charakter-ID fehlt.', 'dnd-helper' ) ), 400 );
    }
    $character_id = intval( $_POST['character_id'] );

    // 3. Berechtigungsprüfung: Gehört der Charakter dem eingeloggten User?
    if ( ! is_user_logged_in() ) {
         wp_send_json_error( array( 'message' => __( 'Fehler: Nicht eingeloggt.', 'dnd-helper' ) ), 403 );
    }
    $user_id = get_current_user_id();
    $post_author = get_post_field( 'post_author', $character_id );

    // Prüfen ob der Post existiert, ein Charakter ist und dem User gehört
    if ( !$post_author || get_post_type($character_id) !== 'dnd_character' || intval($post_author) !== $user_id ) {
         wp_send_json_error( array( 'message' => __( 'Fehler: Charakter nicht gefunden oder keine Berechtigung.', 'dnd-helper' ) ), 403 );
    }

    // 4. HTML generieren über die ausgelagerte Funktion
    // Lade die Funktion, falls sie in einer anderen Datei ist
    // require_once DND_HELPER_PLUGIN_DIR . 'includes/template-tags.php';
    $sheet_html = dnd_get_rendered_character_sheet_html( $character_id );

    if ( strpos($sheet_html, 'dnd-error') !== false ) { // Einfacher Check auf Fehlermeldung
         wp_send_json_error( array( 'message' => __( 'Fehler beim Laden der Charakterdaten.', 'dnd-helper' ), 'html' => $sheet_html ) );
    } else {
        wp_send_json_success( array( 'html' => $sheet_html ) );
    }
}
add_action( 'wp_ajax_dnd_get_character_sheet_html', 'dnd_ajax_get_character_sheet_html' );


/**
 * AJAX Handler zum Aktualisieren der benutzten Zauberplätze.
 */
function dnd_ajax_update_spell_slot() {
	    error_log("===== AJAX Update Spell Slot START =====");
    // 1. Sicherheit: Nonce
	error_log("AJAX Update Slot: Checking Nonce...");
		if ( ! check_ajax_referer( 'dnd_update_slot_nonce', '_ajax_nonce', false ) ) {
			 error_log("AJAX Update Slot: ERROR - Nonce check failed.");
			 wp_send_json_error( array( 'message' => 'Nonce Error' ), 403 );
		}
		error_log("AJAX Update Slot: Nonce OK.");
    // 2. Eingaben holen
    if ( ! isset( $_POST['character_id'], $_POST['slot_level'], $_POST['new_used_count'] ) ) {
        error_log("AJAX Update Slot: ERROR - Missing POST data.");
        wp_send_json_error( /*...*/ );
    }
    $character_id = intval( $_POST['character_id'] );
    $slot_level = intval( $_POST['slot_level'] );
    $new_used_count = intval( $_POST['new_used_count'] );
    error_log("AJAX Update Slot: Input - CharID: $character_id, Level: $slot_level, Used: $new_used_count"); // LOG INPUT

    // 3. Berechtigung
    if ( ! current_user_can( 'edit_post', $character_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Fehler: Keine Berechtigung.', 'dnd-helper' ) ), 403 );
    }

    // 4. Post-Typ (optional)
    if ( get_post_type( $character_id ) !== 'dnd_character' ) {
         wp_send_json_error( array( 'message' => __( 'Fehler: Ungültige Charakter-ID.', 'dnd-helper' ) ), 404 );
    }

    // 5. Aktuelles Slot-Array laden
    $meta_key = '_dnd_spell_slots';
    error_log("AJAX Update Slot: Attempting to get meta key '$meta_key' for Post ID $character_id..."); // LOG VOR GET
    $slots_json = get_post_meta( $character_id, $meta_key, true );
    error_log("AJAX Update Slot: Raw data received from get_post_meta('$meta_key'): " . print_r($slots_json, true)); // LOG NACH GET

    $slots_data = null;
    $found_slot = false;
    $max_slots = 0;

    if (!empty($slots_json)) {
		error_log("AJAX Update Slot: Meta value is not empty. Decoding...");
        $slots_data = json_decode($slots_json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($slots_data)) {
             wp_send_json_error( array( 'message' => __( 'Fehler: Slot-Daten sind korrupt.', 'dnd-helper' ) ), 500 );
        }
    } else {
        // Wenn keine Daten da sind, können wir nichts ändern
		error_log("AJAX Update Slot: ERROR - Meta value for '$meta_key' is considered EMPTY by PHP."); // LOG HIER WICHTIG
        wp_send_json_error( array( 'message' => __( 'Fehler: Keine Slot-Daten gefunden.', 'dnd-helper' ) ), 500 );
    }

    // 6. Slot finden und Wert validieren/aktualisieren
    foreach ($slots_data as $index => $slot_info) {
        if (isset($slot_info['level']) && intval($slot_info['level']) === $slot_level) {
            $found_slot = true;
            $max_slots = isset($slot_info['total']) ? intval($slot_info['total']) : 0;

            // Validierung: Neuer Wert muss zwischen 0 und max liegen
            if ($new_used_count < 0 || $new_used_count > $max_slots) {
                 wp_send_json_error( array( 'message' => sprintf(__('Ungültiger Wert für Grad %d (muss zwischen 0 und %d sein).', 'dnd-helper'), $slot_level, $max_slots) ), 400 );
            }

            // Wert im Array aktualisieren
            $slots_data[$index]['used'] = $new_used_count;
            break; // Slot gefunden, Schleife beenden
        }
    }

    if (!$found_slot) {
         wp_send_json_error( array( 'message' => sprintf(__('Fehler: Slot-Grad %d nicht gefunden.', 'dnd-helper'), $slot_level) ), 404 );
    }

    // 7. Aktualisiertes Array zurück als JSON speichern
    $updated_slots_json = wp_json_encode( $slots_data ); // Keine spezielle Formatierung nötig
    if ( ! $updated_slots_json ) {
         wp_send_json_error( array( 'message' => __( 'Fehler: Konnte Slot-Daten nicht neu kodieren.', 'dnd-helper' ) ), 500 );
    }

    if ( update_post_meta( $character_id, $meta_key, $updated_slots_json ) ) {
        wp_send_json_success( array(
            'message' => __('Zauberplatz aktualisiert.', 'dnd-helper'),
            'level' => $slot_level,
            'new_value' => $new_used_count // Der gespeicherte Wert
        ) );
    } else {
        // Prüfen ob Wert identisch war
        $current_json_check = get_post_meta( $character_id, $meta_key, true );
        if ($current_json_check === $updated_slots_json) {
              wp_send_json_success( array(
                'message' => __('Wert war bereits aktuell.', 'dnd-helper'),
                'level' => $slot_level,
                'new_value' => $new_used_count
             ) );
        } else {
            // Echter Speicherfehler
             error_log("DND AJAX Update Error: update_post_meta failed for Slots, Post $character_id");
             wp_send_json_error( array( 'message' => __( 'Fehler: Slot-Daten konnten nicht gespeichert werden.', 'dnd-helper' ) ), 500 );
        }
    }
}
add_action( 'wp_ajax_dnd_update_spell_slot', 'dnd_ajax_update_spell_slot' );

/**
 * AJAX Handler zum Aktualisieren der aktuellen Nutzung einer begrenzten Ressource.
 */
function dnd_ajax_update_limited_use() {
    // 1. Sicherheit: Nonce
    check_ajax_referer( 'dnd_update_resource_nonce', '_ajax_nonce' ); // Eigene Nonce

    // 2. Eingaben holen
    if ( ! isset( $_POST['character_id'], $_POST['resource_index'], $_POST['new_current_value'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Fehler: Unvollständige Daten.', 'dnd-helper' ) ), 400 );
    }
    $character_id = intval( $_POST['character_id'] );
    $resource_index = intval( $_POST['resource_index'] ); // Index der Ressource im Array
    $new_current_value = intval( $_POST['new_current_value'] );

    // 3. Berechtigung
    if ( ! current_user_can( 'edit_post', $character_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Fehler: Keine Berechtigung.', 'dnd-helper' ) ), 403 );
    }

    // 4. Post-Typ (optional)
    if ( get_post_type( $character_id ) !== 'dnd_character' ) {
         wp_send_json_error( array( 'message' => __( 'Fehler: Ungültige Charakter-ID.', 'dnd-helper' ) ), 404 );
    }

    // 5. Aktuelles Ressourcen-Array laden
    $meta_key = '_dnd_limited_uses';
    $resources_json = get_post_meta( $character_id, $meta_key, true );
    $resources_data = null;

    if (!empty($resources_json)) {
        $resources_data = json_decode($resources_json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($resources_data)) {
             wp_send_json_error( array( 'message' => __( 'Fehler: Ressourcendaten sind korrupt.', 'dnd-helper' ) ), 500 );
        }
    } else {
        wp_send_json_error( array( 'message' => __( 'Fehler: Keine Ressourcendaten gefunden.', 'dnd-helper' ) ), 500 );
    }

    // 6. Ressource finden und Wert validieren/aktualisieren
    if (!isset($resources_data[$resource_index])) {
         wp_send_json_error( array( 'message' => sprintf(__('Fehler: Ressource mit Index %d nicht gefunden.', 'dnd-helper'), $resource_index) ), 404 );
    }

    $target_resource = $resources_data[$resource_index];
    $max_uses = isset($target_resource['usesMax']) ? intval($target_resource['usesMax']) : 0;

    // Validierung
    if ($new_current_value < 0 || $new_current_value > $max_uses) {
         wp_send_json_error( array( 'message' => sprintf(__('Ungültiger Wert (muss zwischen 0 und %d sein).', 'dnd-helper'), $max_uses) ), 400 );
    }

    // Wert im Array aktualisieren
    $resources_data[$resource_index]['usesCurrent'] = $new_current_value;

    // 7. Aktualisiertes Array zurück als JSON speichern
    $updated_resources_json = wp_json_encode( $resources_data );
    if ( ! $updated_resources_json ) {
         wp_send_json_error( array( 'message' => __( 'Fehler: Konnte Ressourcendaten nicht neu kodieren.', 'dnd-helper' ) ), 500 );
    }

    if ( update_post_meta( $character_id, $meta_key, $updated_resources_json ) ) {
        wp_send_json_success( array(
            'message' => __('Ressource aktualisiert.', 'dnd-helper'),
            'index' => $resource_index,
            'new_value' => $new_current_value // Der gespeicherte Wert
        ) );
    } else {
        // Prüfen ob Wert identisch war
        $current_json_check = get_post_meta( $character_id, $meta_key, true );
        if ($current_json_check === $updated_resources_json) {
              wp_send_json_success( array(
                'message' => __('Wert war bereits aktuell.', 'dnd-helper'),
                'index' => $resource_index,
                'new_value' => $new_current_value
             ) );
        } else {
             error_log("DND AJAX Update Error: update_post_meta failed for Limited Uses, Post $character_id");
             wp_send_json_error( array( 'message' => __( 'Fehler: Ressourcendaten konnten nicht gespeichert werden.', 'dnd-helper' ) ), 500 );
        }
    }
}
add_action( 'wp_ajax_dnd_update_limited_use', 'dnd_ajax_update_limited_use' );

?>