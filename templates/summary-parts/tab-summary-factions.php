<?php
/**
 * Template für den Fraktionen-Tab.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$factions = $get_val('factions', []);
?>
<div class="tab-section">
    <h3><?php _e('Wichtige Fraktionen', 'dnd-helper'); ?></h3>
    <?php if (!empty($factions) && is_array($factions)): ?>
        <div class="dnd-accordion-container factions-accordion">
            <?php foreach ($factions as $faction): ?>
                <div class="dnd-accordion">
                    <button class="dnd-accordion-header">
                        <?php echo esc_html($faction['name'] ?? __('Unbenannte Fraktion', 'dnd-helper')); ?>
                        <?php if(!empty($faction['alignment'])): ?>
                            <small>(<?php echo esc_html($faction['alignment']); ?>)</small>
                        <?php endif; ?>
                    </button>
                    <div class="dnd-accordion-content">
                        <?php if (!empty($faction['description'])): ?>
                            <p><strong><?php _e('Beschreibung:', 'dnd-helper'); ?></strong> <?php echo nl2br(esc_html($faction['description'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($faction['keyMembers']) && is_array($faction['keyMembers'])): ?>
                            <p><strong><?php _e('Schlüsselmitglieder:', 'dnd-helper'); ?></strong> <?php echo esc_html(implode(', ', $faction['keyMembers'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($faction['notes'])): ?>
                            <p><strong><?php _e('Notizen:', 'dnd-helper'); ?></strong> <?php echo nl2br(esc_html($faction['notes'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p><?php _e('Keine Fraktionen für diese Kampagne erfasst.', 'dnd-helper'); ?></p>
    <?php endif; ?>
</div>