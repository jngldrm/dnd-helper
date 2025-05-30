<?php
/**
 * Template für den Akte/Zusammenfassungs-Tab.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$act_updates = $get_val('campaignSummary.actUpdates', []);
?>

<div class="tab-section">
    <h3><?php _e('Akte der Kampagne', 'dnd-helper'); ?></h3>
    <?php if (!empty($act_updates)): ?>
        <div class="dnd-accordion-container acts-accordion">
            <?php foreach ($act_updates as $act): ?>
                <div class="dnd-accordion">
                    <button class="dnd-accordion-header">
                        <?php printf(esc_html($act['title'] ?? 'Unbenannter Akt')); ?>
                    </button>
                    <div class="dnd-accordion-content">
                        <p><strong><?php _e('Zusammenfassung des Akts:', 'dnd-helper'); ?></strong></p>
                        <p><?php echo nl2br(esc_html($act['summary'] ?? 'Keine Zusammenfassung.')); ?></p>

                        <?php if (!empty($act['sessionDetails'])): ?>
                            <h4><?php _e('Session-Details:', 'dnd-helper'); ?></h4>
                            <ul>
                                <?php foreach ($act['sessionDetails'] as $session): ?>
                                    <li>
                                        <?php if (!empty($session['date']) && $session['date'] !== 'YYYY-MM-DD'): ?>
                                            <strong><?php
                                                try { $date_obj = new DateTime($session['date']); echo esc_html($date_obj->format(get_option('date_format', 'Y-m-d'))); }
                                                catch (Exception $e) { echo esc_html($session['date']);}
                                            ?>:</strong>
                                        <?php endif; ?>
                                        <?php echo nl2br(esc_html($session['summaryOfEvents'] ?? 'Keine Details.')); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p><?php _e('Keine Akte für diese Kampagne erfasst.', 'dnd-helper'); ?></p>
    <?php endif; ?>
</div>