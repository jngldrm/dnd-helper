jQuery(document).ready(function ($) {

    // --- Tab-Logik ---
    $('.dnd-character-sheet .dnd-tabs-nav').on('click', '.dnd-tab-link', function (e) {
        e.preventDefault(); // Standard-Linkverhalten verhindern

        const $this = $(this);
        const targetTab = $this.data('tab'); // Hole das data-tab Attribut (z.B. "combat")
        const $sheet = $this.closest('.dnd-character-sheet'); // Finde das Elternelement

        // 1. Alle Links und Panes im aktuellen Sheet deaktivieren
        $sheet.find('.dnd-tabs-nav .dnd-tab-link').removeClass('active');
        $sheet.find('.dnd-tabs-content .dnd-tab-pane').removeClass('active');

        // 2. Angeklickten Link und zugehörigen Pane aktivieren
        $this.addClass('active');
        $sheet.find('#tab-' + targetTab).addClass('active');
    });

    // --- Akkordeon-Logik ---
    $('.dnd-character-sheet .dnd-accordion-container').on('click', '.dnd-accordion-header', function () {
        const $header = $(this);
        const $content = $header.next('.dnd-accordion-content');

        // Optional: Andere Akkordeons im selben Container schließen
        // $header.closest('.dnd-accordion-container').find('.dnd-accordion-content').not($content).slideUp();
        // $header.closest('.dnd-accordion-container').find('.dnd-accordion-header').not($header).removeClass('active');

        // Aktuelles Akkordeon umschalten
        $header.toggleClass('active');
        $content.slideToggle(300); // Animation (300ms)
    });
	
	const sheetContainer = $('.dnd-character-sheet.enhanced');

    function switchToEditMode($element) {
        if ($element.hasClass('editing')) return; // Verhindern, dass man erneut klickt
        $element.addClass('editing');

        const currentValue = $element.text().trim();
        // const fieldType = $element.data('field-type') || 'number'; // Typ aus data Attribut holen

        // Verstecke den Text-Inhalt und speichere ihn
        $element.data('original-value', currentValue).html(''); // Inhalt leeren

        const $input = $('<input>')
            .attr('type', 'number') // Für HP etc., ggf. dynamisch machen
            .addClass('dnd-inline-input')
            .val(currentValue)
            .css({ 'width': '50px', 'text-align': 'center', 'padding': '2px' })
            .appendTo($element);

        $input.focus().select();

        // Event Listener für Enter, Escape und Blur
        $input.on('keyup blur', function(e) {
            if (e.type === 'blur' || (e.type === 'keyup' && e.key === 'Enter')) { // Bei Enter Key oder Blur
                e.preventDefault();
                saveInlineEdit($element, $input);
            } else if (e.type === 'keyup' && e.key === 'Escape') { // Bei Escape
                switchToViewMode($element, $element.data('original-value')); // Abbrechen
            }
        }).on('click', function(e){ e.stopPropagation(); }); // Klick auf Input soll nicht als Klick "daneben" gelten
    }

    function saveInlineEdit($element, $input) {
        if (!$element.hasClass('editing')) return; // Nur speichern, wenn im Edit-Modus

        const newValue = $input.val();
        const originalValue = $element.data('original-value');
        const metaKey = $element.data('meta-key'); // Meta Key aus data Attribut
        const charId = $element.data('character-id');
        const $spinner = $element.siblings('.dnd-edit-spinner');

        if (newValue == originalValue) { // Typunsicherer Vergleich ok, da Input immer String ist
            switchToViewMode($element, originalValue);
            return;
        }

        $spinner.show();
        $input.prop('disabled', true);

        // AJAX Request - Verwende dndSheetData von wp_localize_script
        $.ajax({
            url: dndSheetData.ajaxUrl, // Korrekte Variable verwenden
            type: 'POST',
            data: {
                action: 'dnd_update_character_field',
                _ajax_nonce: dndSheetData.updateNonce, // Korrekte Nonce verwenden
                character_id: charId,
                meta_key: metaKey, // Meta Key senden
                new_value: newValue
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    switchToViewMode($element, response.data.new_value);
                    $element.css('background-color', '#d4edda').animate({backgroundColor: 'transparent'}, 1000);
                } else {
                    alert('Fehler: ' + (response.data.message || 'Unbekannter Fehler.'));
                    switchToViewMode($element, originalValue);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                 console.error("Inline Edit Save Error:", textStatus, errorThrown, jqXHR.responseText);
                 alert('Fehler: Verbindung fehlgeschlagen.');
                 switchToViewMode($element, originalValue);
            },
            complete: function() {
                $spinner.hide();
                $element.removeClass('editing'); // Bearbeitungsmarkierung entfernen
                // Input wird in switchToViewMode entfernt
            }
        });
    }

    function switchToViewMode($element, valueToShow) {
         $element.empty().text(valueToShow).removeClass('editing');
         $element.removeData('original-value');
    }

    // Event Listener (Delegation)
    sheetContainer.on('click', '.dnd-editable-field', function(e) {
        e.stopPropagation();
         // Nur starten, wenn nicht schon ein Input da ist
         if ($(this).find('.dnd-inline-input').length === 0) {
             switchToEditMode($(this));
         }
    });

});