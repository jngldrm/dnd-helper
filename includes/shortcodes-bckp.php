<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// -- Funktionen für [dnd_character_sheet] --

/**
 * Registriert den Shortcode für die Charakterblatt-Anzeige.
 */
function dnd_register_character_sheet_shortcode() {
    add_shortcode( 'dnd_character_sheet', 'dnd_character_sheet_shortcode_handler' );
}
add_action( 'init', 'dnd_register_character_sheet_shortcode' );

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

/**
 * Die Handler-Funktion für den [dnd_character_sheet] Shortcode.
 *
 * @param array $atts Shortcode-Attribute. Erwartet 'id' des Charakter-Posts.
 * @return string Der HTML-Output für das Charakterblatt.
 */
function dnd_character_sheet_shortcode_handler( $atts ) {
    // Standardwerte für Attribute festlegen und übergebene $atts parsen
    $atts = shortcode_atts( array(
        'id' => 0, // Standardmäßig keine ID
    ), $atts, 'dnd_character_sheet' );

    $character_post_id = intval( $atts['id'] );

    // 1. ID prüfen, Post existiert & Typ prüfen (wie vorher)
    if ( $character_post_id <= 0 ) {
        return '<p class="dnd-error">' . __( 'Fehler: Bitte geben Sie eine gültige Charakter-ID an.', 'dnd-helper' ) . '</p>';
    }
    $post = get_post( $character_post_id );
    if ( ! $post || $post->post_type !== 'dnd_character' ) {
        return '<p class="dnd-error">' . sprintf( __( 'Fehler: Charakter mit ID %d nicht gefunden.', 'dnd-helper' ), $character_post_id ) . '</p>';
    }

    // 2. JSON-Daten abrufen & dekodieren (wie vorher)
    $character_json = get_post_meta( $character_post_id, '_dnd_character_json_data', true );
    if ( ! $character_json ) {
         return '<p class="dnd-error">' . sprintf( __( 'Fehler: Keine Daten für Charakter-ID %d.', 'dnd-helper' ), $character_post_id ) . '</p>';
    }
    $data = json_decode( $character_json, true );
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        error_log( 'DND Helper Shortcode: JSON Decode Error für Post ID ' . $character_post_id . ' - Error: ' . json_last_error_msg() );
        return '<p class="dnd-error">' . sprintf( __( 'Fehler: Daten für ID %d konnten nicht gelesen werden.', 'dnd-helper' ), $character_post_id ) . '</p>';
    }

    // 3. Assets laden (CSS + NEUES JS für Tabs/Akkordeons)
    wp_enqueue_style( 'dnd-character-sheet-style' ); // Das existierende CSS
    wp_enqueue_script( 'dnd-sheet-interaction-script' ); // Unser neues JS
	
	// Helper Funktion, um sicher auf Array-Werte zuzugreifen (Wird für Fallbacks benötigt)
    $get_val = function($path, $default = '') use ($data) { // $character_data zu $data ändern!
        $keys = explode('.', $path);
        $value = $data; // Stelle sicher, dass $data hier verwendet wird
        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return $default;
            }
            $value = $value[$key];
        }
        return $value;
    };

    // 4. HTML generieren mit Tabs und Akkordeons
    ob_start();
    ?>
    <div class="dnd-character-sheet enhanced" data-character-id="<?php echo esc_attr( $character_post_id ); ?>">

        <!-- === Charakter Header (Außerhalb der Tabs) === -->
        <header class="sheet-header">
    <h1><?php echo esc_html( $data['basicInfo']['characterName'] ?? 'Unbenannter Charakter' ); ?></h1>
    <div class="char-meta">
        <span><?php echo dnd_helper_generate_wikidot_link( $data['basicInfo']['raceSlug'] ?? null, $data['basicInfo']['race'] ?? '', 'View Race' ); ?></span> |
        <span>
            <?php
            if ( ! empty( $data['basicInfo']['classes'] ) ) {
                $class_links = [];
                foreach ( $data['basicInfo']['classes'] as $class ) {
                    $class_name = $class['className'] ?? '';
                    $class_slug = $class['classSlug'] ?? null;
                    $subclass_name = $class['subClass'] ?? '';
                    $subclass_slug = $class['subClassSlug'] ?? null;
                    $level = $class['level'] ?? '';

                    $class_part = dnd_helper_generate_wikidot_link($class_slug, $class_name, 'View Class');
                    $subclass_part = !empty($subclass_name) ? ' (' . dnd_helper_generate_wikidot_link($subclass_slug, $subclass_name, 'View Subclass') . ')' : '';

                    $class_links[] = $class_part . $subclass_part . ' ' . esc_html($level);
                }
                echo implode( ' / ', $class_links );
            } ?>
        </span> |
        <span><?php echo dnd_helper_generate_wikidot_link( $data['basicInfo']['backgroundSlug'] ?? null, $data['basicInfo']['background'] ?? '', 'View Background' ); ?></span> |
        <span><?php echo esc_html( $data['basicInfo']['alignment'] ?? '' ); ?></span> |
        <span><?php _e( 'Spieler:', 'dnd-helper' ); ?> <?php echo esc_html( $data['basicInfo']['playerName'] ?? '' ); ?></span> |
        <span><?php _e( 'XP:', 'dnd-helper' ); ?> <?php echo esc_html( $data['basicInfo']['experiencePoints'] ?? '0' ); ?></span>
    </div>
