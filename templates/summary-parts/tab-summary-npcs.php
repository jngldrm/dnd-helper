<?php
/**
 * Template für den NPCs-Tab.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$npcs = $get_val('npcs', []);
?>
<div class="tab-section">
    <h3><?php _e('Wichtige NSCs', 'dnd-helper'); ?></h3>
    <?php if (!empty($npcs)): ?>
        <ul class="dnd-npc-list">
            <?php foreach ($npcs as $npc): ?>
                <li>
                    <strong><?php echo esc_html($npc['name'] ?? 'Unbekannt'); ?></strong>
                    <?php if(!empty($npc['faction'])): ?>
                        <small>(<?php echo esc_html($npc['faction']); ?>)</small>
                    <?php endif; ?>
                     <?php if(!empty($npc['status'])): ?>
                        <em>- <?php echo esc_html($npc['status']); ?></em>
                    <?php endif; ?>
                    <?php if(!empty($npc['description'])): ?>
                        <p class="npc-description"><?php echo nl2br(esc_html($npc['description'])); ?></p>
                    <?php endif; ?>
                     <?php if(!empty($npc['firstSeen'])): ?>
                        <p class="npc-meta"><?php _e('Erstmals gesehen:', 'dnd-helper'); ?> <?php echo esc_html($npc['firstSeen']); ?></p>
                    <?php endif; ?>
                     <?php if(!empty($npc['notes'])): ?>
                        <p class="npc-meta"><em><?php _e('Notizen:', 'dnd-helper'); ?> <?php echo nl2br(esc_html($npc['notes'])); ?></em></p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p><?php _e('Keine NPCs für diese Kampagne erfasst.', 'dnd-helper'); ?></p>
    <?php endif; ?>
</div>