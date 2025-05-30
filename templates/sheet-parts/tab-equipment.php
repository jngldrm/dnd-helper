<!-- == Tab: Ausrüstung == -->
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