</header>

        <!-- === Tab Navigation === -->
        <ul class="dnd-tabs-nav">
            <li><a class="dnd-tab-link active" href="#tab-overview" data-tab="overview"><?php _e('Übersicht', 'dnd-helper'); ?></a></li>
            <li><a class="dnd-tab-link" href="#tab-combat" data-tab="combat"><?php _e('Kampf', 'dnd-helper'); ?></a></li>
            <li><a class="dnd-tab-link" href="#tab-actions" data-tab="actions"><?php _e('Aktionen', 'dnd-helper'); ?></a></li>
            <li><a class="dnd-tab-link" href="#tab-equipment" data-tab="equipment"><?php _e('Ausrüstung', 'dnd-helper'); ?></a></li>
            <li><a class="dnd-tab-link" href="#tab-features" data-tab="features"><?php _e('Merkmale', 'dnd-helper'); ?></a></li>
            <li><a class="dnd-tab-link" href="#tab-background" data-tab="background"><?php _e('Hintergrund', 'dnd-helper'); ?></a></li>
        </ul>

        <!-- === Tab Content === -->
        <div class="dnd-tabs-content">

            <!-- == Tab: Übersicht == -->
            <div id="tab-overview" class="dnd-tab-pane active">
                <div class="tab-section columns-2">
                    <div class="col">
                        <div class="ability-scores"> <!-- Attribute (wie vorher) -->
                            <h3><?php _e( 'Attribute', 'dnd-helper' ); ?></h3>
                            <ul>
                            <?php foreach ( $data['abilityScores'] ?? [] as $key => $scoreData ) : ?>
                                <li>
                                    <span class="ability-name"><?php echo esc_html( ucfirst( $key ) ); ?></span>
                                    <span class="ability-score"><?php echo esc_html( $scoreData['score'] ?? '?' ); ?></span>
                                    <span class="ability-modifier">(<?php echo ( ( $scoreData['modifier'] ?? 0 ) >= 0 ? '+' : '' ) . esc_html( $scoreData['modifier'] ?? 0 ); ?>)</span>
                                </li>
                            <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="col">
                         <div class="proficiency-bonus"> <!-- Übungsbonus -->
                            <strong><?php _e('Übungsbonus:', 'dnd-helper'); ?></strong> +<?php echo esc_html($data['proficiencies']['bonus'] ?? 0); ?>
                         </div>
                         <div class="saving-throws"> <!-- Rettungswürfe (wie vorher) -->
                            <h4><?php _e( 'Rettungswürfe', 'dnd-helper' ); ?></h4>
                            <ul>
                           <?php $saving_throw_prof = $data['proficiencies']['savingThrows'] ?? [];
                           foreach ( $data['abilityScores'] ?? [] as $key => $scoreData ) :
                               $is_proficient = in_array( $key, $saving_throw_prof ); $modifier = $scoreData['modifier'] ?? 0;
                               $total_bonus = $is_proficient ? $modifier + ($data['proficiencies']['bonus'] ?? 0) : $modifier; ?>
                            <li>
                                <span class="prof-indicator"><?php echo $is_proficient ? '●' : '○'; ?></span>
                                <span class="save-bonus"><?php echo ( $total_bonus >= 0 ? '+' : '' ) . esc_html( $total_bonus ); ?></span>
                                <span class="save-name"><?php echo esc_html( ucfirst( $key ) ); ?></span>
                            </li>
                           <?php endforeach; ?>
                            </ul>
                        </div>
                         <div class="passive-perception"> <!-- Passive Wahrnehmung -->
                             <strong><?php _e('Passive Wahrnehmung:', 'dnd-helper'); ?></strong> <?php echo esc_html($data['passivePerception'] ?? 10 + ($data['skills']['perception']['modifier'] ?? 0)); ?>
                         </div>
                    </div>
                </div>
                 <div class="tab-section skills"> <!-- Fertigkeiten (wie vorher) -->
                    <h3><?php _e( 'Fertigkeiten', 'dnd-helper' ); ?></h3>
                    <ul>
                       <?php foreach ( $data['skills'] ?? [] as $key => $skillData ) :
                            $total_bonus = $skillData['modifier'] ?? 0; $ability = $skillData['ability'] ?? ''; ?>
                        <li>
                            <span class="prof-indicator"><?php echo ( $skillData['proficient'] ?? false ) ? '●' : '○'; ?></span>
                            <span class="skill-bonus"><?php echo ( $total_bonus >= 0 ? '+' : '' ) . esc_html( $total_bonus ); ?></span>
                            <span class="skill-name"><?php echo esc_html( ucfirst( preg_replace('/(?<!^)([A-Z])/', ' $1', $key) ) ); // CamelCase zu Leerzeichen ?></span>
                            <span class="skill-ability">(<?php echo esc_html( strtoupper( substr( $ability, 0, 3 ) ) ); ?>)</span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- == Tab: Kampf == -->
            <div id="tab-combat" class="dnd-tab-pane">
                <div class="tab-section columns-3"> <!-- Kampf-Hauptwerte -->
                    <div class="combat-box ac">
                        <label><?php _e('RK', 'dnd-helper'); ?></label>
                        <span class="value"><?php echo esc_html($data['combat']['armorClass']['value'] ?? '?'); ?></span>
                        <?php if (!empty($data['combat']['armorClass']['calculation'])): ?>
                            <small><?php echo esc_html($data['combat']['armorClass']['calculation']); ?></small>
                        <?php endif; ?>
                    </div>
                     <div class="combat-box initiative">
                        <label><?php _e('Initiative', 'dnd-helper'); ?></label>
                        <span class="value"><?php echo (($data['combat']['initiative'] ?? 0) >= 0 ? '+' : '') . esc_html($data['combat']['initiative'] ?? 0); ?></span>
                    </div>
                     <div class="combat-box speed">
                        <label><?php _e('Bewegung', 'dnd-helper'); ?></label>
                        <span class="value"><?php echo esc_html($data['combat']['speed']['walk'] ?? '?'); ?> ft</span>
                        <?php // TODO: Andere Speed-Typen anzeigen, falls vorhanden ?>
                    </div>
                </div>
                 <div class="tab-section columns-2"> <!-- HP, Hit Dice, Death Saves -->
                    <div class="col">
                         <div class="hitpoints">
							 <h4><?php _e('Trefferpunkte', 'dnd-helper'); ?></h4>
							 <?php
								// Lese die Werte primär aus den separaten Meta-Feldern,
								// Fallback auf JSON, wenn Meta-Feld nicht existiert (z.B. vor erstem Speichern)
								$hp_current_meta = get_post_meta($character_post_id, '_dnd_hp_current', true);
								$hp_max_meta = get_post_meta($character_post_id, '_dnd_hp_max', true);
								$hp_temp_meta = get_post_meta($character_post_id, '_dnd_hp_temporary', true);

								// Verwende Meta-Wert, wenn er numerisch ist, sonst Fallback auf JSON-Wert
								$hp_current = is_numeric($hp_current_meta) ? $hp_current_meta : $get_val('combat.hitPoints.current', '?');
								$hp_max = is_numeric($hp_max_meta) ? $hp_max_meta : $get_val('combat.hitPoints.max', '?');
								$hp_temp = is_numeric($hp_temp_meta) ? $hp_temp_meta : $get_val('combat.hitPoints.temporary', '0');
							 ?>
							 <p><?php _e('Maximal:', 'dnd-helper'); ?> <span class="hp-max"><?php echo esc_html($hp_max); ?></span></p>
							 <p>
								 <?php _e('Aktuell:', 'dnd-helper'); ?>
								 <span class="hp-current dnd-editable-field"
									   data-meta-key="_dnd_hp_current"
									   data-character-id="<?php echo esc_attr($character_post_id); ?>"
									   data-field-type="number"
									   title="<?php esc_attr_e('Klicken zum Bearbeiten', 'dnd-helper'); ?>">
									 <?php echo esc_html($hp_current); // Zeigt jetzt den aktuellen Wert an ?>
								 </span>
								 <span class="dnd-edit-spinner" style="display: none;"></span>
							 </p>
							 <p>
								 <?php _e('Temporär:', 'dnd-helper'); ?>
								  <span class="hp-temp dnd-editable-field"
									   data-meta-key="_dnd_hp_temporary"
									   data-character-id="<?php echo esc_attr($character_post_id); ?>"
									   data-field-type="number"
									   title="<?php esc_attr_e('Klicken zum Bearbeiten', 'dnd-helper'); ?>">
									 <?php echo esc_html($hp_temp); // Zeigt jetzt den aktuellen Wert an ?>
								  </span>
								 <span class="dnd-edit-spinner" style="display: none;"></span>
							 </p>
						 </div>
                    </div>
                    <div class="col">
                        <div class="hitdice">
                            <h4><?php _e('Trefferwürfel', 'dnd-helper'); ?></h4>
                             <p><?php _e('Gesamt:', 'dnd-helper'); ?> <?php echo esc_html($data['combat']['hitDice']['total'] ?? '?'); ?></p>
                             <p><?php _e('Verfügbar:', 'dnd-helper'); ?> <?php echo esc_html($data['combat']['hitDice']['current'] ?? '?'); ?></p>
                         </div>
                         <div class="death-saves">
                             <h4><?php _e('Todesschutzwürfe:', 'dnd-helper'); ?></h4>
                             <p><?php _e('Erfolge:', 'dnd-helper'); ?> <span class="death-save-dots"><?php echo str_repeat('●', $data['combat']['deathSaves']['successes'] ?? 0) . str_repeat('○', 3 - ($data['combat']['deathSaves']['successes'] ?? 0)); ?></span></p>
                             <p><?php _e('Fehlschläge:', 'dnd-helper'); ?> <span class="death-save-dots"><?php echo str_repeat('●', $data['combat']['deathSaves']['failures'] ?? 0) . str_repeat('○', 3 - ($data['combat']['deathSaves']['failures'] ?? 0)); ?></span></p>
                         </div>
                    </div>
                 </div>
            </div>

            <!-- == Tab: Aktionen == -->
            <div id="tab-actions" class="dnd-tab-pane">
                 <div class="tab-section attacks-spells"> <!-- Angriffe (wie vorher) -->
                    <h3><?php _e('Angriffe', 'dnd-helper'); ?></h3>
                    <table class="attacks-table">
                        <thead><tr><th>Name</th><th>Bonus</th><th>Schaden/Typ</th><th>Notizen</th></tr></thead>
                        <tbody>
                            <?php foreach($data['attacks'] ?? [] as $attack):
    $attack_name = $attack['name'] ?? '';
    $related_spell_slug = $attack['relatedSpellSlug'] ?? null;
    $attack_display = !empty($related_spell_slug) ? dnd_helper_generate_wikidot_link($related_spell_slug, $attack_name, 'View Spell') : esc_html($attack_name);
