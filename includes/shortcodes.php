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


/**
 * Die Handler-Funktion für den [dnd_character_sheet] Shortcode.
 * Zeigt eine Auswahl und das Blatt des gewählten Charakters des eingeloggten Benutzers an.
 *
 * @param array $atts Shortcode-Attribute (ignoriert).
 * @return string HTML für Auswahl und Charakterblatt-Container.
 */
function dnd_character_sheet_shortcode_handler( $atts ) {

    if ( ! is_user_logged_in() ) {
        return '<p class="dnd-notice">' . __( 'Bitte logge dich ein, um dein Charakterblatt zu sehen.', 'dnd-helper' ) . '</p>';
    }

    $user_id = get_current_user_id();
    $initial_character_id = 0; // ID des Charakters, der initial geladen wird
    $user_characters = []; // Array für Charakter-IDs und Namen

    // 1. Alle Charaktere des Benutzers abfragen
    $args = array(
        'post_type' => 'dnd_character',
        'post_status' => 'publish',
        'author' => $user_id,
        'posts_per_page' => -1, // Alle holen
        'orderby' => 'title', // Nach Namen sortieren für die Dropdown-Liste
        'order' => 'ASC',
        // 'fields' => 'ids', // Wir brauchen auch den Titel
    );
    $character_query = new WP_Query( $args );

    if ( $character_query->have_posts() ) {
        while ( $character_query->have_posts() ) {
            $character_query->the_post();
            $char_id = get_the_ID();
            $user_characters[$char_id] = get_the_title(); // Speichere ID => Name
            if ( $initial_character_id === 0 ) {
                $initial_character_id = $char_id; // Nimm den ersten als initialen Charakter
            }
        }
        wp_reset_postdata();
    } else {
        return '<p class="dnd-error">' . __( 'Für deinen Benutzer wurde noch kein Charakterblatt angelegt oder veröffentlicht.', 'dnd-helper' ) . '</p>';
    }

    // 2. Assets laden (CSS + JS)
    wp_enqueue_style( 'dnd-character-sheet-style' );
    wp_enqueue_script( 'dnd-sheet-interaction-script' );
    // Stelle sicher, dass die Daten für JS (Nonces etc.) übergeben werden (in dnd_register_character_sheet_assets)

    // 3. HTML für Auswahl und Container generieren
    ob_start();
    ?>
    <div class="dnd-character-selector-area">
        <label for="dnd-character-selector"><?php _e('Wähle deinen Charakter:', 'dnd-helper'); ?></label>
        <select id="dnd-character-selector" name="dnd_character_selector">
            <?php foreach ($user_characters as $id => $name): ?>
                <option value="<?php echo esc_attr($id); ?>" <?php selected($id, $initial_character_id); ?>>
                    <?php echo esc_html($name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <span id="dnd-sheet-loader" style="display: none; margin-left: 10px;"><?php _e('Lade...', 'dnd-helper'); ?></span>
    </div>

    <div id="dnd-character-sheet-display" class="dnd-character-sheet-wrapper">
        <?php
        // Initial das erste Charakterblatt laden und rendern
        if ( $initial_character_id > 0 ) {
             // Lade die Funktion, falls sie in einer anderen Datei ist
             // require_once DND_HELPER_PLUGIN_DIR . 'includes/template-tags.php';
            echo dnd_get_rendered_character_sheet_html( $initial_character_id );
        }
        ?>
    </div>
    <?php
    return ob_get_clean();
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
    'loadSheetNonce' => wp_create_nonce('dnd_load_sheet_nonce'),
	'updateSlotNonce' => wp_create_nonce('dnd_update_slot_nonce'),
	'updateResourceNonce' => wp_create_nonce('dnd_update_resource_nonce')
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
			'rollPrefix' => '/roll', // Oder '/r'
			'systemUser' => __('System', 'dnd-helper'),
			'noMessages' => __('Keine Nachrichten bisher.', 'dnd-helper'), // <-- NEU HINZUGEFÜGT
			'fetchError' => __('Fehler beim Abrufen von Nachrichten.', 'dnd-helper'), // <-- NEU (Beispiel)
			'sendError' => __('Fehler beim Senden der Nachricht.', 'dnd-helper'), // <-- NEU (Beispiel)
		)
    ) );
}
// Wir registrieren die Assets immer, aber enqueuen sie nur im Shortcode.
add_action( 'wp_enqueue_scripts', 'dnd_register_chat_assets' );

// =========================================================================
// == KAMPAGNEN-ZUSAMMENFASSUNG SHORTCODE
// =========================================================================

/**
 * Registriert den Shortcode für die Kampagnen-Zusammenfassung.
 */
