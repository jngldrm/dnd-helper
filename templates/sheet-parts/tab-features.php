            <!-- == Tab: Merkmale == -->

     <!-- NEU: Abschnitt für Feats -->
     <?php if (isset($data['feats']) && !empty($data['feats'])): ?>
     <div class="tab-section feats-list">
        <h3><?php _e('Talente (Feats)', 'dnd-helper'); ?></h3>
         <div class="dnd-accordion-container feats-accordion">
             <div class="dnd-accordion">
                  <button class="dnd-accordion-header">
                     <?php _e('Talente anzeigen/verbergen', 'dnd-helper'); ?>
                     <span>(<?php echo count($data['feats']); ?>)</span>
                  </button>
                  <div class="dnd-accordion-content">
                     <ul>
                     <?php foreach ($data['feats'] as $feat):
                         $feat_name = $feat['name'] ?? '';
                         $feat_slug = $feat['featSlug'] ?? null;
                     ?>
                         <li>
                             <strong><?php echo dnd_helper_generate_wikidot_link($feat_slug, $feat_name, 'View Feat'); ?></strong>
                             <?php if (!empty($feat['source'])): ?>
                                 <small>(<?php echo esc_html($feat['source']); ?>)</small>
                             <?php endif; ?>
                             <?php if (!empty($feat['description'])): ?>
                                 <p><?php echo nl2br(esc_html($feat['description'])); ?></p>
                             <?php endif; ?>
                         </li>
                     <?php endforeach; ?>
                     </ul>
                 </div>
             </div>
         </div> <!-- /feats-accordion -->
     </div>
     <?php endif; ?>


     <!-- Bestehender Abschnitt für Features & Traits -->
     <div class="tab-section features-traits">
        <h3><?php _e('Klassen-/Rassen-/Hintergrundmerkmale', 'dnd-helper'); ?></h3>
         <?php $features = $data['featuresAndTraits'] ?? []; ?>
        <div class="dnd-accordion-container features-accordion">
            <div class="dnd-accordion">
                 <button class="dnd-accordion-header">
                    <?php _e('Merkmale anzeigen/verbergen', 'dnd-helper'); ?>
                    <span>(<?php echo count($features); ?>)</span>
                 </button>
                 <div class="dnd-accordion-content">
                    <ul>
                    <?php foreach ($features as $feature):
                        $feature_name = $feature['name'] ?? '';
                        $feature_slug = $feature['featureSlug'] ?? null; // Optionaler Slug für Features
                    ?>
                        <li>
                            <strong><?php echo dnd_helper_generate_wikidot_link($feature_slug, $feature_name, 'View Feature'); ?></strong>
                            <?php if (!empty($feature['source'])): ?>
                                <small>(<?php echo esc_html($feature['source']); ?>)</small>
                            <?php endif; ?>
                            <?php if (isset($feature['uses']) && !empty($feature['uses']['max'])): ?>
                                <em>[<?php _e('Nutzungen:', 'dnd-helper'); ?> <?php echo esc_html($feature['uses']['current'] ?? 0); ?>/<?php echo esc_html($feature['uses']['max']); ?> (<?php echo esc_html($feature['uses']['recharge'] ?? ''); ?>)]</em>
                            <?php endif; ?>
                            <?php if (!empty($feature['description'])): ?>
                                <p><?php echo nl2br(esc_html($feature['description'])); ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div> <!-- /features-accordion -->
    </div>