?>
<tr>
    <td><?php echo $attack_display; // Bereits escaped durch Helfer oder esc_html ?></td>
    <td><?php echo (($attack['attackBonus'] ?? 0) >= 0 ? '+' : '') . esc_html($attack['attackBonus'] ?? '?'); ?></td>
    <td><?php echo esc_html($attack['damage'] ?? ''); ?> <?php echo esc_html($attack['damageType'] ?? ''); ?></td>
    <td><?php echo esc_html($attack['notes'] ?? ''); ?></td>
</tr>
<?php endforeach; ?>
                        </tbody>
                    </table>
                 </div>
                 <?php if (isset($data['spellcasting']) && !empty($data['spellcasting']['spells']) ): ?>
                 <div class="tab-section spellcasting">
                    <h3><?php _e('Zauberwirken', 'dnd-helper'); ?></h3>
                    <p>
                        <strong><?php _e('Zauberattribut:', 'dnd-helper'); ?></strong> <?php echo esc_html(ucfirst($data['spellcasting']['spellcastingAbility'] ?? '?')); ?> |
                        <strong><?php _e('Rettungswurf-SG:', 'dnd-helper'); ?></strong> <?php echo esc_html($data['spellcasting']['spellSaveDC'] ?? '?'); ?> |
                        <strong><?php _e('Zauberangriffsbonus:', 'dnd-helper'); ?></strong> +<?php echo esc_html($data['spellcasting']['spellAttackBonus'] ?? '?'); ?>
                    </p>

                    <!-- Zauberplätze -->
                     <h4><?php _e('Zauberplätze', 'dnd-helper'); ?></h4>
                     <div class="spell-slots">
                         <?php foreach ($data['spellcasting']['slots'] ?? [] as $slot): ?>
                         <div class="slot-level">
                            <label><?php _e('Grad', 'dnd-helper'); ?> <?php echo esc_html($slot['level']); ?></label>
                            <span><?php echo esc_html($slot['used'] ?? 0); ?> / <?php echo esc_html($slot['total'] ?? 0); ?></span>
                         </div>
                         <?php endforeach; ?>
                     </div>

                    <!-- Zauberliste als Akkordeon -->
                    <div class="dnd-accordion-container spells-accordion">
                        <?php
                        // Zauber nach Grad gruppieren
                        $spells_by_level = [];
                        foreach ($data['spellcasting']['spells'] ?? [] as $spell) {
                            $level = intval($spell['level'] ?? -1);
                            $spells_by_level[$level][] = $spell;
                        }
                        ksort($spells_by_level); // Nach Grad sortieren
                        ?>
                        <?php foreach ($spells_by_level as $level => $spells): ?>
                            <?php if ($level >= 0): // Ignoriere ungültige Level ?>
                                <div class="dnd-accordion">
                                    <button class="dnd-accordion-header">
                                        <?php echo $level === 0 ? __('Tricks (Grad 0)', 'dnd-helper') : sprintf(__('Zauber Grad %d', 'dnd-helper'), $level); ?>
                                        <span>(<?php echo count($spells); ?>)</span>
                                    </button>
                                    <div class="dnd-accordion-content">
                                        <ul>
                                            <?php foreach($spells as $spell):
    $spell_name = $spell['name'] ?? 'Unbekannter Zauber';
    $spell_slug = $spell['spellSlug'] ?? null;
