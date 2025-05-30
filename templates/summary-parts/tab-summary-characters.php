<?php
/**
 * Template für den Spielercharaktere-Tab.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$player_characters = $get_val('playerCharacters', []);
?>
<div class="tab-section">
    <h3><?php _e('Spielercharaktere', 'dnd-helper'); ?></h3>
    <?php if (!empty($player_characters) && is_array($player_characters)): ?>
        <ul class="dnd-player-character-list">
            <?php foreach ($player_characters as $pc): ?>
                <li>
                    <strong><?php echo esc_html($pc['name'] ?? 'Unbekannt'); ?></strong>
                    (<?php _e('Spieler:', 'dnd-helper'); ?> <?php echo esc_html($pc['player'] ?? 'Unbekannt'); ?>)
                    - <?php _e('Klasse:', 'dnd-helper'); ?> <?php echo esc_html($pc['class'] ?? 'Unbekannt'); ?>
                    - <?php _e('Status:', 'dnd-helper'); ?> <?php echo esc_html($pc['status'] ?? 'Unbekannt'); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p><?php _e('Keine Spielercharaktere für diese Kampagne erfasst.', 'dnd-helper'); ?></p>
    <?php endif; ?>
</div>

<?php if (isset($data['meta'])): $meta = $data['meta']; ?>
<div class="tab-section">
    <h4><?php _e('Meta-Informationen zur Gruppe', 'dnd-helper'); ?></h4>
    <?php if (isset($meta['currentLevel'])): ?>
        <p><strong><?php _e('Aktuelles Gruppenlevel:', 'dnd-helper'); ?></strong> <?php echo esc_html($meta['currentLevel']); ?></p>
    <?php endif; ?>
    <?php if (!empty($meta['levelUpHistory']) && is_array($meta['levelUpHistory'])): ?>
        <p><strong><?php _e('Level-Up Historie:', 'dnd-helper'); ?></strong></p>
        <ul>
            <?php foreach ($meta['levelUpHistory'] as $level_up): ?>
                <li>
                    <?php printf(
                        esc_html__('Level %1$s erreicht am %2$s (Akt %3$s)', 'dnd-helper'),
                        esc_html($level_up['level'] ?? '?'),
                        esc_html($level_up['date'] ?? '?'),
                        esc_html($level_up['act'] ?? '?')
                    ); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?php endif; ?>