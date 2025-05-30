<!-- == Tab: Übersicht == -->
                <div class="tab-section columns-2">
                    <div class="col">
                        <div class="ability-scores"> <!-- Attribute (wie vorher) -->
                            <h3><?php _e( 'Attribute', 'dnd-helper' ); ?></h3>
                            <ul>
                            <?php foreach ( $data['abilityScores'] ?? [] as $key => $scoreData ) : ?>
                                <li>
                                    <span class="ability-name"><?php echo esc_html( ucfirst( $key ) ); ?></span>
                                    <span class="ability-score"><?php echo esc_html( $scoreData['score'] ?? '?' ); ?></span>
                                    <span class="ability-modifier">(<?php echo ( ( $scoreData['modifier'] ?? 0 ) >= 0 ? '+' : '' ) . esc_html( $scoreData['modifier'] ?? 0 ); ?>)</span>
                                </li>
                            <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="col">
                         <div class="proficiency-bonus"> <!-- Übungsbonus -->
                            <strong><?php _e('Übungsbonus:', 'dnd-helper'); ?></strong> +<?php echo esc_html($data['proficiencies']['bonus'] ?? 0); ?>
                         </div>
                         <div class="saving-throws"> <!-- Rettungswürfe (wie vorher) -->
                            <h4><?php _e( 'Rettungswürfe', 'dnd-helper' ); ?></h4>
                            <ul>
                           <?php $saving_throw_prof = $data['proficiencies']['savingThrows'] ?? [];
                           foreach ( $data['abilityScores'] ?? [] as $key => $scoreData ) :
                               $is_proficient = in_array( $key, $saving_throw_prof ); $modifier = $scoreData['modifier'] ?? 0;
                               $total_bonus = $is_proficient ? $modifier + ($data['proficiencies']['bonus'] ?? 0) : $modifier; ?>
                            <li>
                                <span class="prof-indicator"><?php echo $is_proficient ? '●' : '○'; ?></span>
                                <span class="save-bonus"><?php echo ( $total_bonus >= 0 ? '+' : '' ) . esc_html( $total_bonus ); ?></span>
                                <span class="save-name"><?php echo esc_html( ucfirst( $key ) ); ?></span>
                            </li>
                           <?php endforeach; ?>
                            </ul>
                        </div>
                         <div class="passive-perception"> <!-- Passive Wahrnehmung -->
                             <strong><?php _e('Passive Wahrnehmung:', 'dnd-helper'); ?></strong> <?php echo esc_html($data['passivePerception'] ?? 10 + ($data['skills']['perception']['modifier'] ?? 0)); ?>
                         </div>
                    </div>
                </div>
                 <div class="tab-section skills"> <!-- Fertigkeiten (wie vorher) -->
                    <h3><?php _e( 'Fertigkeiten', 'dnd-helper' ); ?></h3>
                    <ul>
                       <?php foreach ( $data['skills'] ?? [] as $key => $skillData ) :
                            $total_bonus = $skillData['modifier'] ?? 0; $ability = $skillData['ability'] ?? ''; ?>
                        <li>
                            <span class="prof-indicator"><?php echo ( $skillData['proficient'] ?? false ) ? '●' : '○'; ?></span>
                            <span class="skill-bonus"><?php echo ( $total_bonus >= 0 ? '+' : '' ) . esc_html( $total_bonus ); ?></span>
                            <span class="skill-name"><?php echo esc_html( ucfirst( preg_replace('/(?<!^)([A-Z])/', ' $1', $key) ) ); // CamelCase zu Leerzeichen ?></span>
                            <span class="skill-ability">(<?php echo esc_html( strtoupper( substr( $ability, 0, 3 ) ) ); ?>)</span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>