?>
<li>
    <strong><?php echo dnd_helper_generate_wikidot_link($spell_slug, $spell_name, 'View Spell'); ?></strong>
    <?php if ($level > 0) : ?>
        <em>(<?php echo ($spell['prepared'] ?? false) ? __('Vorbereitet', 'dnd-helper') : __('Nicht vorbereitet', 'dnd-helper'); ?>)</em>
    <?php endif; ?>
    <?php if (!empty($spell['source'])): ?>
         <small>[<?php echo esc_html($spell['source']); ?>]</small>
    <?php endif; ?>
    <?php if (!empty($spell['description'])): ?>
        <p><?php echo esc_html($spell['description']); ?></p>
    <?php endif; ?>
</li>
<?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div> <!-- /spells-accordion -->
                 </div>
                <?php endif; ?>
            </div>

            <!-- == Tab: Ausrüstung == -->
            <div id="tab-equipment" class="dnd-tab-pane">
                 <div class="tab-section currency">
                     <h3><?php _e('Währung', 'dnd-helper'); ?></h3>
                     <div class="currency-values">
                        <span><?php _e('KP:', 'dnd-helper'); ?> <?php echo esc_html($data['equipment']['currency']['cp'] ?? 0); ?></span>
                        <span><?php _e('SP:', 'dnd-helper'); ?> <?php echo esc_html($data['equipment']['currency']['sp'] ?? 0); ?></span>
                        <span><?php _e('EP:', 'dnd-helper'); ?> <?php echo esc_html($data['equipment']['currency']['ep'] ?? 0); ?></span>
                        <span><?php _e('GP:', 'dnd-helper'); ?> <?php echo esc_html($data['equipment']['currency']['gp'] ?? 0); ?></span>
                        <span><?php _e('PP:', 'dnd-helper'); ?> <?php echo esc_html($data['equipment']['currency']['pp'] ?? 0); ?></span>
                     </div>
                 </div>
                  <div class="tab-section inventory">
                    <h3><?php _e('Inventar', 'dnd-helper'); ?></h3>
                     <?php $items = $data['equipment']['items'] ?? []; ?>
                    <div class="dnd-accordion-container inventory-accordion">
                        <div class="dnd-accordion">
                            <button class="dnd-accordion-header">
                                <?php _e('Gegenstände anzeigen/verbergen', 'dnd-helper'); ?>
                                <span>(<?php echo count($items); ?>)</span>
                            </button>
                            <div class="dnd-accordion-content">
                                <ul>
                                <?php foreach ($items as $item):
    $item_name = $item['name'] ?? '';
    $item_slug = $item['itemSlug'] ?? null; // Nur für spezielle Items
