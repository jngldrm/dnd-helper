<?php
/**
 * Template für den Gegenstände-Tab.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$items = $get_val('items', []);
?>
<div class="tab-section">
    <h3><?php _e('Wichtige Gegenstände der Gruppe', 'dnd-helper'); ?></h3>
    <?php if (!empty($items) && is_array($items)): ?>
        <ul class="dnd-item-list">
            <?php foreach ($items as $item): ?>
                <li>
                    <strong><?php echo esc_html($item['name'] ?? 'Unbekannt'); ?></strong>
                    <?php if (isset($item['quantity']) && $item['quantity'] > 1): ?>
                        (<?php echo esc_html($item['quantity']); ?>)
                    <?php endif; ?>
                    <?php if (!empty($item['possessedBy'])): ?>
                        <small>- <?php _e('Im Besitz von:', 'dnd-helper'); ?> <?php echo esc_html($item['possessedBy']); ?></small>
                    <?php endif; ?>

                    <?php if (!empty($item['description'])): ?>
                        <p class="item-description"><?php echo nl2br(esc_html($item['description'])); ?></p>
                    <?php endif; ?>
                    <p class="item-meta">
                        <?php if (!empty($item['discoveredInAct'])): ?>
                            <?php printf(esc_html__('Entdeckt in Akt %s', 'dnd-helper'), esc_html($item['discoveredInAct'])); ?>
                        <?php endif; ?>
                        <?php if (!empty($item['discoveredDate']) && $item['discoveredDate'] !== 'Unbekannt'): ?>
                            (<?php
                                try { $date_obj = new DateTime($item['discoveredDate']); echo esc_html($date_obj->format(get_option('date_format', 'Y-m-d'))); }
                                catch (Exception $e) { echo esc_html($item['discoveredDate']);}
                            ?>)
                        <?php endif; ?>
                    </p>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p><?php _e('Keine besonderen Gegenstände für diese Kampagne erfasst.', 'dnd-helper'); ?></p>
    <?php endif; ?>
</div>