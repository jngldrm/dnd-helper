<!-- == Tab: Aktionen == -->
                 <div class="tab-section attacks-spells"> <!-- Angriffe (wie vorher) -->
                    <h3><?php _e('Angriffe', 'dnd-helper'); ?></h3>
                    <table class="attacks-table">
                        <thead><tr><th>Name</th><th>Bonus</th><th>Schaden/Typ</th><th>Notizen</th></tr></thead>
                        <tbody>
                            <?php foreach($data['attacks'] ?? [] as $attack):
    $attack_name = $attack['name'] ?? '';
    $related_spell_slug = $attack['relatedSpellSlug'] ?? null;
    $attack_display = !empty($related_spell_slug) ? dnd_helper_generate_wikidot_link($related_spell_slug, $attack_name, 'View Spell') : esc_html($attack_name);
?>
<tr>
    <td><?php echo $attack_display; // Bereits escaped durch Helfer oder esc_html ?></td>
    <td><?php echo (($attack['attackBonus'] ?? 0) >= 0 ? '+' : '') . esc_html($attack['attackBonus'] ?? '?'); ?></td>
    <td><?php echo esc_html($attack['damage'] ?? ''); ?> <?php echo esc_html($attack['damageType'] ?? ''); ?></td>
    <td><?php echo esc_html($attack['notes'] ?? ''); ?></td>