?>
<li>
    <strong><?php echo dnd_helper_generate_wikidot_link($item_slug, $item_name, 'View Item'); ?></strong>
    (<?php echo esc_html($item['quantity'] ?? 1); ?>)
    <?php if (isset($item['weight']) && $item['weight'] > 0): ?>
        <small><?php echo esc_html($item['weight']); ?> lbs</small>
    <?php endif; ?>
    <?php if (isset($item['equipped']) && $item['equipped']): ?>
        <em class="equipped-tag">[<?php _e('Ausrüstet', 'dnd-helper'); ?>]</em>
    <?php endif; ?>
     <?php if (!empty($item['type'])): ?>
        <em class="type-tag">[<?php echo esc_html(ucfirst($item['type'])); ?>]</em>
    <?php endif; ?>
    <?php if (!empty($item['description'])): ?>
        <p><?php echo nl2br(esc_html($item['description'])); ?></p>
    <?php endif; ?>
</li>
<?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div> <!-- /inventory-accordion -->
                    <?php // TODO: Gesamtgewicht anzeigen, falls gewünscht ?>
                </div>
            </div>

            <!-- == Tab: Merkmale == -->
            <div id="tab-features" class="dnd-tab-pane">

     <!-- NEU: Abschnitt für Feats -->
     <?php if (isset($data['feats']) && !empty($data['feats'])): ?>
     <div class="tab-section feats-list">
        <h3><?php _e('Talente (Feats)', 'dnd-helper'); ?></h3>
         <div class="dnd-accordion-container feats-accordion">
             <div class="dnd-accordion">
                  <button class="dnd-accordion-header">
                     <?php _e('Talente anzeigen/verbergen', 'dnd-helper'); ?>
                     <span>(<?php echo count($data['feats']); ?>)</span>
                  </button>
                  <div class="dnd-accordion-content">
                     <ul>
                     <?php foreach ($data['feats'] as $feat):
                         $feat_name = $feat['name'] ?? '';
                         $feat_slug = $feat['featSlug'] ?? null;
                     ?>
                         <li>
                             <strong><?php echo dnd_helper_generate_wikidot_link($feat_slug, $feat_name, 'View Feat'); ?></strong>
                             <?php if (!empty($feat['source'])): ?>
                                 <small>(<?php echo esc_html($feat['source']); ?>)</small>
                             <?php endif; ?>
                             <?php if (!empty($feat['description'])): ?>
                                 <p><?php echo nl2br(esc_html($feat['description'])); ?></p>
                             <?php endif; ?>
                         </li>
                     <?php endforeach; ?>
                     </ul>
                 </div>
             </div>
         </div> <!-- /feats-accordion -->
     </div>
     <?php endif; ?>


     <!-- Bestehender Abschnitt für Features & Traits -->
     <div class="tab-section features-traits">
        <h3><?php _e('Klassen-/Rassen-/Hintergrundmerkmale', 'dnd-helper'); ?></h3>
         <?php $features = $data['featuresAndTraits'] ?? []; ?>
        <div class="dnd-accordion-container features-accordion">
            <div class="dnd-accordion">
                 <button class="dnd-accordion-header">
                    <?php _e('Merkmale anzeigen/verbergen', 'dnd-helper'); ?>
                    <span>(<?php echo count($features); ?>)</span>
                 </button>
                 <div class="dnd-accordion-content">
                    <ul>
                    <?php foreach ($features as $feature):
                        $feature_name = $feature['name'] ?? '';
                        $feature_slug = $feature['featureSlug'] ?? null; // Optionaler Slug für Features
                    ?>
                        <li>
                            <strong><?php echo dnd_helper_generate_wikidot_link($feature_slug, $feature_name, 'View Feature'); ?></strong>
                            <?php if (!empty($feature['source'])): ?>
                                <small>(<?php echo esc_html($feature['source']); ?>)</small>
                            <?php endif; ?>
                            <?php if (isset($feature['uses']) && !empty($feature['uses']['max'])): ?>
                                <em>[<?php _e('Nutzungen:', 'dnd-helper'); ?> <?php echo esc_html($feature['uses']['current'] ?? 0); ?>/<?php echo esc_html($feature['uses']['max']); ?> (<?php echo esc_html($feature['uses']['recharge'] ?? ''); ?>)]</em>
                            <?php endif; ?>
                            <?php if (!empty($feature['description'])): ?>
                                <p><?php echo nl2br(esc_html($feature['description'])); ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div> <!-- /features-accordion -->
    </div>
