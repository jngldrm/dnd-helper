<?php
/**
 * Template für den Sitzungen-Tab.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Hole alle Sessions aus dem Post Type dndt_session
$sessions = get_posts( array(
    'post_type' => 'dndt_session',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC' // Chronologisch, neuere zuerst
) );

?>

<div class="tab-section">
    <h3><?php _e('Sitzungen', 'dnd-helper'); ?></h3>
    <?php if (!empty($sessions)): ?>
        <div class="dnd-accordion-container sessions-accordion">
            <?php 
            foreach ($sessions as $session):
                // Session-Metadaten abrufen
                $session_id = $session->ID;
                $speaker_mapping = get_post_meta($session_id, '_dndt_speaker_mapping', true);
                $ai_summary = get_post_meta($session_id, '_dndt_session_summary', true);
                $speakers = get_post_meta($session_id, '_dndt_session_speakers', true);
                
                // Speaker-Mapping decodieren falls JSON
                if (is_string($speaker_mapping)) {
                    $speaker_mapping = json_decode($speaker_mapping, true);
                }
                if (!is_array($speaker_mapping)) {
                    $speaker_mapping = array();
                }
                
                // Speakers decodieren falls JSON
                if (is_string($speakers)) {
                    $speakers = json_decode($speakers, true);
                }
                if (!is_array($speakers)) {
                    $speakers = array();
                }
                
                // Hole Mitspieler-Daten für die Speaker
                $mitspieler_data = array();
                foreach ($speaker_mapping as $speaker_name => $mitspieler_id) {
                    if (!empty($mitspieler_id)) {
                        $mitspieler = get_post($mitspieler_id);
                        if ($mitspieler) {
                            $mitspieler_data[$speaker_name] = array(
                                'name' => $mitspieler->post_title,
                                'image' => get_the_post_thumbnail_url($mitspieler_id, 'thumbnail')
                            );
                        }
                    }
                }
                
                // Formatiere das Datum der Session
                $session_date = get_the_date('', $session);
                ?>
                <div class="dnd-accordion">
                    <button class="dnd-accordion-header">
                        <span class="session-title"><?php echo esc_html($session->post_title); ?></span>
                        <div class="session-meta">
                            <?php if (!empty($mitspieler_data)): ?>
                                <div class="session-speakers">
                                    <?php foreach ($mitspieler_data as $speaker_name => $data): ?>
                                        <div class="speaker-avatar" title="<?php echo esc_attr($data['name']); ?>">
                                            <?php if (!empty($data['image'])): ?>
                                                <img src="<?php echo esc_url($data['image']); ?>" alt="<?php echo esc_attr($data['name']); ?>" class="speaker-image">
                                            <?php else: ?>
                                                <div class="speaker-placeholder" title="<?php echo esc_attr($data['name']); ?>">
                                                    <?php echo esc_html(substr($data['name'], 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <span class="session-date"><?php echo esc_html($session_date); ?></span>
                        </div>
                    </button>
                    <div class="dnd-accordion-content">
                        <?php if (!empty($ai_summary)): ?>
                            <div class="session-summary">
                                <div class="summary-display">
                                    <?php
                                    // Markdown-Syntax in HTML umwandeln
                                    $summary_html = wp_kses_post($ai_summary);
                                    
                                    // Einfache Markdown-Konvertierung
                                    $summary_html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $summary_html);
                                    $summary_html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $summary_html);
                                    $summary_html = preg_replace('/^### (.*?)$/m', '<h4>$1</h4>', $summary_html);
                                    $summary_html = preg_replace('/^## (.*?)$/m', '<h3>$1</h3>', $summary_html);
                                    $summary_html = preg_replace('/^# (.*?)$/m', '<h2>$1</h2>', $summary_html);
                                    $summary_html = preg_replace('/^- (.*?)$/m', '<li>$1</li>', $summary_html);
                                    $summary_html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $summary_html);
                                    $summary_html = nl2br($summary_html);
                                    
                                    echo $summary_html;
                                    ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="session-content">
                                <?php 
                                // Verwende die neue Funktion, die das Transkript mit Sprecher-Zuordnung formatiert
                                $formatted_transcript = dnd_format_transcript_with_speakers($session_id);
                                echo wp_kses_post($formatted_transcript);
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p><?php _e('Keine Sitzungen für diese Kampagne gefunden.', 'dnd-helper'); ?></p>
    <?php endif; ?>
</div>