</tr>
<?php endforeach; ?>
                        </tbody>
                    </table>
                 </div>
				 
				 <?php
				$limited_uses_json = get_post_meta($character_post_id, '_dnd_limited_uses', true);
				$limited_uses = null;
				if (!empty($limited_uses_json)) {
					$limited_uses = json_decode($limited_uses_json, true);
				}
				if (empty($limited_uses) || json_last_error() !== JSON_ERROR_NONE) {
					$limited_uses = $get_val('limitedUseResources', []);
				}
				?>

				<?php if (!empty($limited_uses) && is_array($limited_uses)): ?>
				<div class="tab-section limited-uses">
					<h3><?php _e('Klassenfähigkeiten', 'dnd-helper'); ?></h3>
					<div class="limited-uses-grid">
						<?php foreach ($limited_uses as $index => $resource):
							$name = esc_html($resource['name'] ?? 'Unbekannte Ressource');
							$source = esc_html($resource['source'] ?? '');
							$max = intval($resource['usesMax'] ?? 0);
							$current = intval($resource['usesCurrent'] ?? 0);
							$recharge = esc_html($resource['recharge'] ?? '');
							$unit = esc_html($resource['unit'] ?? '');
							$notes = esc_html($resource['notes'] ?? '');

							if ($max > 0):
						?>
							<div class="limited-use-item" data-resource-index="<?php echo esc_attr($index); ?>">
								<strong class="resource-name"><?php echo $name; ?></strong>
								<?php if ($source): ?><small class="resource-source">(<?php echo $source; ?>)</small><?php endif; ?>
								<div class="resource-usage">
									<span class="resource-current dnd-editable-field"
										  data-meta-key="_dnd_limited_uses"
										  data-resource-index="<?php echo esc_attr($index); ?>"
										  data-character-id="<?php echo esc_attr($character_post_id); ?>"
										  data-field-type="number"
										  data-max-value="<?php echo esc_attr($max); ?>"
										  data-min-value="0"
										  title="<?php esc_attr_e('Klicken zum Bearbeiten (Verbleibend)', 'dnd-helper'); ?>">
										<?php echo esc_html($current); ?>
									</span>
									/
									<span class="resource-max"><?php echo esc_html($max); ?></span>
									 <?php if ($unit): ?><span class="resource-unit"><?php echo $unit; ?></span><?php endif; ?>
									<span class="dnd-edit-spinner" style="display: none;"></span>
								</div>
								<?php if ($recharge): ?><div class="resource-recharge">Aufladung: <?php echo $recharge; ?></div><?php endif; ?>
								<?php if ($notes): ?><div class="resource-notes"><?php echo nl2br($notes); ?></div><?php endif; ?>
							</div>
						<?php endif; endforeach; ?>
					</div>
				</div>
				<?php endif; ?>
                 <?php if (isset($data['spellcasting']) && !empty($data['spellcasting']['spells']) ): ?>
                 <div class="tab-section spellcasting">
                    <h3><?php _e('Zauberwirken', 'dnd-helper'); ?></h3>
                    <p>
                        <strong><?php _e('Zauberattribut:', 'dnd-helper'); ?></strong> <?php echo esc_html(ucfirst($data['spellcasting']['spellcastingAbility'] ?? '?')); ?> |
                        <strong><?php _e('Rettungswurf-SG:', 'dnd-helper'); ?></strong> <?php echo esc_html($data['spellcasting']['spellSaveDC'] ?? '?'); ?> |
                        <strong><?php _e('Zauberangriffsbonus:', 'dnd-helper'); ?></strong> +<?php echo esc_html($data['spellcasting']['spellAttackBonus'] ?? '?'); ?>
                    </p>

                    <!-- Zauberplätze -->
					<h4><?php _e('Zauberplätze', 'dnd-helper'); ?></h4>
					<div class="spell-slots">
						<?php
						// Lese primär das separate Meta-Feld
						$slots_json = get_post_meta($character_post_id, '_dnd_spell_slots', true);
						$slots_data = null;
						if (!empty($slots_json)) {
							$slots_data = json_decode($slots_json, true);
						}
						// Fallback auf das Haupt-JSON, wenn Meta-Feld leer oder ungültig ist
						if (empty($slots_data) || json_last_error() !== JSON_ERROR_NONE) {
							$slots_data = $get_val('spellcasting.slots', []);
						}
						?>
						<?php foreach ($slots_data as $slot):
							$level = intval($slot['level'] ?? 0);
							$used = intval($slot['used'] ?? 0);
							$total = intval($slot['total'] ?? 0);
							// Nur Slots mit Level > 0 und Total > 0 anzeigen? Oder alle? Hier: Nur Level > 0
							if ($level > 0 && $total > 0):
						?>
							<div class="slot-level" data-level="<?php echo esc_attr($level); ?>">
								<label><?php printf(esc_html__('Grad %d', 'dnd-helper'), $level); ?></label>
								<span class="slot-usage">
									<span class="slot-used dnd-editable-field"
										  data-meta-key="_dnd_spell_slots"
										  data-slot-level="<?php echo esc_attr($level); ?>"
										  data-character-id="<?php echo esc_attr($character_post_id); ?>"
										  data-field-type="number"
										  data-max-value="<?php echo esc_attr($total); ?>"
										  data-min-value="0"
										  title="<?php esc_attr_e('Klicken zum Bearbeiten (Verbraucht)', 'dnd-helper'); ?>">
										<?php echo esc_html($used); ?>
									</span>
									/
									<span class="slot-total"><?php echo esc_html($total); ?></span>
								</span>
								 <span class="dnd-edit-spinner" style="display: none;"></span>
							</div>
						<?php endif; endforeach; ?>
					</div>

                    <!-- Zauberliste als Akkordeon -->
                    <div class="dnd-accordion-container spells-accordion">
                        <?php
                        // Zauber nach Grad gruppieren
                        $spells_by_level = [];
                        foreach ($data['spellcasting']['spells'] ?? [] as $spell) {
                            $level = intval($spell['level'] ?? -1);
                            $spells_by_level[$level][] = $spell;
                        }
                        ksort($spells_by_level); // Nach Grad sortieren
                        ?>
                        <?php foreach ($spells_by_level as $level => $spells): ?>
                            <?php if ($level >= 0): // Ignoriere ungültige Level ?>
                                <div class="dnd-accordion">
                                    <button class="dnd-accordion-header">
                                        <?php echo $level === 0 ? __('Tricks (Grad 0)', 'dnd-helper') : sprintf(__('Zauber Grad %d', 'dnd-helper'), $level); ?>
                                        <span>(<?php echo count($spells); ?>)</span>
                                    </button>
                                    <div class="dnd-accordion-content">
                                        <ul>
                                            <?php foreach($spells as $spell):
    $spell_name = $spell['name'] ?? 'Unbekannter Zauber';
    $spell_slug = $spell['spellSlug'] ?? null;
?>
<li>
    <strong><?php echo dnd_helper_generate_wikidot_link($spell_slug, $spell_name, 'View Spell'); ?></strong>
    <?php if ($level > 0) : ?>
        <em>(<?php echo ($spell['prepared'] ?? false) ? __('Vorbereitet', 'dnd-helper') : __('Nicht vorbereitet', 'dnd-helper'); ?>)</em>
    <?php endif; ?>
    <?php if (!empty($spell['source'])): ?>
         <small>[<?php echo esc_html($spell['source']); ?>]</small>
    <?php endif; ?>
    <?php if (!empty($spell['description'])): ?>
        <p><?php echo esc_html($spell['description']); ?></p>
    <?php endif; ?>
</li>
<?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div> <!-- /spells-accordion -->
                 </div>
                <?php endif; ?>