</div>

            <!-- == Tab: Hintergrund == -->
            <div id="tab-background" class="dnd-tab-pane">
                <div class="tab-section columns-2">
                    <div class="col personality">
                        <h3><?php _e('Persönlichkeit', 'dnd-helper'); ?></h3>
                        <?php if(isset($data['personality'])): $p = $data['personality']; ?>
                            <p><strong><?php _e('Züge:', 'dnd-helper'); ?></strong><br><?php echo nl2br(esc_html($p['traits'] ?? '')); ?></p>
                            <p><strong><?php _e('Ideale:', 'dnd-helper'); ?></strong><br><?php echo nl2br(esc_html($p['ideals'] ?? '')); ?></p>
                            <p><strong><?php _e('Bindungen:', 'dnd-helper'); ?></strong><br><?php echo nl2br(esc_html($p['bonds'] ?? '')); ?></p>
                            <p><strong><?php _e('Makel:', 'dnd-helper'); ?></strong><br><?php echo nl2br(esc_html($p['flaws'] ?? '')); ?></p>
                        <?php endif; ?>
                    </div>
                     <div class="col appearance">
                        <h3><?php _e('Aussehen', 'dnd-helper'); ?></h3>
                         <?php if(isset($data['appearance'])): $a = $data['appearance']; ?>
                            <p><strong><?php _e('Alter:', 'dnd-helper'); ?></strong> <?php echo esc_html($a['age'] ?? ''); ?></p>
                            <p><strong><?php _e('Größe:', 'dnd-helper'); ?></strong> <?php echo esc_html($a['height'] ?? ''); ?></p>
                            <p><strong><?php _e('Gewicht:', 'dnd-helper'); ?></strong> <?php echo esc_html($a['weight'] ?? ''); ?></p>
                            <p><strong><?php _e('Augen:', 'dnd-helper'); ?></strong> <?php echo esc_html($a['eyes'] ?? ''); ?></p>
                            <p><strong><?php _e('Haut:', 'dnd-helper'); ?></strong> <?php echo esc_html($a['skin'] ?? ''); ?></p>
                            <p><strong><?php _e('Haare:', 'dnd-helper'); ?></strong> <?php echo esc_html($a['hair'] ?? ''); ?></p>
                            <?php if (!empty($a['description'])): ?>
                            <p><strong><?php _e('Beschreibung:', 'dnd-helper'); ?></strong><br><?php echo nl2br(esc_html($a['description'])); ?></p>
                             <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                 <div class="tab-section backstory-allies">
                    <h3><?php _e('Hintergrundgeschichte & Verbündete', 'dnd-helper'); ?></h3>
                     <?php if (!empty($data['backstory'])): ?>
                    <p><strong><?php _e('Geschichte:', 'dnd-helper'); ?></strong><br><?php echo nl2br(esc_html($data['backstory'])); ?></p>
                     <?php endif; ?>
                     <?php if (!empty($data['alliesAndOrganizations'])): ?>
                     <p><strong><?php _e('Verbündete & Organisationen:', 'dnd-helper'); ?></strong><br><?php echo nl2br(esc_html($data['alliesAndOrganizations'])); ?></p>
                     <?php endif; ?>
                 </div>
                 <div class="tab-section notes">
                    <h3><?php _e('Notizen', 'dnd-helper'); ?></h3>
                     <ul>
                     <?php foreach ($data['notes'] ?? [] as $note): ?>
                         <li><?php echo esc_html($note); ?></li>
                     <?php endforeach; ?>
                     </ul>
                 </div>
            </div>

        </div> <!-- /dnd-tabs-content -->

    </div> <!-- /dnd-character-sheet -->
    <?php
    $output = ob_get_clean();
    return $output;
}

