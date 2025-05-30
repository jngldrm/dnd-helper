jQuery(document).ready(function ($) {

    // Funktion zum Initialisieren aller Interaktionen innerhalb eines Containers
    function initializeSheetInteraction(containerSelector) {
        const $container = $(containerSelector); // Container für den Geltungsbereich

        if ($container.length === 0) {
            console.error("InitializeSheetInteraction Error: Container with selector '" + containerSelector + "' not found!");
            return;
        }
        console.log("InitializeSheetInteraction: Initializing for container ->", containerSelector);

        // --- Tab-Logik (Delegiert an $container) ---
        $container.off('click.dndTabs').on('click.dndTabs', '.dnd-tabs-nav .dnd-tab-link', function (e) {
            e.preventDefault();
            const $this = $(this);
            const targetTabId = $this.attr('href');
            const $sheetOrSummary = $this.closest('.dnd-character-sheet, .dnd-campaign-summary');

            $sheetOrSummary.find('.dnd-tabs-nav .dnd-tab-link').removeClass('active');
            $sheetOrSummary.find('.dnd-tabs-content .dnd-tab-pane').removeClass('active');

            $this.addClass('active');
            $(targetTabId, $sheetOrSummary).addClass('active');
        });

        // --- Akkordeon-Logik (Delegiert an $container) ---
        $container.off('click.dndAccordion').on('click.dndAccordion', '.dnd-accordion-header', function () {
            const $header = $(this);
            const $content = $header.next('.dnd-accordion-content');
            $header.toggleClass('active');
            $content.slideToggle(300);
        });

        // --- Inline Edit Start (Klick auf Feld) (Delegiert an $container) ---
         $container.off('click.dndInlineEdit').on('click.dndInlineEdit', '.dnd-editable-field', function(e) {
             e.stopPropagation();
             if (!$(this).hasClass('editing') && $(this).find('.dnd-inline-input').length === 0) {
                 switchToEditMode($(this));
             }
         });

        // --- Inline Edit Input Events (NUR Keyup für Enter/Escape, delegiert an $container) ---
        $container.off('keyup.dndInlineInput').on('keyup.dndInlineInput', '.dnd-inline-input', function(e) {
            const $input = $(this);
            const $element = $input.parent();
            if (!$element.hasClass('editing')) return;

            if (e.key === 'Enter') {
                console.log("Event triggered: keyup Enter for element:", $element);
                e.preventDefault();
                saveInlineEdit($element, $input);
            } else if (e.key === 'Escape') {
                 console.log("Event triggered: keyup Escape for element:", $element);
                 switchToViewMode($element, $element.data('original-value'));
            }
        });

        // Klick auf Input stoppt Propagation (delegiert an $container)
        $container.off('click.dndInlineInputStopProp').on('click.dndInlineInputStopProp', '.dnd-inline-input', function(e){
            e.stopPropagation();
        });

    } // --- Ende initializeSheetInteraction ---


    // --- Globaler Focusout-Listener (nur einmal für das Dokument registrieren) ---
    // Dieser wird NICHT innerhalb von initializeSheetInteraction registriert, da er global sein soll.
    $(document).off('focusout.dndGlobalInlineEdit').on('focusout.dndGlobalInlineEdit', '.dnd-inline-input', function(e) {
         const $input = $(this);
         // Finde das Elternelement, das die .editing Klasse haben sollte.
         // Wichtig: Suche nur nach .editing innerhalb eines .dnd-character-sheet oder .dnd-campaign-summary,
         // um Konflikte mit anderen Inputs auf der Seite zu vermeiden, FALLS initializeSheetInteraction
         // nicht korrekt für ALLE relevanten Container gelaufen ist.
         // Besser noch: Prüfe, ob der Input *innerhalb* eines Containers ist, für den initializeSheetInteraction gelaufen ist.
         // Fürs Erste: Wir nehmen an, der $input.parent() ist der richtige.
         const $element = $input.parent('.dnd-editable-field.editing');

         if ($element.length > 0) { // Nur wenn das Elternelement das bearbeitete Feld ist
             console.log("Event triggered: global focusout for element:", $element);
              setTimeout(function() {
                 if ($input.closest('body').length > 0 && $element.hasClass('editing')) {
                      console.log("Global Focusout: Calling saveInlineEdit after timeout.");
                      saveInlineEdit($element, $input);
                 } else {
                      console.log("Global Focusout: Element no longer editing or removed, skipping save.");
                 }
              }, 150);
         } else {
            // console.log("Global Focusout ignored: Input's parent is not .dnd-editable-field.editing");
         }
    });


    // --- Charakter-Auswahl ---
    const $selector = $('#dnd-character-selector');
    const $displayArea = $('#dnd-character-sheet-display');
    const $loader = $('#dnd-sheet-loader');

    if ($selector.length > 0) {
        $selector.on('change', function() {
            const selectedId = $(this).val();
            if (!selectedId) return;
            $loader.show();
            $displayArea.html('');

            $.ajax({
                url: dndSheetData.ajaxUrl, type: 'POST',
                data: { action: 'dnd_get_character_sheet_html', _ajax_nonce: dndSheetData.loadSheetNonce, character_id: selectedId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $displayArea.html(response.data.html);
                        initializeSheetInteraction('#dnd-character-sheet-display');
                    } else { $displayArea.html('<p class="dnd-error">' + (response.data.message || 'Fehler beim Laden.') + '</p>'); }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Load Sheet Error:", textStatus, errorThrown, jqXHR.responseText);
                    $displayArea.html('<p class="dnd-error">Kommunikationsfehler.</p>');
                },
                complete: function() { $loader.hide(); }
            });
        });
    }

    // --- Inline Edit Hilfsfunktionen (switchToEditMode, saveInlineEdit, switchToViewMode) ---
    // Diese bleiben unverändert gegenüber dem letzten funktionierenden Stand
    function switchToEditMode($element) {
        if ($element.hasClass('editing')) return;
        $element.addClass('editing');
        const currentValue = $element.text().trim();
        const fieldType = $element.data('field-type') || 'number';
        const minValue = $element.data('min-value');
        const maxValue = $element.data('max-value');
        $element.data('original-value', currentValue).html('');
        const $input = $('<input>')
            .attr('type', fieldType)
            .addClass('dnd-inline-input')
            .val(currentValue)
            .css({ 'width': '50px', 'text-align': 'center', 'padding': '2px' });
        if (minValue !== undefined) { $input.attr('min', minValue); }
        if (maxValue !== undefined) { $input.attr('max', maxValue); }
        $input.appendTo($element).focus().select();
    }

    function saveInlineEdit($element, $input) {
        console.log("saveInlineEdit called for element:", $element);
        if (!$element.hasClass('editing')) {
             console.log("saveInlineEdit aborted: Element not in editing mode.");
             return;
        }
        // ... (Rest der saveInlineEdit Funktion wie vorher)
        const newValue = $input.val();
        const originalValue = $element.data('original-value');
        const metaKey = $element.data('meta-key');
        const charId = $element.data('character-id');
        const slotLevel = $element.data('slot-level');
        const resourceIndex = $element.data('resource-index');
        const minValue = $input.attr('min') !== undefined ? parseInt($input.attr('min')) : null;
        const maxValue = $input.attr('max') !== undefined ? parseInt($input.attr('max')) : null;
        const $spinner = $element.closest('.slot-level, p, .limited-use-item .resource-usage').find('.dnd-edit-spinner'); // Finde Spinner besser

        let validatedValue = parseInt(newValue);
        if (isNaN(validatedValue)) { alert('Bitte gib eine Zahl ein.'); switchToViewMode($element, originalValue); return; }
        if (minValue !== null && validatedValue < minValue) { validatedValue = minValue; }
        if (maxValue !== null && validatedValue > maxValue) { validatedValue = maxValue; }
        if (validatedValue == originalValue) { switchToViewMode($element, originalValue); return; }

        $spinner.show();
        $input.prop('disabled', true);

        let ajaxAction = ''; let ajaxNonce = ''; let ajaxData = {};
        if (metaKey === '_dnd_limited_uses' && resourceIndex !== undefined) {
            ajaxAction = 'dnd_update_limited_use'; ajaxNonce = dndSheetData.updateResourceNonce;
            ajaxData = { action: ajaxAction, _ajax_nonce: ajaxNonce, character_id: charId, resource_index: resourceIndex, new_current_value: validatedValue };
        } else if (metaKey === '_dnd_spell_slots' && slotLevel !== undefined) {
            ajaxAction = 'dnd_update_spell_slot'; ajaxNonce = dndSheetData.updateSlotNonce;
            ajaxData = { action: ajaxAction, _ajax_nonce: ajaxNonce, character_id: charId, slot_level: slotLevel, new_used_count: validatedValue };
        } else {
             ajaxAction = 'dnd_update_character_field'; ajaxNonce = dndSheetData.updateNonce;
             ajaxData = { action: ajaxAction, _ajax_nonce: ajaxNonce, character_id: charId, meta_key: metaKey, new_value: validatedValue };
        }

        console.log("Starting AJAX request with data:", ajaxData);
        $.ajax({
            url: dndSheetData.ajaxUrl, type: 'POST', data: ajaxData, dataType: 'json',
            success: function(response) { /* ... */ },
            error: function(jqXHR, textStatus, errorThrown) { /* ... */ },
            complete: function() { /* ... */ $spinner.hide(); /* ... */ }
        });
    }

    function switchToViewMode($element, valueToShow) {
        console.log("switchToViewMode called. Value:", valueToShow, "Element:", $element);
        // ... (Rest der switchToViewMode Funktion wie vorher) ...
         if($element && $element.length > 0) {
             const numericValueInput = parseInt(valueToShow);
             if (isNaN(numericValueInput)) {
                 $element.empty().text(valueToShow).removeClass('editing');
                 $element.removeData('original-value');
                 return;
             }
             $element.empty().text(numericValueInput).removeClass('editing');
             $element.removeData('original-value');

             const $dotsSpan = $element.siblings('.death-save-dots');
             if ($dotsSpan.length > 0) {
                 const metaKey = $element.data('meta-key');
                 if (metaKey === '_dnd_deathsaves_successes' || metaKey === '_dnd_deathsaves_failures') {
                     const totalDots = 3;
                     const numericValue = Math.max(0, Math.min(totalDots, numericValueInput));
                     const filledDots = '●'.repeat(numericValue);
                     const emptyDots = '○'.repeat(totalDots - numericValue);
                     $dotsSpan.html('(' + filledDots + emptyDots + ')');
                 }
             }
         } else {
             console.warn("switchToViewMode called on non-existent element.");
         }
    }


    // --- Initialer Aufruf für alle relevanten Container ---
    if ($('#dnd-character-sheet-display').length) {
        initializeSheetInteraction('#dnd-character-sheet-display');
    }
    $('.dnd-campaign-summary.enhanced').each(function() {
        const campaignContainerId = $(this).attr('id');
        if (campaignContainerId) {
            initializeSheetInteraction('#' + campaignContainerId);
        } else {
            console.warn("Campaign summary container found without an ID...");
        }
    });

}); // --- Ende jQuery(document).ready ---