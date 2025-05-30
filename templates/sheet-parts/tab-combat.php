<!-- == Tab: Kampf == -->
                <div class="tab-section columns-3"> <!-- Kampf-Hauptwerte -->
                    <div class="combat-box ac">
                        <label><?php _e('RK', 'dnd-helper'); ?></label>
                        <span class="value"><?php echo esc_html($data['combat']['armorClass']['value'] ?? '?'); ?></span>
                        <?php if (!empty($data['combat']['armorClass']['calculation'])): ?>
                            <small><?php echo esc_html($data['combat']['armorClass']['calculation']); ?></small>
                        <?php endif; ?>
                    </div>
                     <div class="combat-box initiative">
                        <label><?php _e('Initiative', 'dnd-helper'); ?></label>
                        <span class="value"><?php echo (($data['combat']['initiative'] ?? 0) >= 0 ? '+' : '') . esc_html($data['combat']['initiative'] ?? 0); ?></span>
                    </div>
                     <div class="combat-box speed">
                        <label><?php _e('Bewegung', 'dnd-helper'); ?></label>
                        <span class="value"><?php echo esc_html($data['combat']['speed']['walk'] ?? '?'); ?> ft</span>
                        <?php // TODO: Andere Speed-Typen anzeigen, falls vorhanden ?>
                    </div>
                </div>
                 <div class="tab-section columns-2"> <!-- HP, Hit Dice, Death Saves -->
                    <div class="col">
                         <div class="hitpoints">
							 <h4><?php _e('Trefferpunkte', 'dnd-helper'); ?></h4>
							 <?php
								// Lese die Werte primär aus den separaten Meta-Feldern,
								// Fallback auf JSON, wenn Meta-Feld nicht existiert (z.B. vor erstem Speichern)
								$hp_current_meta = get_post_meta($character_post_id, '_dnd_hp_current', true);
								$hp_max_meta = get_post_meta($character_post_id, '_dnd_hp_max', true);
								$hp_temp_meta = get_post_meta($character_post_id, '_dnd_hp_temporary', true);

								// Verwende Meta-Wert, wenn er numerisch ist, sonst Fallback auf JSON-Wert
								$hp_current = is_numeric($hp_current_meta) ? $hp_current_meta : $get_val('combat.hitPoints.current', '?');
								$hp_max = is_numeric($hp_max_meta) ? $hp_max_meta : $get_val('combat.hitPoints.max', '?');
								$hp_temp = is_numeric($hp_temp_meta) ? $hp_temp_meta : $get_val('combat.hitPoints.temporary', '0');
							 ?>
							 <p><?php _e('Maximal:', 'dnd-helper'); ?> <span class="hp-max"><?php echo esc_html($hp_max); ?></span></p>
							 <p>
								 <?php _e('Aktuell:', 'dnd-helper'); ?>
								 <span class="hp-current dnd-editable-field"
									   data-meta-key="_dnd_hp_current"
									   data-character-id="<?php echo esc_attr($character_post_id); ?>"
									   data-field-type="number"
									   title="<?php esc_attr_e('Klicken zum Bearbeiten', 'dnd-helper'); ?>">
									 <?php echo esc_html($hp_current); // Zeigt jetzt den aktuellen Wert an ?>
								 </span>
								 <span class="dnd-edit-spinner" style="display: none;"></span>
							 </p>
							 <p>
								 <?php _e('Temporär:', 'dnd-helper'); ?>
								  <span class="hp-temp dnd-editable-field"
									   data-meta-key="_dnd_hp_temporary"
									   data-character-id="<?php echo esc_attr($character_post_id); ?>"
									   data-field-type="number"
									   title="<?php esc_attr_e('Klicken zum Bearbeiten', 'dnd-helper'); ?>">
									 <?php echo esc_html($hp_temp); // Zeigt jetzt den aktuellen Wert an ?>
								  </span>
								 <span class="dnd-edit-spinner" style="display: none;"></span>
							 </p>
						 </div>
                    </div>
                    <div class="col">
                        <div class="hitdice">
                            <h4><?php _e('Trefferwürfel', 'dnd-helper'); ?></h4>
														 <?php
								// Lese die Werte primär aus den separaten Meta-Feldern,
								// Fallback auf JSON, wenn Meta-Feld nicht existiert (z.B. vor erstem Speichern)
								$hitdice_current_meta = get_post_meta($character_post_id, '_dnd_hitdice_current', true);
								
								// Verwende Meta-Wert, wenn er numerisch ist, sonst Fallback auf JSON-Wert
								$hitdice_current = is_numeric($hitdice_current_meta) ? $hitdice_current_meta : $get_val('combat.hitDice.current', '?');
															 ?>
                             <p><?php _e('Gesamt:', 'dnd-helper'); ?> <?php echo esc_html($data['combat']['hitDice']['total'] ?? '?'); ?></p>
							 <p>
								 <?php _e('Verfügbar:', 'dnd-helper'); ?>
								 <span class="hitdice-current dnd-editable-field"
									   data-meta-key="_dnd_hitdice_current"
									   data-character-id="<?php echo esc_attr($character_post_id); ?>"
									   data-field-type="number"
									   title="<?php esc_attr_e('Klicken zum Bearbeiten', 'dnd-helper'); ?>">
									 <?php echo esc_html($hitdice_current); // Zeigt jetzt den aktuellen Wert an ?>
								 </span>
								 <span class="dnd-edit-spinner" style="display: none;"></span>
							 </p>
                         </div>
						<div class="death-saves">
							<h4><?php _e('Todesschutzwürfe', 'dnd-helper'); ?></h4>
							<?php
								// Lese Werte aus separaten Meta-Feldern (mit Fallback auf JSON)
								$ds_success_meta = get_post_meta($character_post_id, '_dnd_deathsaves_successes', true);
								$ds_failure_meta = get_post_meta($character_post_id, '_dnd_deathsaves_failures', true);

								$successes = is_numeric($ds_success_meta) ? intval($ds_success_meta) : intval($get_val('combat.deathSaves.successes', 0));
								$failures  = is_numeric($ds_failure_meta) ? intval($ds_failure_meta) : intval($get_val('combat.deathSaves.failures', 0));

								// Stelle sicher, dass Werte im gültigen Bereich sind (für die Punktanzeige)
								$successes = max(0, min(3, $successes));
								$failures  = max(0, min(3, $failures));
							?>
							<p>
								<?php _e('Erfolge:', 'dnd-helper'); ?>
								<span class="death-save-group">
									<span class="dnd-editable-field"
										  data-meta-key="_dnd_deathsaves_successes"
										  data-character-id="<?php echo esc_attr($character_post_id); ?>"
										  data-field-type="number"
										  data-min-value="0"
										  data-max-value="3"
										  title="<?php esc_attr_e('Klicken zum Bearbeiten (Erfolge)', 'dnd-helper'); ?>">
										<?php echo esc_html($successes); ?>
									</span>
									 <span class="death-save-dots">
										(<?php echo str_repeat('●', $successes) . str_repeat('○', 3 - $successes); ?>) <?php // Zeige die Punkte visuell daneben ?>
									</span>
									 <span class="dnd-edit-spinner" style="display: none;"></span>
								</span>
							</p>
							 <p>
								<?php _e('Fehlschläge:', 'dnd-helper'); ?>
								<span class="death-save-group">
									 <span class="dnd-editable-field"
										   data-meta-key="_dnd_deathsaves_failures"
										   data-character-id="<?php echo esc_attr($character_post_id); ?>"
										   data-field-type="number"
										   data-min-value="0"
										   data-max-value="3"
										   title="<?php esc_attr_e('Klicken zum Bearbeiten (Fehlschläge)', 'dnd-helper'); ?>">
										 <?php echo esc_html($failures); ?>
									 </span>
									  <span class="death-save-dots">
										 (<?php echo str_repeat('●', $failures) . str_repeat('○', 3 - $failures); ?>) <?php // Zeige die Punkte visuell daneben ?>
									 </span>
									   <span class="dnd-edit-spinner" style="display: none;"></span>
								</span>
							</p>
						</div>
                    </div>
                 </div>