/**
 * Registriert und lädt Assets für das Charakterblatt (CSS & JS).
 */
function dnd_register_character_sheet_assets() {
    // CSS (wie vorher)
    $css_file_url = plugin_dir_url( __FILE__ ) . '../assets/css/character-sheet.css';
    $css_file_path = plugin_dir_path( __FILE__ ) . '../assets/css/character-sheet.css';
    $css_version = file_exists($css_file_path) ? filemtime($css_file_path) : DND_HELPER_VERSION;
    wp_register_style( 'dnd-character-sheet-style', $css_file_url, array(), $css_version );

    // NEUES JavaScript für Tabs & Akkordeons
    $js_file_url = plugin_dir_url( __FILE__ ) . '../assets/js/sheet-interaction.js';
    $js_file_path = plugin_dir_path( __FILE__ ) . '../assets/js/sheet-interaction.js';
    $js_version = file_exists($js_file_path) ? filemtime($js_file_path) : DND_HELPER_VERSION;
    wp_register_script(
        'dnd-sheet-interaction-script',
        $js_file_url,
        array('jquery'), // Abhängig von jQuery
        $js_version,
        true // Im Footer laden
    );
	wp_localize_script('dnd-sheet-interaction-script', 'dndSheetData', array(
    'ajaxUrl'     => admin_url('admin-ajax.php'),
    'updateNonce' => wp_create_nonce('dnd_update_field_nonce'), // Für Inline Edit
    'loadSheetNonce' => wp_create_nonce('dnd_load_sheet_nonce') // NEU für das Laden des Sheets
));
}
add_action( 'wp_enqueue_scripts', 'dnd_register_character_sheet_assets' );


