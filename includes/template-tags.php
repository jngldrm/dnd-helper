<?php
// In includes/template-tags.php (oder shortcodes.php)

/**
 * Generiert das HTML für ein Charakterblatt anhand der Post-ID.
 *
 * @param int $character_post_id Die ID des Charakter-Posts.
 * @return string Das generierte HTML oder eine Fehlermeldung.
 */
function dnd_get_rendered_character_sheet_html( $character_post_id ) {
    // 1. ID validieren (redundant, aber sicher)
    if ( $character_post_id <= 0 ) { return '<p class="dnd-error">Ungültige ID.</p>'; }

    // 2. Post-Typ prüfen (redundant, aber sicher)
     $post = get_post( $character_post_id );
     if ( ! $post || $post->post_type !== 'dnd_character' ) {
         return '<p class="dnd-error">Charakter nicht gefunden.</p>';
     }

    // 3. JSON-Daten abrufen & dekodieren
    $character_json = get_post_meta( $character_post_id, '_dnd_character_json_data', true );
    if ( ! $character_json ) { return '<p class="dnd-error">Keine Charakterdaten (JSON) gefunden.</p>'; }
    $data = json_decode( $character_json, true );
    if ( json_last_error() !== JSON_ERROR_NONE ) { return '<p class="dnd-error">Charakterdaten (JSON) korrupt.</p>'; }

    // 4. Helferfunktion für JSON-Fallback-Werte definieren
    $get_val = function($path, $default = '') use ($data) {
        $keys = explode('.', $path);
        $value = $data;
        foreach ($keys as $key) {
            if (!isset($value[$key])) { return $default; }
            $value = $value[$key];
        }
        return $value;
    };

     // 5. Separate Meta-Felder für aktuelle Werte holen (Hybrid-Ansatz)
    $hp_current_meta = get_post_meta($character_post_id, '_dnd_hp_current', true);
    $hp_max_meta = get_post_meta($character_post_id, '_dnd_hp_max', true);
    $hp_temp_meta = get_post_meta($character_post_id, '_dnd_hp_temporary', true);
    // ... weitere Meta-Felder hier laden ...

    // Werte bestimmen (Meta bevorzugen, dann JSON Fallback)
    $hp_current = is_numeric($hp_current_meta) ? $hp_current_meta : $get_val('combat.hitPoints.current', '?');
    $hp_max = is_numeric($hp_max_meta) ? $hp_max_meta : $get_val('combat.hitPoints.max', '?');
    $hp_temp = is_numeric($hp_temp_meta) ? $hp_temp_meta : $get_val('combat.hitPoints.temporary', '0');
     // ... weitere Werte bestimmen ...

    // Hole das Beitragsbild
    $character_image_html_large = ''; // Für die erweiterte Ansicht
    $character_image_html_small = ''; // Für die Vorschau
    if ( has_post_thumbnail( $character_post_id ) ) {
        $character_image_html_large = get_the_post_thumbnail( $character_post_id, 'medium', array('class' => 'char-header-image-expanded golden-frame') );
        $character_image_html_small = get_the_post_thumbnail( $character_post_id, 'thumbnail', array('class' => 'char-header-image-preview') );
    }

    // 6. HTML generieren (mithilfe von Template-Teilen)
    ob_start();
    ?>
    <div class="dnd-character-sheet enhanced" data-character-id="<?php echo esc_attr( $character_post_id ); ?>">
        <!-- Header -->
        <header class="sheet-header expanding-header <?php echo $character_image_html_large ? 'has-image' : 'no-image'; ?>">
            <?php if ( $character_image_html_large ): ?>
                <div class="char-image-trigger">
                    <?php echo $character_image_html_large; ?>
                </div>
            <?php endif; ?>
                
            <div class="char-info-container">
                <div class="char-info-main">
                    <h1><?php echo esc_html( $get_val('basicInfo.characterName', 'Unbenannter Charakter') ); ?></h1>
                    <!--
                    <div class="char-meta-brief">
                        <span><?php echo dnd_helper_generate_wikidot_link( $get_val('basicInfo.raceSlug'), $get_val('basicInfo.race'), 'View Race' ); ?></span> |
                        <span>
                            <?php // Nur die Klasse(n) und Level als kurze Info anzeigen
                            if ( ! empty( $data['basicInfo']['classes'] ) && is_array($data['basicInfo']['classes']) ) {
                                $class_briefs = [];
                                foreach ( $data['basicInfo']['classes'] as $class ) {
                                    $class_name = $class['className'] ?? '';
                                    $level = $class['level'] ?? '';
                                    if (!empty($class_name)) {
                                        $class_briefs[] = esc_html($class_name) . ($level ? ' ' . esc_html($level) : '');
                                    }
                                }
                                echo implode( ' / ', $class_briefs );
                            } ?>
                        </span>
                        <?php // Optional: Pfeil-Icon, das die Erweiterbarkeit anzeigt ?>
                        <span class="expand-indicator">▾</span>
                    </div>
                        -->
                </div>

                <div class="char-info-details">
                    <!--
                    <?php if ( $character_image_html_large ): ?>
                        <div class="char-image-large-wrapper">
                            <?php echo $character_image_html_large; ?>
                        </div>
                    <?php endif; ?>
                    -->
                    <div class="char-details-full-text">
                        <p>
                            <?php echo dnd_helper_generate_wikidot_link( $get_val('basicInfo.raceSlug'), $get_val('basicInfo.race'), 'View Race' ); ?>
                            <?php
                            if ( ! empty( $data['basicInfo']['classes'] ) && is_array($data['basicInfo']['classes']) ) {
                                $class_links = [];
                                foreach ( $data['basicInfo']['classes'] as $class ) {
                                    $class_name = $class['className'] ?? ''; $class_slug = $class['classSlug'] ?? null;
                                    $subclass_name = $class['subClass'] ?? ''; $subclass_slug = $class['subClassSlug'] ?? null;
                                    $level = $class['level'] ?? '';
                                    if (!empty($class_name)) {
                                        $class_part = dnd_helper_generate_wikidot_link($class_slug, $class_name, 'View Class');
                                        $subclass_part = !empty($subclass_name) ? ' (' . dnd_helper_generate_wikidot_link($subclass_slug, $subclass_name, 'View Subclass') . ')' : '';
                                        $class_links[] = $class_part . $subclass_part . ' ' . esc_html($level);
                                    }
                                }
                                echo ' | ' . implode( ' / ', $class_links );
                            } ?>
                        </p>
                        <p>
                            <?php echo dnd_helper_generate_wikidot_link( $get_val('basicInfo.backgroundSlug'), $get_val('basicInfo.background'), 'View Background' ); ?> |
                            <?php echo esc_html( $get_val('basicInfo.alignment') ); ?>
                        </p>
                        <p><?php _e( 'Spieler:', 'dnd-helper' ); ?> <?php echo esc_html( $get_val('basicInfo.playerName') ); ?></p>
                        <p><?php _e( 'XP:', 'dnd-helper' ); ?> <?php echo esc_html( $get_val('basicInfo.experiencePoints', '0') ); ?></p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Tab Navigation -->
        <ul class="dnd-tabs-nav">
             <li><a class="dnd-tab-link active" href="#tab-overview-<?php echo esc_attr($character_post_id); ?>" data-tab="overview-<?php echo esc_attr($character_post_id); ?>"><?php _e('Übersicht', 'dnd-helper'); ?></a></li>
            <li><a class="dnd-tab-link" href="#tab-combat-<?php echo esc_attr($character_post_id); ?>" data-tab="combat-<?php echo esc_attr($character_post_id); ?>"><?php _e('Kampf', 'dnd-helper'); ?></a></li>
            <li><a class="dnd-tab-link" href="#tab-actions-<?php echo esc_attr($character_post_id); ?>" data-tab="actions-<?php echo esc_attr($character_post_id); ?>"><?php _e('Aktionen', 'dnd-helper'); ?></a></li>
            <li><a class="dnd-tab-link" href="#tab-equipment-<?php echo esc_attr($character_post_id); ?>" data-tab="equipment-<?php echo esc_attr($character_post_id); ?>"><?php _e('Ausrüstung', 'dnd-helper'); ?></a></li>
            <li><a class="dnd-tab-link" href="#tab-features-<?php echo esc_attr($character_post_id); ?>" data-tab="features-<?php echo esc_attr($character_post_id); ?>"><?php _e('Merkmale', 'dnd-helper'); ?></a></li>
            <li><a class="dnd-tab-link" href="#tab-background-<?php echo esc_attr($character_post_id); ?>" data-tab="background-<?php echo esc_attr($character_post_id); ?>"><?php _e('Hintergrund', 'dnd-helper'); ?></a></li>
        </ul>

        <!-- Tab Content -->
        <div class="dnd-tabs-content">
             <!-- Wichtig: IDs der Panes müssen eindeutig sein, wenn mehrere Sheets auf einer Seite sein könnten (obwohl hier nicht der Fall) -->
             <!-- Wir hängen die Post-ID an, um sicherzugehen -->
            <div id="tab-overview-<?php echo esc_attr($character_post_id); ?>" class="dnd-tab-pane active">
                 <!-- Inhalt Übersicht (wie vorher, nutzt $get_val) -->
                 <?php include( plugin_dir_path(__FILE__) . '../templates/sheet-parts/tab-overview.php' ); ?>
            </div>
            <div id="tab-combat-<?php echo esc_attr($character_post_id); ?>" class="dnd-tab-pane">
                 <!-- Inhalt Kampf (wie vorher, nutzt $get_val und separate Meta) -->
                  <?php include( plugin_dir_path(__FILE__) . '../templates/sheet-parts/tab-combat.php' ); ?>
            </div>
             <div id="tab-actions-<?php echo esc_attr($character_post_id); ?>" class="dnd-tab-pane">
                  <?php include( plugin_dir_path(__FILE__) . '../templates/sheet-parts/tab-actions.php' ); ?>
             </div>
             <div id="tab-equipment-<?php echo esc_attr($character_post_id); ?>" class="dnd-tab-pane">
                  <?php include( plugin_dir_path(__FILE__) . '../templates/sheet-parts/tab-equipment.php' ); ?>
             </div>
             <div id="tab-features-<?php echo esc_attr($character_post_id); ?>" class="dnd-tab-pane">
                  <?php include( plugin_dir_path(__FILE__) . '../templates/sheet-parts/tab-features.php' ); ?>
             </div>
              <div id="tab-background-<?php echo esc_attr($character_post_id); ?>" class="dnd-tab-pane">
                  <?php include( plugin_dir_path(__FILE__) . '../templates/sheet-parts/tab-background.php' ); ?>
             </div>
        </div> <!-- /dnd-tabs-content -->
    </div> <!-- /dnd-character-sheet -->
    <?php
    return ob_get_clean(); // Gibt das generierte HTML zurück
}

