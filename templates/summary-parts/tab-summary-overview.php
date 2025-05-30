<?php
/**
 * Template für den Übersichts-Tab der Kampagnenzusammenfassung.
 * Verfügbare Variablen: $data (gesamtes Kampagnen-Array), $get_val (Helferfunktion), $campaign_post_id.
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Direkten Zugriff verhindern
?>

<?php if (isset($data['nextSessionBriefing'])): $briefing = $data['nextSessionBriefing']; ?>
<div class="tab-section">
    <h3><?php _e('Übersicht', 'dnd-helper'); ?></h3>
    <div class="dnd-accordion-container overview-accordion">
        <div class="dnd-accordion">
            <button class="dnd-accordion-header">Briefing nächste Session</button>
            <div class="dnd-accordion-content">
                                <?php if (!empty($briefing['currentSituation'])): ?>
                            <p><strong><?php _e('Aktuelle Situation:', 'dnd-helper'); ?></strong> <?php echo esc_html($briefing['currentSituation']); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($briefing['locations'])): ?>
                            <h4><?php _e('Aufenthaltsorte der Gruppen:', 'dnd-helper'); ?></h4>
                            <ul>
                                <?php foreach($briefing['locations'] as $loc): ?>
                                    <li><strong><?php echo esc_html($loc['characterGroup'] ?? ''); ?>:</strong> <?php echo esc_html($loc['location'] ?? ''); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if (!empty($briefing['cliffhanger'])): ?>
                            <p><strong><?php _e('Cliffhanger:', 'dnd-helper'); ?></strong> <?php echo esc_html($briefing['cliffhanger']); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($briefing['primaryObjective'])): ?>
                            <p><strong><?php _e('Primäres Ziel:', 'dnd-helper'); ?></strong> <?php echo esc_html($briefing['primaryObjective']); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($briefing['secondaryObjectives'])): ?>
                            <h4><?php _e('Sekundäre Ziele:', 'dnd-helper'); ?></h4>
                            <ul>
                                <?php foreach($briefing['secondaryObjectives'] as $obj): ?>
                                    <li><?php echo esc_html($obj); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if (!empty($briefing['immediateThreats'])): ?>
                            <h4><?php _e('Unmittelbare Bedrohungen:', 'dnd-helper'); ?></h4>
                            <ul>
                                <?php foreach($briefing['immediateThreats'] as $threat): ?>
                                    <li><?php echo esc_html($threat); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if (!empty($briefing['keyOpenQuestions'])): ?>
                            <h4><?php _e('Wichtige offene Fragen:', 'dnd-helper'); ?></h4>
                            <ul>
                                <?php foreach($briefing['keyOpenQuestions'] as $q): ?>
                                    <li><?php echo esc_html($q); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
            </div>
        </div>
    
        <div class="dnd-accordion">
            <button class="dnd-accordion-header">Gesamtzusammenfassung</button>
            <div class="dnd-accordion-content">
                <h3><?php _e('Gesamtzusammenfassung', 'dnd-helper'); ?></h3>
                <p><?php echo nl2br(esc_html($get_val('campaignSummary.overall', __('Keine Gesamtzusammenfassung vorhanden.', 'dnd-helper')))); ?></p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>