/**
 * Registriert den Shortcode für den D&D Chat.
 */
function dnd_register_chat_shortcode() {
    add_shortcode( 'dnd_chat', 'dnd_chat_shortcode_handler' );
}
add_action( 'init', 'dnd_register_chat_shortcode' );

/**
 * Die Handler-Funktion für den [dnd_chat] Shortcode.
 *
 * @param array $atts Shortcode-Attribute (derzeit keine verwendet).
 * @return string Der HTML-Output für den Chat.
 */
function dnd_chat_shortcode_handler( $atts ) {
    // 1. Prüfen, ob der Benutzer eingeloggt ist
    if ( ! is_user_logged_in() ) {
        return '<p class="dnd-error">' . __( 'Bitte logge dich ein, um den Chat zu nutzen.', 'dnd-helper' ) . '</p>';
    }

    // 2. Benötigte Skripte und Stile laden (Enqueue)
    wp_enqueue_style( 'dnd-chat-style' );
    wp_enqueue_script( 'dnd-chat-script' );

    // 3. HTML für den Chat generieren
    ob_start();
    ?>
    <div id="dnd-chat-container">
        <h2><?php _e( 'D&D Chat', 'dnd-helper' ); ?></h2>
        <div id="dnd-chat-log" aria-live="polite" aria-atomic="false">
            <!-- Chatnachrichten werden hier per JS eingefügt -->
            <p><?php _e( 'Lade Nachrichten...', 'dnd-helper' ); ?></p>
        </div>
        <div id="dnd-chat-input-area">
            <label for="dnd-chat-message-input" class="screen-reader-text"><?php _e( 'Nachricht eingeben', 'dnd-helper' ); ?></label>
            <input type="text" id="dnd-chat-message-input" placeholder="<?php esc_attr_e( 'Nachricht oder /roll Befehl...', 'dnd-helper' ); ?>">
            <button id="dnd-chat-send-button"><?php _e( 'Senden', 'dnd-helper' ); ?></button>
        </div>
         <div id="dnd-chat-status" aria-live="polite"></div> <!-- Für Statusmeldungen (z.B. Fehler) -->
    </div>
    <?php
    $output = ob_get_clean();
    return $output;
}

/**
 * Registriert und lädt die CSS & JS für den Chat.
 */
function dnd_register_chat_assets() {
    // CSS
    $css_file_url = plugin_dir_url( __FILE__ ) . '../assets/css/chat.css';
    $css_file_path = plugin_dir_path( __FILE__ ) . '../assets/css/chat.css';
    $css_version = file_exists($css_file_path) ? filemtime($css_file_path) : DND_HELPER_VERSION;

    wp_register_style( 'dnd-chat-style', $css_file_url, array(), $css_version );

    // JavaScript
    $js_file_url = plugin_dir_url( __FILE__ ) . '../assets/js/chat.js';
    $js_file_path = plugin_dir_path( __FILE__ ) . '../assets/js/chat.js';
    $js_version = file_exists($js_file_path) ? filemtime($js_file_path) : DND_HELPER_VERSION;

    wp_register_script(
        'dnd-chat-script',
        $js_file_url,
        array( 'jquery' ), // Abhängigkeit von jQuery (vereinfacht AJAX und DOM-Manipulation)
        $js_version,
        true // Im Footer laden
    );

    // Daten an das JavaScript übergeben (AJAX URL, Nonce, etc.)
    wp_localize_script( 'dnd-chat-script', 'dndChatData', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'dnd_chat_nonce' ), // Nonce für AJAX-Sicherheit
        'pollInterval' => 5000, // Intervall für das Abfragen neuer Nachrichten (5 Sekunden)
        'initialLoadCount' => 20, // Wie viele Nachrichten initial laden?
        'text' => array( // Übersetzbare Strings für JS
            'sendMessage' => __( 'Senden', 'dnd-helper' ),
            'sending' => __( 'Sende...', 'dnd-helper' ),
            'loading' => __( 'Lade Nachrichten...', 'dnd-helper' ),
            'error' => __( 'Ein Fehler ist aufgetreten.', 'dnd-helper' ),
            'emptyMessage' => __( 'Bitte gib eine Nachricht ein.', 'dnd-helper' ),
            'rollPrefix' => '/roll', // Oder '/r' - definieren, was wir erkennen wollen
            'systemUser' => __('System', 'dnd-helper'),
        )
    ) );
}
// Wir registrieren die Assets immer, aber enqueuen sie nur im Shortcode.
add_action( 'wp_enqueue_scripts', 'dnd_register_chat_assets' );

?>