// Die Funktion dnd_helper_generate_wikidot_link() muss auch hier verfügbar sein.
/**
 * Generiert einen Wikidot-Link, falls ein Slug vorhanden ist.
 *
 * @param string|null $slug Der Wikidot-Slug (z.B. "druid", "spell:cure-wounds").
 * @param string $text Der anzuzeigende Text.
 * @param string $title_prefix Optionaler Prefix für den Link-Titel (z.B. "View Class").
 * @return string Entweder der HTML-Link oder der einfache Text.
 */
function dnd_helper_generate_wikidot_link( $slug, $text, $title_prefix = 'View' ) {
    if ( empty( $slug ) || empty( $text ) ) {
        return esc_html( $text ); // Nur Text zurückgeben, wenn kein Slug oder Text vorhanden
    }

    $base_url = 'https://dnd5e.wikidot.com/';
    // Einfache Bereinigung: Trimmen (Doppelpunkte etc. im Slug sind oft nötig)
    $formatted_slug = trim( (string) $slug );

    // Erstelle die URL
    $url = $base_url . $formatted_slug;

    // Generiere den Link
    return sprintf(
        '<a href="%s" target="_blank" rel="noopener noreferrer" title="%s %s on Wikidot">%s</a>',
        esc_url( $url ),
        esc_attr( $title_prefix ), // "View"
        esc_attr( $text ),       // "Druid"
        esc_html( $text )        // Angezeigter Text: "Druid"
    );
}
?>