function dnd_register_campaign_summary_shortcode() {
    add_shortcode( 'dnd_campaign_summary', 'dnd_campaign_summary_shortcode_handler' );
}
add_action( 'init', 'dnd_register_campaign_summary_shortcode' );

/**
 * Die Handler-Funktion für den [dnd_campaign_summary] Shortcode.
 *
 * @param array $atts Shortcode-Attribute. Erwartet 'id' des Kampagnen-Posts.
 * @return string Der HTML-Output für die Kampagnen-Zusammenfassung.
 */
function dnd_campaign_summary_shortcode_handler( $atts ) {
    // Standardwerte und Attribute parsen
    $atts = shortcode_atts( array(
        'id' => 0, // ID des Kampagnen-Posts
    ), $atts, 'dnd_campaign_summary' );

    $campaign_post_id = intval( $atts['id'] );

    if ( $campaign_post_id <= 0 ) {
        return '<p class="dnd-error">' . __( 'Fehler: Bitte geben Sie eine gültige Kampagnen-ID im Shortcode an (z.B. [dnd_campaign_summary id="123"]).', 'dnd-helper' ) . '</p>';
    }

    // Prüfen, ob der Post existiert und vom Typ 'dnd_campaign' ist
    $post = get_post( $campaign_post_id );
    if ( ! $post || $post->post_type !== 'dnd_campaign' ) {
        return '<p class="dnd-error">' . sprintf( __( 'Fehler: Kampagne mit der ID %d nicht gefunden oder ist kein Kampagnen-Post.', 'dnd-helper' ), $campaign_post_id ) . '</p>';
    }

    // Assets laden (wir können die gleichen wie für das Charakterblatt verwenden, da Tabs/Akkordeons genutzt werden)
    wp_enqueue_style( 'dnd-character-sheet-style' ); // Oder einen eigenen Stil 'dnd-summary-style' erstellen
    wp_enqueue_script( 'dnd-sheet-interaction-script' ); // Für Tabs/Akkordeons

    // HTML über eine separate Funktion generieren lassen
    return dnd_get_rendered_campaign_summary_html( $campaign_post_id );
}


/**
 * Generiert das HTML für eine Kampagnen-Zusammenfassung anhand der Post-ID.
 *
 * @param int $campaign_post_id Die ID des Kampagnen-Posts.
 * @return string Das generierte HTML oder eine Fehlermeldung.
 */
