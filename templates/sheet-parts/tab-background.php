<!-- == Tab: Hintergrund == -->
                <div class="tab-section columns-2">
                    <div class="col personality">
                        <h3><?php _e('Persönlichkeit', 'dnd-helper'); ?></h3>
                        <?php if(isset($data['personality'])): $p = $data['personality']; ?>
                            <p><strong><?php _e('Züge:', 'dnd-helper'); ?></strong><br><?php echo nl2br(esc_html($p['traits'] ?? '')); ?></p>
                            <p><strong><?php _e('Ideale:', 'dnd-helper'); ?></strong><br><?php echo nl2br(esc_html($p['ideals'] ?? '')); ?></p>
                            <p><strong><?php _e('Bindungen:', 'dnd-helper'); ?></strong><br><?php echo nl2br(esc_html($p['bonds'] ?? '')); ?></p>
                            <p><strong><?php _e('Makel:', 'dnd-helper'); ?></strong><br><?php echo nl2br(esc_html($p['flaws'] ?? '')); ?></p>
                        <?php endif; ?>
                    </div>
                     <div class="col appearance">
                        <h3><?php _e('Aussehen', 'dnd-helper'); ?></h3>
                         <?php if(isset($data['appearance'])): $a = $data['appearance']; ?>
                            <p><strong><?php _e('Alter:', 'dnd-helper'); ?></strong> <?php echo esc_html($a['age'] ?? ''); ?></p>
                            <p><strong><?php _e('Größe:', 'dnd-helper'); ?></strong> <?php echo esc_html($a['height'] ?? ''); ?></p>
                            <p><strong><?php _e('Gewicht:', 'dnd-helper'); ?></strong> <?php echo esc_html($a['weight'] ?? ''); ?></p>
                            <p><strong><?php _e('Augen:', 'dnd-helper'); ?></strong> <?php echo esc_html($a['eyes'] ?? ''); ?></p>
                            <p><strong><?php _e('Haut:', 'dnd-helper'); ?></strong> <?php echo esc_html($a['skin'] ?? ''); ?></p>
                            <p><strong><?php _e('Haare:', 'dnd-helper'); ?></strong> <?php echo esc_html($a['hair'] ?? ''); ?></p>
                            <?php if (!empty($a['description'])): ?>
                            <p><strong><?php _e('Beschreibung:', 'dnd-helper'); ?></strong><br><?php echo nl2br(esc_html($a['description'])); ?></p>
                             <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                 <div class="tab-section backstory-allies">
                    <h3><?php _e('Hintergrundgeschichte & Verbündete', 'dnd-helper'); ?></h3>
                     <?php if (!empty($data['backstory'])): ?>
                    <p><strong><?php _e('Geschichte:', 'dnd-helper'); ?></strong><br><?php echo nl2br(esc_html($data['backstory'])); ?></p>
                     <?php endif; ?>
                     <?php if (!empty($data['alliesAndOrganizations'])): ?>
                     <p><strong><?php _e('Verbündete & Organisationen:', 'dnd-helper'); ?></strong><br><?php echo nl2br(esc_html($data['alliesAndOrganizations'])); ?></p>
                     <?php endif; ?>
                 </div>
                 <div class="tab-section notes">
                    <h3><?php _e('Notizen', 'dnd-helper'); ?></h3>
                     <ul>
                     <?php foreach ($data['notes'] ?? [] as $note): ?>
                         <li><?php echo esc_html($note); ?></li>
                     <?php endforeach; ?>
                     </ul>
                 </div>