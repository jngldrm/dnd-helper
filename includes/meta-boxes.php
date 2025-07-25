<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Fügt die Meta Box für Charakterdaten hinzu.
 */
function dnd_add_character_meta_box() {
    add_meta_box(
        'dnd_character_data_metabox',             // Eindeutige ID der Meta Box
        __( 'Charakterdaten & Import', 'dnd-helper' ), // Titel der Meta Box
        'dnd_render_character_meta_box_content', // Callback-Funktion zum Rendern des Inhalts
        'dnd_character',                          // Post Type, bei dem die Box angezeigt wird
        'normal',                                 // Kontext (normal, side, advanced)
        'high'                                    // Priorität (high, core, default, low)
    );
}
add_action( 'add_meta_boxes_dnd_character', 'dnd_add_character_meta_box' ); // Hook spezifisch für unseren CPT

/**
 * Rendert den Inhalt der Charakterdaten Meta Box (mit Editierfeldern).
 *
 * @param WP_Post $post Das aktuelle Post-Objekt.
 */
function dnd_render_character_meta_box_content( $post ) {
    // Sicherheits-Nonce (bleibt wichtig)
    wp_nonce_field( 'dnd_save_character_data', 'dnd_character_data_nonce' );

    // Hole die aktuell gespeicherten JSON-Daten
    $character_json = get_post_meta( $post->ID, '_dnd_character_json_data', true );
    $character_data = []; // Default leeres Array
    if ($character_json) {
        $decoded_data = json_decode($character_json, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $character_data = $decoded_data; // Nur gültige Daten verwenden
        } else {
            // Optional: Hinweis auf ungültiges JSON anzeigen
             echo '<div class="notice notice-error"><p>' . __('Warnung: Gespeicherte Charakterdaten sind kein gültiges JSON.', 'dnd-helper') . '</p></div>';
        }
    }

    // Helper Funktion (wird aktuell nicht mehr gebraucht, da Felder auskommentiert sind)
    /*
    $get_val = function($path, $default = '') use ($character_data) {
        $keys = explode('.', $path);
        $value = $character_data;
        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return $default;
            }
            $value = $value[$key];
        }
        return $value;
    };
    */

    ?>
    <style>
        /* Styling kann bleiben oder angepasst werden */
        .dnd-meta-box-section { margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .dnd-meta-box-section:last-child { border-bottom: none; }
        .dnd-meta-box-section h4 { margin-top: 0; margin-bottom: 15px; font-size: 1.1em; border-bottom: 1px dotted #ccc; padding-bottom: 5px;}
        .dnd-field-group { margin-bottom: 12px; }
        .dnd-field-group label { display: block; margin-bottom: 3px; font-weight: bold; }
        .dnd-field-group input[type="text"],
        .dnd-field-group input[type="number"],
        .dnd-field-group select,
        .dnd-field-group textarea { width: 100%; max-width: 400px; padding: 5px; }
         .dnd-field-group textarea { max-width: 98%; min-height: 80px; }
        .dnd-field-group input[type="number"] { max-width: 100px; }
        .dnd-columns { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; }
    </style>

    <div class="dnd-meta-box-container">

                <!-- Bereich für JSON Import/Export -->
        <div class="dnd-meta-box-section">
            <h4><?php _e( 'JSON Import/Export', 'dnd-helper' ); ?></h4>
            <p><em><?php _e('Priorität: Wenn eine Datei hochgeladen WIRD, wird diese verwendet, unabhängig vom Textfeld unten.', 'dnd-helper'); ?></em></p>

            <div class="dnd-field-group">
                <label for="dnd_character_json_upload"><?php _e( 'Option 1: Charakter-JSON importieren (Datei-Upload)', 'dnd-helper' ); ?></label>
                <input type="file" id="dnd_character_json_upload" name="dnd_character_json_upload" accept=".json">
            </div>

            <hr style="margin: 20px 0;">

            <div class="dnd-field-group">
                 <label for="dnd_character_json_paste"><?php _e( 'Option 2: Charakter-JSON importieren (Copy & Paste)', 'dnd-helper' ); ?></label>
                 <textarea id="dnd_character_json_paste" name="dnd_character_json_paste" rows="10" placeholder="<?php esc_attr_e('JSON-Code hier einfügen...', 'dnd-helper'); ?>" style="width: 98%; font-family: monospace;"></textarea>
                 <p class="description"><?php _e('Füge den gesamten Inhalt einer Charakter-JSON-Datei hier ein.', 'dnd-helper'); ?></p>
            </div>

            <hr style="margin: 20px 0;">

             <div class="dnd-field-group">
                 <label for="dnd_json_export"><?php _e( 'Aktuell gespeicherte JSON Daten (zum Kopieren/Export):', 'dnd-helper' ); ?></label>
                 <textarea id="dnd_json_export" rows="8" readonly style="width: 98%; font-family: monospace; white-space: pre; overflow-x: auto; background-color:#f0f0f0;"><?php echo esc_textarea( wp_json_encode($character_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ); ?></textarea>
            </div>
        </div>

        <!-- Bereich für manuelle Bearbeitung (AUSKOMMENTIERT) -->
        <?php /* <--- START AUSKOMMENTIERUNG RENDERING ---
        <?php if (!empty($character_data)): // Nur anzeigen, wenn Daten vorhanden sind ?>
        <div class="dnd-meta-box-section">
            <h4><?php _e( 'Manuelle Bearbeitung', 'dnd-helper' ); ?></h4>
            <p><?php _e('Änderungen hier werden gespeichert, wenn KEINE neue JSON-Datei hochgeladen wird.', 'dnd-helper'); ?></p>

            <!-- Basis-Infos -->
            <h5><?php _e('Basis-Informationen', 'dnd-helper'); ?></h5>
            <div class="dnd-columns">
                <div class="dnd-field-group">
                    <label for="dnd_data_basicInfo_characterName"><?php _e('Charaktername', 'dnd-helper'); ?></label>
                    <input type="text" id="dnd_data_basicInfo_characterName" name="dnd_data[basicInfo][characterName]" value="<?php echo esc_attr($get_val('basicInfo.characterName')); ?>">
                </div>
                 <div class="dnd-field-group">
                    <label for="dnd_data_basicInfo_playerName"><?php _e('Spielername', 'dnd-helper'); ?></label>
                    <input type="text" id="dnd_data_basicInfo_playerName" name="dnd_data[basicInfo][playerName]" value="<?php echo esc_attr($get_val('basicInfo.playerName')); ?>">
                </div>
                 <div class="dnd-field-group">
                    <label for="dnd_data_basicInfo_race"><?php _e('Rasse', 'dnd-helper'); ?></label>
                    <input type="text" id="dnd_data_basicInfo_race" name="dnd_data[basicInfo][race]" value="<?php echo esc_attr($get_val('basicInfo.race')); ?>">
                </div>
                 <div class="dnd-field-group">
                    <label for="dnd_data_basicInfo_background"><?php _e('Hintergrund', 'dnd-helper'); ?></label>
                    <input type="text" id="dnd_data_basicInfo_background" name="dnd_data[basicInfo][background]" value="<?php echo esc_attr($get_val('basicInfo.background')); ?>">
                </div>
                <div class="dnd-field-group">
                    <label for="dnd_data_basicInfo_alignment"><?php _e('Gesinnung', 'dnd-helper'); ?></label>
                    <input type="text" id="dnd_data_basicInfo_alignment" name="dnd_data[basicInfo][alignment]" value="<?php echo esc_attr($get_val('basicInfo.alignment')); ?>">
                </div>
                <div class="dnd-field-group">
                    <label for="dnd_data_basicInfo_experiencePoints"><?php _e('Erfahrungspunkte', 'dnd-helper'); ?></label>
                    <input type="number" id="dnd_data_basicInfo_experiencePoints" name="dnd_data[basicInfo][experiencePoints]" value="<?php echo esc_attr($get_val('basicInfo.experiencePoints', 0)); ?>" step="1">
                </div>
            </div>


            <!-- Attribute -->
            <h5><?php _e('Attributswerte', 'dnd-helper'); ?></h5>
            <div class="dnd-columns">
                <?php foreach ($get_val('abilityScores', []) as $key => $scoreData): ?>
                <div class="dnd-field-group">
                    <label for="dnd_data_abilityScores_<?php echo esc_attr($key); ?>_score"><?php echo esc_html(ucfirst($key)); ?> Score</label>
                    <input type="number" id="dnd_data_abilityScores_<?php echo esc_attr($key); ?>_score" name="dnd_data[abilityScores][<?php echo esc_attr($key); ?>][score]" value="<?php echo esc_attr($get_val("abilityScores.$key.score", 10)); ?>" min="1" max="30">

                </div>
                <?php endforeach; ?>
            </div>

            <!-- Kampfinfos -->
            <h5><?php _e('Kampfwerte', 'dnd-helper'); ?></h5>
             <div class="dnd-columns">
                <div class="dnd-field-group">
                    <label for="dnd_data_combat_hitPoints_current"><?php _e('Aktuelle TP', 'dnd-helper'); ?></label>
                    <input type="number" id="dnd_data_combat_hitPoints_current" name="dnd_data[combat][hitPoints][current]" value="<?php echo esc_attr($get_val('combat.hitPoints.current', 0)); ?>">
                </div>
                 <div class="dnd-field-group">
                    <label for="dnd_data_combat_hitPoints_max"><?php _e('Maximale TP', 'dnd-helper'); ?></label>
                    <input type="number" id="dnd_data_combat_hitPoints_max" name="dnd_data[combat][hitPoints][max]" value="<?php echo esc_attr($get_val('combat.hitPoints.max', 0)); ?>">
                </div>
                 <div class="dnd-field-group">
                    <label for="dnd_data_combat_hitPoints_temporary"><?php _e('Temporäre TP', 'dnd-helper'); ?></label>
                    <input type="number" id="dnd_data_combat_hitPoints_temporary" name="dnd_data[combat][hitPoints][temporary]" value="<?php echo esc_attr($get_val('combat.hitPoints.temporary', 0)); ?>">
                </div>
                 <div class="dnd-field-group">
                    <label for="dnd_data_combat_hitDice_current"><?php _e('Verfügbare Trefferwürfel', 'dnd-helper'); ?></label>
                    <input type="number" id="dnd_data_combat_hitDice_current" name="dnd_data[combat][hitDice][current]" value="<?php echo esc_attr($get_val('combat.hitDice.current', 0)); ?>">
                </div>
                 <div class="dnd-field-group">
                    <label for="dnd_data_combat_deathSaves_successes"><?php _e('Todesschutzwürfe Erfolge', 'dnd-helper'); ?></label>
                    <input type="number" id="dnd_data_combat_deathSaves_successes" name="dnd_data[combat][deathSaves][successes]" value="<?php echo esc_attr($get_val('combat.deathSaves.successes', 0)); ?>" min="0" max="3">
                </div>
                 <div class="dnd-field-group">
                    <label for="dnd_data_combat_deathSaves_failures"><?php _e('Todesschutzwürfe Fehlschläge', 'dnd-helper'); ?></label>
                    <input type="number" id="dnd_data_combat_deathSaves_failures" name="dnd_data[combat][deathSaves][failures]" value="<?php echo esc_attr($get_val('combat.deathSaves.failures', 0)); ?>" min="0" max="3">
                </div>
            </div>

            <!-- Ausrüstung: Währung -->
             <h5><?php _e('Währung', 'dnd-helper'); ?></h5>
             <div class="dnd-columns" style="grid-template-columns: repeat(5, 1fr);">
                <?php foreach (['cp', 'sp', 'ep', 'gp', 'pp'] as $coin): ?>
                 <div class="dnd-field-group">
                    <label for="dnd_data_equipment_currency_<?php echo esc_attr($coin); ?>"><?php echo esc_html(strtoupper($coin)); ?></label>
                    <input type="number" id="dnd_data_equipment_currency_<?php echo esc_attr($coin); ?>" name="dnd_data[equipment][currency][<?php echo esc_attr($coin); ?>]" value="<?php echo esc_attr($get_val("equipment.currency.$coin", 0)); ?>" min="0">
                </div>
                <?php endforeach; ?>
            </div>

             <!-- Notizen -->
             <h5><?php _e('Notizen (als Liste)', 'dnd-helper'); ?></h5>
             <div class="dnd-field-group">
                <label for="dnd_data_notes"><?php _e('Eine Notiz pro Zeile', 'dnd-helper'); ?></label>
                <textarea id="dnd_data_notes" name="dnd_data[notes]"><?php
                    echo esc_textarea(implode("\n", $get_val('notes', [])));
                ?></textarea>
             </div>


             <p><em><?php _e('Hinweis: Inventar, Zauber, Merkmale, Talente, Fertigkeiten-Boni etc. können derzeit nur über den JSON-Import aktualisiert werden.', 'dnd-helper'); ?></em></p>

        </div>
        <?php else: ?>
        <div class="dnd-meta-box-section">
             <p><?php _e('Keine gültigen Charakterdaten zum Bearbeiten vorhanden. Bitte importieren Sie zuerst eine JSON-Datei.', 'dnd-helper'); ?></p>
        </div>
        <?php endif; ?>
        */ // <--- ENDE AUSKOMMENTIERUNG RENDERING --- ?>

    </div><!-- /dnd-meta-box-container -->
    <?php
}


/**
 * Speichert die Charakterdaten (JSON Upload ODER Copy/Paste).
 * Priorisiert JSON-Upload.
 *
 * @param int $post_id Die ID des zu speichernden Posts.
 */
function dnd_save_character_meta_box_data( $post_id ) {

    // --- 1. Sicherheitschecks (wie vorher) ---
    if ( ! isset( $_POST['dnd_character_data_nonce'] ) || ! wp_verify_nonce( $_POST['dnd_character_data_nonce'], 'dnd_save_character_data' ) ) { return; }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
    if ( get_post_type($post_id) !== 'dnd_character' || ! current_user_can( 'edit_post', $post_id ) ) { return; }

    $json_processed = false; // Flag, ob wir JSON verarbeitet haben (egal ob Upload oder Paste)

    // --- 2. Prüfung auf und Verarbeitung von JSON-Datei-Upload (Priorität 1) ---
    if ( isset( $_FILES['dnd_character_json_upload'] ) && isset($_FILES['dnd_character_json_upload']['error']) && $_FILES['dnd_character_json_upload']['error'] === UPLOAD_ERR_OK ) {

        $file = $_FILES['dnd_character_json_upload'];
        $temp_file_path = $file['tmp_name'];
        $original_filename = basename( $file['name'] );

        // Dateityp-Validierung
        $file_extension = strtolower( pathinfo( $original_filename, PATHINFO_EXTENSION ) );
        $mime_type = is_readable($temp_file_path) ? mime_content_type( $temp_file_path ) : false;

        if ( $file_extension === 'json' && $mime_type === 'application/json' ) {
            $json_content = file_get_contents( $temp_file_path );
            if ($json_content !== false) {
                $decoded_data = json_decode( $json_content, true );
                if ( json_last_error() === JSON_ERROR_NONE ) {
                    // Gültiges JSON aus Datei! Verarbeite es.
                    dnd_process_and_save_json_data( $post_id, $json_content, $decoded_data, 'Upload' );
                    $json_processed = true; // Markieren, dass wir fertig sind
                } else { /* Optional: Fehler loggen/anzeigen für ungültiges JSON */ }
            } else { /* Optional: Fehler loggen/anzeigen für Lesefehler */ }
        } else { /* Optional: Fehler loggen/anzeigen für falschen Typ */ }
    }

    // --- 3. Prüfung auf und Verarbeitung von Copy/Paste JSON (Priorität 2, nur wenn kein Upload erfolgte) ---
    if ( ! $json_processed && isset( $_POST['dnd_character_json_paste'] ) && ! empty( trim( $_POST['dnd_character_json_paste'] ) ) ) {

        // Wichtig: Slashes entfernen, die WordPress evtl. hinzufügt
        $pasted_json_content = wp_unslash( trim( $_POST['dnd_character_json_paste'] ) );

        // JSON dekodieren
        $decoded_data = json_decode( $pasted_json_content, true );
        if ( json_last_error() === JSON_ERROR_NONE ) {
            // Gültiges JSON aus Textarea! Verarbeite es.
            // Wir verwenden den *ursprünglichen* (aber unslashed) String zum Speichern
             dnd_process_and_save_json_data( $post_id, $pasted_json_content, $decoded_data, 'Paste' );
             $json_processed = true; // Markieren, dass wir fertig sind
        } else {
             // Fehler: Ungültiges JSON im Textfeld
             // Optional: Admin-Nachricht hinzufügen
             // error_log("DND Save Error (Post ID: $post_id): Invalid JSON pasted in textarea. Error: " . json_last_error_msg());
             // Verhindere, dass WordPress den Post als "aktualisiert" markiert, wenn nur ungültiges JSON gepastet wurde? Schwierig.
             // Fürs Erste tun wir nichts, der alte Zustand bleibt erhalten.
        }
    }

    // --- 4. Keine Import-Aktion ---
    if ( ! $json_processed ) {
        // Keine gültige Datei hochgeladen und kein gültiges JSON eingefügt.
        // Hier passiert nichts, da manuelle Backend-Felder entfernt wurden.
        // error_log("DND Info (Post ID: $post_id): No valid JSON import detected (Upload or Paste).");
    }

    // Ende der Funktion
}

// Wichtig: Der Hook muss nach der Funktion definiert werden!
add_action( 'save_post_dnd_character', 'dnd_save_character_meta_box_data' );

/**
 * NEUE HILFSFUNKTION: Verarbeitet und speichert gültige JSON-Daten.
 * Wird von Upload und Paste aufgerufen.
 *
 * @param int $post_id Post ID.
 * @param string $json_content Der rohe, gültige JSON-String.
 * @param array $decoded_data Das dekodierte assoziative Array.
 * @param string $source 'Upload' oder 'Paste' (für Logging/Notices).
 */
function dnd_process_and_save_json_data( $post_id, $json_content, $decoded_data, $source = 'Unknown' ) {

    // 1. JSON-Blob speichern
    $blob_saved = update_post_meta( $post_id, '_dnd_character_json_data', wp_slash( $json_content ) );
    // error_log("DND Save (Post ID: $post_id, Source: $source): update_post_meta (JSON Blob) result: " . print_r($blob_saved, true));

    // 2. Dedizierte Meta-Felder aktualisieren (nur wenn Blob gespeichert wurde)
    if ($blob_saved !== false) {
        dnd_update_dedicated_meta_fields( $post_id, $decoded_data );
    } else {
         // error_log("DND Save Error (Post ID: $post_id, Source: $source): Failed to save JSON blob.");
         return; // Nicht mit Titel-Sync weitermachen, wenn Blob nicht gespeichert werden konnte
    }

    // 3. Titel synchronisieren
    if (isset($decoded_data['basicInfo']['characterName']) && !empty(trim($decoded_data['basicInfo']['characterName']))) {
         $new_title = sanitize_text_field($decoded_data['basicInfo']['characterName']);
         $current_post = get_post($post_id);
         if ($current_post && $current_post->post_title !== $new_title) {
             // error_log("DND Save (Post ID: $post_id, Source: $source): Syncing post title to '$new_title'.");
             remove_action('save_post_dnd_character', 'dnd_save_character_meta_box_data');
             wp_update_post(array('ID' => $post_id, 'post_title' => $new_title));
             add_action('save_post_dnd_character', 'dnd_save_character_meta_box_data');
         }
     }
     // Optional: Admin-Nachricht für Erfolg setzen
     // set_transient('dnd_admin_notice_import_success_' . $post_id . '_' . strtolower($source), true, 5);
}


/**
 * Hilfsfunktion: Aktualisiert dedizierte Meta-Felder aus einem Datenarray.
 * (Mit spezifischem Logging für _dnd_spell_slots)
 *
 * @param int $post_id Die Post ID.
 * @param array $data Das dekodierte Charakterdaten-Array.
 */
function dnd_update_dedicated_meta_fields( $post_id, $data ) {
    // Definiere die Felder und ihre Pfade im Datenarray
    $fields_to_update = [
        '_dnd_hp_current'           => 'combat.hitPoints.current',
        '_dnd_hp_max'               => 'combat.hitPoints.max',
        '_dnd_hp_temporary'         => 'combat.hitPoints.temporary',
        '_dnd_hitdice_current'      => 'combat.hitDice.current',
        '_dnd_deathsaves_successes' => 'combat.deathSaves.successes',
        '_dnd_deathsaves_failures'  => 'combat.deathSaves.failures',
        '_dnd_currency_cp'          => 'equipment.currency.cp',
        '_dnd_currency_sp'          => 'equipment.currency.sp',
        '_dnd_currency_ep'          => 'equipment.currency.ep',
        '_dnd_currency_gp'          => 'equipment.currency.gp',
        '_dnd_currency_pp'          => 'equipment.currency.pp',
        '_dnd_xp'                   => 'basicInfo.experiencePoints',
        '_dnd_spell_slots'          => 'spellcasting.slots',
		'_dnd_limited_uses'         => 'limitedUseResources',
    ];

    // Helferfunktion zum sicheren Zugriff
    $get_val_local = function($path, $default = null) use ($data) {
        $keys = explode('.', $path);
        $value = $data;
        foreach ($keys as $key) {
            if (!isset($value[$key])) { return $default; }
            $value = $value[$key];
        }
        return $value;
    };

    // Gehe durch die Felder
    foreach ($fields_to_update as $meta_key => $data_path) {
        $value_from_json = $get_val_local($data_path);

        // --- SPEZIFISCHES LOGGING NUR FÜR SPELL SLOTS ---
        if ($meta_key === '_dnd_spell_slots') {
            error_log("--- DEBUG START: Processing _dnd_spell_slots for Post ID $post_id ---");
            error_log("Path used: '$data_path'");
            error_log("Value extracted from JSON for path '$data_path': " . print_r($value_from_json, true));

            if ($value_from_json !== null) {
                if (is_array($value_from_json)) {
                    // Bereinigen & als JSON vorbereiten
                     $sanitized_slots = [];
                     foreach ($value_from_json as $slot_data) {
                         $sanitized_slots[] = [
                             'level' => isset($slot_data['level']) ? intval($slot_data['level']) : 0,
                             'total' => isset($slot_data['total']) ? intval($slot_data['total']) : 0,
                             'used'  => isset($slot_data['used']) ? intval($slot_data['used']) : 0,
                         ];
                     }
                     $json_to_save = wp_json_encode($sanitized_slots);
                     error_log("Value is array. Prepared JSON to save: " . $json_to_save);

                     // Speichern
                     error_log("Attempting update_post_meta with key '$meta_key'...");
                     $save_result = update_post_meta($post_id, $meta_key, $json_to_save);
                     error_log("update_post_meta result for '$meta_key': " . print_r($save_result, true));

                } else {
                    // Es wurde etwas gefunden, aber es ist kein Array!
                    error_log("ERROR: Value for path '$data_path' is NOT an array! Type: " . gettype($value_from_json) . ". Saving empty JSON array instead.");
                    update_post_meta($post_id, $meta_key, wp_json_encode([])); // Speichere leeres Array
                }
            } else {
                // Wert wurde im JSON nicht gefunden
                error_log("Value for path '$data_path' was NULL (not found in JSON). Meta key '$meta_key' will not be updated or deleted.");
                // Optional: delete_post_meta($post_id, $meta_key);
            }
             error_log("--- DEBUG END: Processing _dnd_spell_slots ---");
        }
		    // --- NEUE Logik für Limited Uses ---
            elseif ($meta_key === '_dnd_limited_uses') {
                 if (is_array($value)) {
                     // Bereinige das Array leicht
                     $sanitized_resources = [];
                     foreach ($value as $resource) {
                         // Stelle sicher, dass zumindest 'name' vorhanden ist
                         if (!empty($resource['name'])) {
                             $sanitized_resources[] = [
                                 'name' => sanitize_text_field($resource['name']),
                                 'source' => isset($resource['source']) ? sanitize_text_field($resource['source']) : '',
                                 'usesMax' => isset($resource['usesMax']) ? intval($resource['usesMax']) : 0, // Default 0 oder 1?
                                 'usesCurrent' => isset($resource['usesCurrent']) ? intval($resource['usesCurrent']) : 0,
                                 'recharge' => isset($resource['recharge']) ? sanitize_text_field($resource['recharge']) : '',
                                 'unit' => isset($resource['unit']) ? sanitize_text_field($resource['unit']) : '', // Optional: Einheit
                                 'notes' => isset($resource['notes']) ? sanitize_textarea_field($resource['notes']) : '', // Optional: Notizen
                             ];
                         }
                     }
                     $sanitized_value_to_save = wp_json_encode($sanitized_resources); // Als JSON speichern
                 } else {
                     // Ungültiger Typ, speichere leeres Array
                     $sanitized_value_to_save = wp_json_encode([]);
                 }
            }
        // --- Verarbeitung für andere Felder (ohne detaillierte Logs) ---
        elseif ($value_from_json !== null) {
             // Bereinige und speichere andere Felder wie zuvor
             if (is_numeric($value_from_json)) {
                 $sanitized_value_to_save = intval($value_from_json);
             } elseif (is_string($value_from_json)) {
                 $sanitized_value_to_save = sanitize_text_field($value_from_json);
             } elseif (is_bool($value_from_json)) {
                 $sanitized_value_to_save = $value_from_json ? '1' : '0';
             } else {
                  $sanitized_value_to_save = '';
             }
             update_post_meta($post_id, $meta_key, $sanitized_value_to_save);
        } else {
             // Wert für andere Felder nicht gefunden
        }
    } // Ende foreach
} // Ende Funktion


/**
 * Bereinigt rekursiv ein Array von übergebenen Formulardaten.
 * (Wird aktuell nicht mehr direkt von save_post gebraucht, aber sicherheitshalber drinlassen)
 *
 * @param array $array Das zu bereinigende Array.
 * @return array Das bereinigte Array.
 */
function dnd_sanitize_submitted_data( $array ) {
    $sanitized_array = [];
    foreach ( $array as $key => $value ) {
        $sanitized_key = sanitize_key( $key ); // Schlüssel bereinigen

        if ( is_array( $value ) ) {
            $sanitized_array[ $sanitized_key ] = dnd_sanitize_submitted_data( $value );
        } else {
            if (is_numeric($value) && !is_string($value)) {
                 $sanitized_array[ $sanitized_key ] = $value;
            } elseif (is_string($value)) {
                if ($sanitized_key === 'notes') {
                    $sanitized_array[ $sanitized_key ] = sanitize_textarea_field( $value );
                } else {
                    $sanitized_array[ $sanitized_key ] = sanitize_text_field( $value );
                }
                 if (strpos($key, 'Points') !== false || strpos($key, 'score') !== false || strpos($key, 'current') !== false || strpos($key, 'max') !== false || strpos($key, 'temporary') !== false || strpos($key, 'successes') !== false || strpos($key, 'failures') !== false || $key === 'cp' || $key === 'sp' || $key === 'ep' || $key === 'gp' || $key === 'pp') {
                      if (is_numeric($sanitized_array[ $sanitized_key ])) {
                          $sanitized_array[ $sanitized_key ] = intval($sanitized_array[ $sanitized_key ]);
                      }
                 }
            } else {
                 $sanitized_array[ $sanitized_key ] = null;
            }
        }
    }
    return $sanitized_array;
}

// =========================================================================
// == KAMPAGNEN META BOX
// =========================================================================

/**
 * Fügt die Meta Box für Kampagnendaten hinzu.
 */
function dnd_add_campaign_meta_box() {
    add_meta_box(
        'dnd_campaign_data_metabox',                // Eindeutige ID der Meta Box
        __( 'Kampagnendaten & Import', 'dnd-helper' ), // Titel der Meta Box
        'dnd_render_campaign_meta_box_content',     // Callback-Funktion zum Rendern des Inhalts
        'dnd_campaign',                             // Post Type, bei dem die Box angezeigt wird (unser neuer CPT)
        'normal',                                   // Kontext
        'high'                                      // Priorität
    );
}
// Hook spezifisch für unseren Kampagnen-CPT
add_action( 'add_meta_boxes_dnd_campaign', 'dnd_add_campaign_meta_box' );

/**
 * Fügt zusätzliche Meta Boxes für Kampagnen hinzu.
 */
function dnd_add_campaign_session_meta_boxes() {
    // Meta Box für Kampagnen-Update
    add_meta_box(
        'dnd_campaign_session_merge',
        __( 'Kampagne mit Session-Daten aktualisieren', 'dnd-helper' ),
        'dnd_render_campaign_session_merge_meta_box',
        'dnd_campaign',
        'normal',
        'high'
    );
    
    // Meta Box für Versionshistorie
    add_meta_box(
        'dnd_campaign_history',
        __( 'Versionshistorie', 'dnd-helper' ),
        'dnd_render_campaign_history_meta_box',
        'dnd_campaign',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes_dnd_campaign', 'dnd_add_campaign_session_meta_boxes' );

/**
 * Rendert den Inhalt der Kampagnendaten Meta Box.
 *
 * @param WP_Post $post Das aktuelle Post-Objekt.
 */
function dnd_render_campaign_meta_box_content( $post ) {
    // Sicherheits-Nonce
    wp_nonce_field( 'dnd_save_campaign_data', 'dnd_campaign_data_nonce' );

    // Hole die aktuell gespeicherten JSON-Daten
    $campaign_json = get_post_meta( $post->ID, '_dnd_campaign_json_data', true );
    $campaign_data_for_export = []; // Default für Textarea-Export
    if ($campaign_json) {
        $decoded = json_decode($campaign_json, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $campaign_data_for_export = $decoded;
        }
    }
    ?>
    <div class="dnd-meta-box-container">
        <div class="dnd-meta-box-section">
            <h4><?php _e( 'Kampagnen-JSON Import/Export', 'dnd-helper' ); ?></h4>
            <p><em><?php _e('Priorität: Wenn eine Datei hochgeladen WIRD, wird diese verwendet, unabhängig vom Textfeld unten.', 'dnd-helper'); ?></em></p>

            <div class="dnd-field-group">
                <label for="dnd_campaign_json_upload"><?php _e( 'Option 1: Kampagnen-JSON importieren (Datei-Upload)', 'dnd-helper' ); ?></label>
                <input type="file" id="dnd_campaign_json_upload" name="dnd_campaign_json_upload" accept=".json">
            </div>

            <hr style="margin: 20px 0;">

            <div class="dnd-field-group">
                 <label for="dnd_campaign_json_paste"><?php _e( 'Option 2: Kampagnen-JSON importieren (Copy & Paste)', 'dnd-helper' ); ?></label>
                 <textarea id="dnd_campaign_json_paste" name="dnd_campaign_json_paste" rows="10" placeholder="<?php esc_attr_e('JSON-Code der Kampagne hier einfügen...', 'dnd-helper'); ?>" style="width: 98%; font-family: monospace;"></textarea>
                 <p class="description"><?php _e('Füge den gesamten Inhalt einer Kampagnen-JSON-Datei (wie summ.json) hier ein.', 'dnd-helper'); ?></p>
            </div>

            <hr style="margin: 20px 0;">

             <div class="dnd-field-group">
                 <label for="dnd_campaign_json_export"><?php _e( 'Aktuell gespeicherte JSON Daten (zum Kopieren/Export):', 'dnd-helper' ); ?></label>
                 <textarea id="dnd_campaign_json_export" rows="8" readonly style="width: 98%; font-family: monospace; white-space: pre; overflow-x: auto; background-color:#f0f0f0;"><?php
                    echo esc_textarea( wp_json_encode($campaign_data_for_export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) );
                 ?></textarea>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Speichert die Daten aus der Kampagnen Meta Box (JSON Upload ODER Copy/Paste).
 *
 * @param int $post_id Die ID des zu speichernden Posts.
 */
function dnd_save_campaign_meta_box_data( $post_id ) {
    // --- 1. Sicherheitschecks ---
    if ( ! isset( $_POST['dnd_campaign_data_nonce'] ) || ! wp_verify_nonce( $_POST['dnd_campaign_data_nonce'], 'dnd_save_campaign_data' ) ) { return; }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
    if ( get_post_type($post_id) !== 'dnd_campaign' || ! current_user_can( 'edit_post', $post_id ) ) { return; }

    $json_processed_for_campaign = false; // Eigene Variable, um nicht mit Charakter-Flag zu kollidieren

    // --- 2. Prüfung auf und Verarbeitung von JSON-Datei-Upload (Priorität 1) ---
    if ( isset( $_FILES['dnd_campaign_json_upload'] ) && isset($_FILES['dnd_campaign_json_upload']['error']) && $_FILES['dnd_campaign_json_upload']['error'] === UPLOAD_ERR_OK ) {
        $file = $_FILES['dnd_campaign_json_upload'];
        $temp_file_path = $file['tmp_name'];
        $original_filename = basename( $file['name'] );

        $file_extension = strtolower( pathinfo( $original_filename, PATHINFO_EXTENSION ) );
        $mime_type = is_readable($temp_file_path) ? mime_content_type( $temp_file_path ) : false;

        if ( $file_extension === 'json' && $mime_type === 'application/json' ) {
            $json_content = file_get_contents( $temp_file_path );
            if ($json_content !== false) {
                $decoded_data = json_decode( $json_content, true );
                if ( json_last_error() === JSON_ERROR_NONE ) {
                    // Gültiges JSON aus Datei! Speichere nur den Blob.
                    update_post_meta( $post_id, '_dnd_campaign_json_data', wp_slash( $json_content ) );
                    dnd_sync_campaign_title( $post_id, $decoded_data ); // Titel synchronisieren
                    $json_processed_for_campaign = true;
                } else { /* Optional: Fehler loggen/anzeigen für ungültiges JSON */ }
            } else { /* Optional: Fehler loggen/anzeigen für Lesefehler */ }
        } else { /* Optional: Fehler loggen/anzeigen für falschen Typ */ }
    }

    // --- 3. Prüfung auf und Verarbeitung von Copy/Paste JSON (Priorität 2) ---
    if ( ! $json_processed_for_campaign && isset( $_POST['dnd_campaign_json_paste'] ) && ! empty( trim( $_POST['dnd_campaign_json_paste'] ) ) ) {
        $pasted_json_content = wp_unslash( trim( $_POST['dnd_campaign_json_paste'] ) );
        $decoded_data = json_decode( $pasted_json_content, true );

        if ( json_last_error() === JSON_ERROR_NONE ) {
            // Gültiges JSON aus Textarea! Speichere nur den Blob.
            update_post_meta( $post_id, '_dnd_campaign_json_data', wp_slash( $pasted_json_content ) );
            dnd_sync_campaign_title( $post_id, $decoded_data ); // Titel synchronisieren
            $json_processed_for_campaign = true;
        } else { /* Optional: Fehler loggen/anzeigen für ungültiges JSON im Textfeld */ }
    }
    // Keine weitere Aktion, wenn nichts importiert wurde.
}
// Hook spezifisch für unseren Kampagnen-CPT
add_action( 'save_post_dnd_campaign', 'dnd_save_campaign_meta_box_data' );


/**
 * Hilfsfunktion: Synchronisiert den Post-Titel mit dem campaignTitle aus den JSON-Daten.
 *
 * @param int $post_id Post ID.
 * @param array $decoded_data Das dekodierte Kampagnen-Datenarray.
 */
function dnd_sync_campaign_title( $post_id, $decoded_data ) {
    if (isset($decoded_data['campaignTitle']) && !empty(trim($decoded_data['campaignTitle']))) {
         $new_title = sanitize_text_field($decoded_data['campaignTitle']);
         $current_post = get_post($post_id);
         if ($current_post && $current_post->post_title !== $new_title) {
             // Endlosschleife verhindern
             remove_action('save_post_dnd_campaign', 'dnd_save_campaign_meta_box_data');
             wp_update_post(array('ID' => $post_id, 'post_title' => $new_title));
             add_action('save_post_dnd_campaign', 'dnd_save_campaign_meta_box_data');
         }
     }
}



/**
 * Fügt enctype="multipart/form-data" zum Post-Bearbeitungsformular hinzu.
 */
function dnd_add_edit_form_multipart_encoding() {
    echo ' enctype="multipart/form-data"';
}
add_action( 'post_edit_form_tag', 'dnd_add_edit_form_multipart_encoding' );

// =========================================================================
// == SESSION META BOXES
// =========================================================================

/**
 * Fügt Meta Boxes für Sessions hinzu.
 */
function dnd_add_session_meta_boxes() {
    // Meta Box für Sprecher-Zuordnung
    add_meta_box(
        'dnd_session_speaker_mapping',
        __( 'Sprecher-Zuordnung', 'dnd-helper' ),
        'dnd_render_speaker_mapping_meta_box',
        'dndt_session',
        'normal',
        'high'
    );
    
    // Meta Box für KI-Analyse & Zusammenfassung
    add_meta_box(
        'dnd_session_ai_summary',
        __( 'KI-Analyse & Zusammenfassung', 'dnd-helper' ),
        'dnd_render_ai_summary_meta_box',
        'dndt_session',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes_dndt_session', 'dnd_add_session_meta_boxes' );

/**
 * Rendert die Sprecher-Zuordnung Meta Box.
 */
function dnd_render_speaker_mapping_meta_box( $post ) {
    // Nonce für Sicherheit
    wp_nonce_field( 'dnd_save_speaker_mapping', 'dnd_speaker_mapping_nonce' );
    
    // Lade strukturierte Transkriptdaten um Sprecher zu extrahieren
    $transcript_data = get_post_meta( $post->ID, '_dndt_processed_transcript', true );
    $speakers = array();
    
    if ( $transcript_data ) {
        // Decode JSON if needed
        if ( is_string( $transcript_data ) ) {
            $transcript_data = json_decode( $transcript_data, true );
        }
        
        if ( is_array( $transcript_data ) ) {
            // Unique speakers extrahieren
            foreach ( $transcript_data as $entry ) {
                if ( isset( $entry['speaker'] ) && ! in_array( $entry['speaker'], $speakers ) ) {
                    $speakers[] = $entry['speaker'];
                }
            }
        }
    }
    
    // Lade aktuelle Sprecher-Zuordnung
    $current_mapping = get_post_meta( $post->ID, '_dndt_speaker_mapping', true );
    if ( is_string( $current_mapping ) && ! empty( $current_mapping ) ) {
        $current_mapping = json_decode( $current_mapping, true );
    }
    if ( ! is_array( $current_mapping ) ) {
        $current_mapping = array();
    }
    
    // Lade alle Mitspieler
    $mitspieler_query = new WP_Query( array(
        'post_type' => 'dndt_mitspieler',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ) );
    
    ?>
    <div class="dnd-speaker-mapping-container">
        <?php if ( empty( $speakers ) ): ?>
            <p><?php _e( 'Keine Sprecher-Daten gefunden. Diese Funktion ist nur bei Sessions mit strukturierten Transkripten verfügbar.', 'dnd-helper' ); ?></p>
        <?php else: ?>
            <p><?php _e( 'Ordnen Sie jeden Sprecher aus dem Transkript einem Mitspieler zu:', 'dnd-helper' ); ?></p>
            
            <table class="form-table">
                <?php foreach ( $speakers as $speaker ): ?>
                <tr>
                    <th scope="row">
                        <label for="speaker_mapping_<?php echo esc_attr( $speaker ); ?>">
                            <?php echo esc_html( $speaker ); ?>
                        </label>
                    </th>
                    <td>
                        <select name="speaker_mapping[<?php echo esc_attr( $speaker ); ?>]" 
                                id="speaker_mapping_<?php echo esc_attr( $speaker ); ?>"
                                class="speaker-mapping-select">
                            <option value=""><?php _e( '-- Mitspieler auswählen --', 'dnd-helper' ); ?></option>
                            <?php
                            if ( $mitspieler_query->have_posts() ) {
                                while ( $mitspieler_query->have_posts() ) {
                                    $mitspieler_query->the_post();
                                    $selected = isset( $current_mapping[ $speaker ] ) && 
                                               $current_mapping[ $speaker ] == get_the_ID() ? 'selected' : '';
                                    echo '<option value="' . get_the_ID() . '" ' . $selected . '>' . 
                                         esc_html( get_the_title() ) . '</option>';
                                }
                                wp_reset_postdata();
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            
            <p class="submit">
                <button type="button" id="save-speaker-mapping" class="button button-primary">
                    <?php _e( 'Sprecher-Zuordnung speichern', 'dnd-helper' ); ?>
                </button>
                <span id="speaker-mapping-status" style="margin-left: 10px;"></span>
            </p>
        <?php endif; ?>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#save-speaker-mapping').on('click', function() {
            var button = $(this);
            var status = $('#speaker-mapping-status');
            
            button.prop('disabled', true).text('<?php _e( 'Speichere...', 'dnd-helper' ); ?>');
            status.html('');
            
            // Sammle alle Zuordnungen
            var mappings = {};
            $('.speaker-mapping-select').each(function() {
                var name = $(this).attr('name');
                var speaker = name.match(/speaker_mapping\[(.*?)\]/)[1];
                var mitspielerId = $(this).val();
                if (mitspielerId) {
                    mappings[speaker] = parseInt(mitspielerId);
                }
            });
            
            // AJAX-Aufruf
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dndt_save_speaker_mapping',
                    post_id: <?php echo $post->ID; ?>,
                    mappings: mappings,
                    nonce: $('#dnd_speaker_mapping_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        status.html('<span style="color: green;"><?php _e( 'Gespeichert!', 'dnd-helper' ); ?></span>');
                    } else {
                        status.html('<span style="color: red;">' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    status.html('<span style="color: red;"><?php _e( 'Fehler beim Speichern', 'dnd-helper' ); ?></span>');
                },
                complete: function() {
                    button.prop('disabled', false).text('<?php _e( 'Sprecher-Zuordnung speichern', 'dnd-helper' ); ?>');
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * Rendert die KI-Analyse & Zusammenfassung Meta Box.
 */
function dnd_render_ai_summary_meta_box( $post ) {
    // Nonce für Sicherheit
    wp_nonce_field( 'dnd_generate_summary', 'dnd_generate_summary_nonce' );
    
    // Lade aktuelle Zusammenfassung
    $current_summary = get_post_meta( $post->ID, '_dndt_session_summary', true );
    
    ?>
    <div class="dnd-ai-summary-container">
        <p><?php _e( 'Generieren Sie eine KI-basierte Zusammenfassung dieser Session:', 'dnd-helper' ); ?></p>
        
        <p class="submit">
            <button type="button" id="generate-session-summary" class="button button-primary">
                <?php _e( 'Zusammenfassung generieren', 'dnd-helper' ); ?>
            </button>
            <span id="summary-loading" style="display: none; margin-left: 10px;">
                <?php _e( 'Generiere Zusammenfassung...', 'dnd-helper' ); ?>
                <span class="spinner" style="visibility: visible; float: none;"></span>
            </span>
        </p>
        
        <div class="summary-output">
            <label for="session-summary-textarea"><?php _e( 'Generierte Zusammenfassung:', 'dnd-helper' ); ?></label>
            <textarea id="session-summary-textarea" rows="15" style="width: 100%;" readonly><?php echo esc_textarea( $current_summary ); ?></textarea>
        </div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#generate-session-summary').on('click', function() {
            var button = $(this);
            var loading = $('#summary-loading');
            var textarea = $('#session-summary-textarea');
            
            button.prop('disabled', true);
            loading.show();
            
            // AJAX-Aufruf
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dndt_summarize_session',
                    post_id: <?php echo $post->ID; ?>,
                    nonce: $('#dnd_generate_summary_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        textarea.val(response.data.summary);
                    } else {
                        alert('Fehler: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('<?php _e( 'Fehler bei der Zusammenfassung', 'dnd-helper' ); ?>');
                },
                complete: function() {
                    button.prop('disabled', false);
                    loading.hide();
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * Rendert die Campaign-Session-Merge Meta Box.
 */
function dnd_render_campaign_session_merge_meta_box( $post ) {
    // Nonce für Sicherheit
    wp_nonce_field( 'dnd_merge_campaign', 'dnd_merge_campaign_nonce' );
    
    // Lade Sessions mit Zusammenfassungen
    $sessions_query = new WP_Query( array(
        'post_type' => 'dndt_session',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_dndt_session_summary',
                'compare' => 'EXISTS'
            )
        ),
        'orderby' => 'date',
        'order' => 'DESC'
    ) );
    
    ?>
    <div class="dnd-campaign-merge-container">
        <?php if ( ! $sessions_query->have_posts() ): ?>
            <p><?php _e( 'Keine Sessions mit Zusammenfassungen gefunden.', 'dnd-helper' ); ?></p>
        <?php else: ?>
            <p><?php _e( 'Wählen Sie eine Session aus, um die Kampagnendaten zu aktualisieren:', 'dnd-helper' ); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="session-select"><?php _e( 'Session auswählen:', 'dnd-helper' ); ?></label>
                    </th>
                    <td>
                        <select id="session-select" style="width: 100%; max-width: 400px;">
                            <option value=""><?php _e( '-- Session auswählen --', 'dnd-helper' ); ?></option>
                            <?php
                            while ( $sessions_query->have_posts() ) {
                                $sessions_query->the_post();
                                echo '<option value="' . get_the_ID() . '">' . 
                                     esc_html( get_the_title() ) . ' (' . get_the_date( 'd.m.Y' ) . ')</option>';
                            }
                            wp_reset_postdata();
                            ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="button" id="merge-campaign-button" class="button button-primary">
                    <?php _e( 'Kampagnen-Daten aktualisieren', 'dnd-helper' ); ?>
                </button>
                <span id="merge-loading" style="display: none; margin-left: 10px;">
                    <?php _e( 'Aktualisiere Kampagne...', 'dnd-helper' ); ?>
                    <span class="spinner" style="visibility: visible; float: none;"></span>
                </span>
            </p>
            
            <div id="merge-status" style="margin-top: 10px;"></div>
        <?php endif; ?>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#merge-campaign-button').on('click', function() {
            var button = $(this);
            var loading = $('#merge-loading');
            var status = $('#merge-status');
            var sessionId = $('#session-select').val();
            
            if (!sessionId) {
                alert('<?php _e( 'Bitte wählen Sie eine Session aus.', 'dnd-helper' ); ?>');
                return;
            }
            
            button.prop('disabled', true);
            loading.show();
            status.html('');
            
            // AJAX-Aufruf
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dndt_merge_campaign_with_session',
                    campaign_id: <?php echo $post->ID; ?>,
                    session_id: sessionId,
                    nonce: $('#dnd_merge_campaign_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        status.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        // Seite nach 2 Sekunden neu laden, um aktualisierte Daten zu zeigen
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        status.html('<div class="notice notice-error"><p>Fehler: ' + response.data.message + '</p></div>');
                    }
                },
                error: function() {
                    status.html('<div class="notice notice-error"><p><?php _e( 'Fehler beim Aktualisieren der Kampagne', 'dnd-helper' ); ?></p></div>');
                },
                complete: function() {
                    button.prop('disabled', false);
                    loading.hide();
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * Rendert die Campaign History Meta Box.
 */
function dnd_render_campaign_history_meta_box( $post ) {
    // Lade Versionshistorie
    $history = get_post_meta( $post->ID, '_dnd_campaign_json_history', true );
    
    if ( ! is_array( $history ) || empty( $history ) ) {
        echo '<p>' . __( 'Keine Versionshistorie verfügbar.', 'dnd-helper' ) . '</p>';
        return;
    }
    
    ?>
    <div class="dnd-campaign-history-container">
        <p><?php _e( 'Frühere Versionen der Kampagnendaten:', 'dnd-helper' ); ?></p>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e( 'Zeitstempel', 'dnd-helper' ); ?></th>
                    <th><?php _e( 'Aktionen', 'dnd-helper' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $history as $index => $version ): ?>
                <tr>
                    <td>
                        <?php 
                        $datetime = new DateTime( $version['timestamp'] );
                        echo esc_html( $datetime->format( 'd.m.Y H:i:s' ) );
                        ?>
                    </td>
                    <td>
                        <button type="button" class="button view-version" data-index="<?php echo $index; ?>">
                            <?php _e( 'Ansehen', 'dnd-helper' ); ?>
                        </button>
                        <button type="button" class="button restore-version" data-index="<?php echo $index; ?>">
                            <?php _e( 'Wiederherstellen', 'dnd-helper' ); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Modal für JSON-Anzeige -->
        <div id="version-modal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border: 1px solid #ccc; padding: 20px; z-index: 10000; width: 80%; max-width: 800px; max-height: 80%; overflow: auto;">
            <h3><?php _e( 'JSON-Daten der Version', 'dnd-helper' ); ?></h3>
            <textarea id="version-json" style="width: 100%; height: 400px; font-family: monospace;" readonly></textarea>
            <p>
                <button type="button" id="close-modal" class="button"><?php _e( 'Schließen', 'dnd-helper' ); ?></button>
            </p>
        </div>
        <div id="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;"></div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var historyData = <?php echo wp_json_encode( $history ); ?>;
        
        // Version ansehen
        $('.view-version').on('click', function() {
            var index = $(this).data('index');
            var versionData = historyData[index];
            
            if (versionData && versionData.data) {
                // JSON formatiert anzeigen
                var formattedJson = JSON.stringify(JSON.parse(versionData.data), null, 2);
                $('#version-json').val(formattedJson);
                $('#modal-overlay, #version-modal').show();
            }
        });
        
        // Modal schließen
        $('#close-modal, #modal-overlay').on('click', function() {
            $('#modal-overlay, #version-modal').hide();
        });
        
        // Version wiederherstellen
        $('.restore-version').on('click', function() {
            var index = $(this).data('index');
            var versionData = historyData[index];
            
            if (!confirm('<?php _e( 'Sind Sie sicher, dass Sie diese Version wiederherstellen möchten? Die aktuellen Daten werden automatisch als Backup gespeichert.', 'dnd-helper' ); ?>')) {
                return;
            }
            
            if (versionData && versionData.data) {
                var button = $(this);
                var originalText = button.text();
                button.prop('disabled', true).text('<?php _e( 'Wiederherstellung läuft...', 'dnd-helper' ); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'dndt_restore_campaign_version',
                        campaign_id: <?php echo $post->ID; ?>,
                        version_index: index,
                        nonce: '<?php echo wp_create_nonce( 'dnd_restore_campaign_version' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e( 'Version erfolgreich wiederhergestellt! Die Seite wird neu geladen.', 'dnd-helper' ); ?>');
                            location.reload();
                        } else {
                            alert('<?php _e( 'Fehler:', 'dnd-helper' ); ?> ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('<?php _e( 'Fehler bei der Wiederherstellung', 'dnd-helper' ); ?>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text(originalText);
                    }
                });
            }
        });
    });
    </script>
    <?php
}

?>