function dnd_get_rendered_campaign_summary_html( $campaign_post_id ) {
    // 1. JSON-Daten abrufen
    $campaign_json = get_post_meta( $campaign_post_id, '_dnd_campaign_json_data', true );
    if ( ! $campaign_json ) {
        return '<p class="dnd-error">' . __( 'Fehler: Keine JSON-Daten für diese Kampagne gefunden.', 'dnd-helper' ) . '</p>';
    }
    $data = json_decode( $campaign_json, true );
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        // Optional: error_log()
        return '<p class="dnd-error">' . __( 'Fehler: Kampagnen-JSON-Daten sind korrupt.', 'dnd-helper' ) . '</p>';
    }

    // 2. Helferfunktion für sicheren Zugriff (optional, kann auch direkt zugegriffen werden)
    $get_val = function($path, $default = '', $context_data = null) use ($data) {
        $current_data = $context_data ?: $data; // Erlaube Übergabe eines Sub-Arrays
        $keys = explode('.', $path);
        $value = $current_data;
        foreach ($keys as $key) {
            if (!isset($value[$key])) { return $default; }
            $value = $value[$key];
        }
        return $value;
    };

        // Hole das Beitragsbild
    $campaign_image_html_large = ''; // Für die erweiterte Ansicht
    $campaign_image_html_small = ''; // Für die Vorschau
    if ( has_post_thumbnail( $campaign_post_id ) ) {
        $campaign_image_html_large = get_the_post_thumbnail( $campaign_post_id, 'medium', array('class' => 'campaign-header-image-expanded golden-frame') );
        $campaign_image_html_small = get_the_post_thumbnail( $campaign_post_id, 'thumbnail', array('class' => 'campaign-header-image-preview') );
    }

    // 3. HTML generieren
    ob_start();
    ?>
    <div id="campaign-summary-<?php echo esc_attr( $campaign_post_id ); ?>" class="dnd-campaign-summary dnd-character-sheet enhanced" data-campaign-id="<?php echo esc_attr( $campaign_post_id ); ?>">
        <header class="sheet-header campaign-header  expanding-header <?php echo $campaign_image_html_large ? 'has-image' : 'no-image'; ?>">
            <?php if ( $campaign_image_html_large ): ?>
                <div class="char-image-trigger">
                    <?php echo $campaign_image_html_large; ?>
                </div>
            <?php endif; ?>

            <div class="char-info-container">
                    <div class="char-info-main">
                        <h1><?php echo esc_html( $get_val('campaignTitle', __('Unbenannte Kampagne', 'dnd-helper')) ); ?></h1>
                    </div>
                    <div class="char-info-details">
                        <p class="campaign-last-played">
                            <?php _e('Letzte Session:', 'dnd-helper'); ?>
                            <?php
                            $last_played = $get_val('campaignSummary.lastPlayedDate');
                            if ($last_played) {
                                // Versuche, Datum zu formatieren (abhängig von WP-Einstellungen)
                                try {
                                    $date_obj = new DateTime($last_played);
                                    echo esc_html($date_obj->format(get_option('date_format', 'Y-m-d')));
                                } catch (Exception $e) {
                                    echo esc_html($last_played); // Fallback auf rohes Datum
                                }
                            } else {
                                echo '-';
                            }
                            ?>
                        </p>
                    </div>
            </div>
        </header>

        <!-- Tab Navigation für Kampagne -->
        <ul class="dnd-tabs-nav">
            <li><a class="dnd-tab-link active" href="#summary-tab-overview-<?php echo esc_attr($campaign_post_id); ?>" data-tab="overview-<?php echo esc_attr($campaign_post_id); ?>"><?php _e('Übersicht', 'dnd-helper'); ?></a></li>
            <li><a class="dnd-tab-link" href="#summary-tab-acts-<?php echo esc_attr($campaign_post_id); ?>" data-tab="acts-<?php echo esc_attr($campaign_post_id); ?>"><?php _e('Akte/Zusammenfassung', 'dnd-helper'); ?></a></li>
            <li><a class="dnd-tab-link" href="#summary-tab-sessions-<?php echo esc_attr($campaign_post_id); ?>" data-tab="sessions-<?php echo esc_attr($campaign_post_id); ?>"><?php _e('Sitzungen', 'dnd-helper'); ?></a></li>
            <li><a class="dnd-tab-link" href="#summary-tab-quests-<?php echo esc_attr($campaign_post_id); ?>" data-tab="quests-<?php echo esc_attr($campaign_post_id); ?>"><?php _e('Quests', 'dnd-helper'); ?></a></li>
            <li><a class="dnd-tab-link" href="#summary-tab-characters-<?php echo esc_attr($campaign_post_id); ?>" data-tab="characters-<?php echo esc_attr($campaign_post_id); ?>"><?php _e('Charaktere', 'dnd-helper'); ?></a></li>
            <li><a class="dnd-tab-link" href="#summary-tab-npcs-<?php echo esc_attr($campaign_post_id); ?>" data-tab="npcs-<?php echo esc_attr($campaign_post_id); ?>"><?php _e('NPCs', 'dnd-helper'); ?></a></li>
            <li><a class="dnd-tab-link" href="#summary-tab-factions-<?php echo esc_attr($campaign_post_id); ?>" data-tab="factions-<?php echo esc_attr($campaign_post_id); ?>"><?php _e('Fraktionen', 'dnd-helper'); ?></a></li>
            <li><a class="dnd-tab-link" href="#summary-tab-items-<?php echo esc_attr($campaign_post_id); ?>" data-tab="items-<?php echo esc_attr($campaign_post_id); ?>"><?php _e('Gegenstände', 'dnd-helper'); ?></a></li>
        </ul>

        <!-- Tab Content für Kampagne -->
        <div class="dnd-tabs-content">
            <?php
            // Definiere die Template-Teile für jeden Tab
            $tab_templates = [
                'overview'   => 'tab-summary-overview.php',
                'acts'       => 'tab-summary-acts.php',
                'sessions'   => 'tab-summary-sessions.php',
                'quests'     => 'tab-summary-quests.php',
                'characters' => 'tab-summary-characters.php',
                'npcs'       => 'tab-summary-npcs.php',
                'factions'   => 'tab-summary-factions.php',
                'items'      => 'tab-summary-items.php',
            ];

            $is_first_tab = true;
            foreach ($tab_templates as $tab_slug => $template_file) {
                $active_class = $is_first_tab ? 'active' : '';
                echo '<div id="summary-tab-' . esc_attr($tab_slug) . '-' . esc_attr($campaign_post_id) . '" class="dnd-tab-pane ' . $active_class . '">';
                // Übergebe Variablen an die Templates (wichtig!)
                // $data und $get_val sind bereits im Scope. $campaign_post_id auch.
                include( DND_HELPER_PLUGIN_DIR . 'templates/summary-parts/' . $template_file );
                echo '</div>';
                $is_first_tab = false;
            }
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

?>