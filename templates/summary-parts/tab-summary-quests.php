<?php
/**
 * Template für den Quests-Tab der Kampagnenzusammenfassung.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$open_quests = $get_val('questLog.open', []);
$completed_quests = $get_val('questLog.completed', []);
?>

<div class="tab-section">
    <h3><?php _e('Offene Quests', 'dnd-helper'); ?></h3>
    <?php if (!empty($open_quests) && is_array($open_quests)): ?>
        <div class="dnd-accordion-container quests-accordion open-quests">
            <?php foreach ($open_quests as $index => $quest): ?>
                <div class="dnd-accordion">
                    <button class="dnd-accordion-header">
                        <?php echo esc_html($quest['title'] ?? __('Unbenannte Quest', 'dnd-helper')); ?>
                        <small>(<?php echo esc_html($quest['status'] ?? 'Status unbekannt'); ?>)</small>
                    </button>
                    <div class="dnd-accordion-content">
                        <?php if (!empty($quest['description'])): ?>
                            <p><strong><?php _e('Beschreibung:', 'dnd-helper'); ?></strong> <?php echo nl2br(esc_html($quest['description'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($quest['origin'])): ?>
                            <p><strong><?php _e('Ursprung:', 'dnd-helper'); ?></strong> <?php echo esc_html($quest['origin']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($quest['relevant_npcs']) && is_array($quest['relevant_npcs'])): ?>
                            <p><strong><?php _e('Relevante NPCs:', 'dnd-helper'); ?></strong> <?php echo esc_html(implode(', ', $quest['relevant_npcs'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($quest['relevant_locations']) && is_array($quest['relevant_locations'])): ?>
                            <p><strong><?php _e('Relevante Orte:', 'dnd-helper'); ?></strong> <?php echo esc_html(implode(', ', $quest['relevant_locations'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($quest['notes'])): ?>
                            <p><strong><?php _e('Notizen:', 'dnd-helper'); ?></strong> <?php echo nl2br(esc_html($quest['notes'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p><?php _e('Keine offenen Quests erfasst.', 'dnd-helper'); ?></p>
    <?php endif; ?>
</div>

<div class="tab-section">
    <h3><?php _e('Abgeschlossene Quests', 'dnd-helper'); ?></h3>
    <?php if (!empty($completed_quests) && is_array($completed_quests)): ?>
        <div class="dnd-accordion-container quests-accordion completed-quests">
            <?php foreach ($completed_quests as $index => $quest): ?>
                 <div class="dnd-accordion">
                    <button class="dnd-accordion-header">
                        <?php echo esc_html($quest['title'] ?? __('Unbenannte Quest', 'dnd-helper')); ?>
                        <small>(<?php echo esc_html($quest['status'] ?? 'Status unbekannt'); ?>)</small>
                    </button>
                    <div class="dnd-accordion-content">
                        <?php if (!empty($quest['description'])): ?>
                            <p><strong><?php _e('Beschreibung:', 'dnd-helper'); ?></strong> <?php echo nl2br(esc_html($quest['description'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($quest['origin'])): ?>
                            <p><strong><?php _e('Ursprung:', 'dnd-helper'); ?></strong> <?php echo esc_html($quest['origin']); ?></p>
                        <?php endif; ?>
                         <?php if (!empty($quest['relevant_npcs']) && is_array($quest['relevant_npcs'])): ?>
                            <p><strong><?php _e('Relevante NPCs:', 'dnd-helper'); ?></strong> <?php echo esc_html(implode(', ', $quest['relevant_npcs'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($quest['relevant_locations']) && is_array($quest['relevant_locations'])): ?>
                            <p><strong><?php _e('Relevante Orte:', 'dnd-helper'); ?></strong> <?php echo esc_html(implode(', ', $quest['relevant_locations'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($quest['notes'])): ?>
                            <p><strong><?php _e('Notizen:', 'dnd-helper'); ?></strong> <?php echo nl2br(esc_html($quest['notes'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p><?php _e('Keine abgeschlossenen Quests erfasst.', 'dnd-helper'); ?></p>
    <?php endif; ?>
</div>