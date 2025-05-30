jQuery(document).ready(function ($) {
    console.log('D&D Chat JS loaded.', dndChatData); // Debug-Ausgabe

    // Elemente zwischenspeichern
    const chatLog = $('#dnd-chat-log');
    const messageInput = $('#dnd-chat-message-input');
    const sendButton = $('#dnd-chat-send-button');
    const chatStatus = $('#dnd-chat-status');

    let lastMessageTimestamp = 0; // Zeitstempel der letzten erhaltenen Nachricht (oder ID)
    let pollingIntervalId = null;
    let isLoading = false; // Verhindert parallele Lade-/Sende-Anfragen

    // --- Funktion zum Hinzufügen von Nachrichten zum Chat-Log ---
    function addMessageToLog(messageData) {
        // messageData erwartet ein Objekt wie:
        // { id: 1, user_name: 'Spieler', content: 'Hallo!', type: 'message', timestamp: '2023-10-28 10:00:00', roll_details: null, is_own: false }

        // Hier wird die eigentliche Logik zum Erstellen des HTML für die Nachricht eingefügt
        // Zum Beispiel:
        /*
        const messageDiv = $('<p></p>').addClass('chat-' + messageData.type);
        if (messageData.is_own) {
            messageDiv.addClass('own-message');
        }
        const meta = $('<span></span>').addClass('message-meta');
        meta.append($('<strong></strong>').text(messageData.user_name));
        meta.append($('<span class="timestamp"></span>').text(formatTimestamp(messageData.timestamp))); // formatTimestamp Funktion fehlt noch
        messageDiv.append(meta);
        messageDiv.append($('<span></span>').addClass('message-content').text(messageData.content)); // Hier muss man aufpassen wegen HTML-Injection, evtl. besser .text()

        // Wenn es ein Würfelwurf ist, Details anzeigen?
        if (messageData.type === 'roll' && messageData.roll_details) {
           // Tooltip oder detailliertere Ausgabe hinzufügen
        }

        chatLog.append(messageDiv);
        */

        // Scroll zum Ende des Chat-Logs
        // chatLog.scrollTop(chatLog[0].scrollHeight);

        // Zeitstempel der letzten Nachricht merken (wir brauchen eine konsistente Quelle, ID oder Timestamp)
        // lastMessageTimestamp = messageData.timestamp; // Oder messageData.id, abhängig von der Server-Antwort
    }

    // --- Funktion zum Senden einer Nachricht ---
    function sendMessage() {
        const message = messageInput.val().trim();
        if (message === '' || isLoading) {
            if(message === '') chatStatus.text(dndChatData.text.emptyMessage).fadeIn().delay(2000).fadeOut();
            return;
        }

        isLoading = true;
        sendButton.prop('disabled', true).text(dndChatData.text.sending);
        chatStatus.empty(); // Status leeren

    // Kontroll-Log HIER einfügen:
    console.log('Starte AJAX Request mit:', {
         url: dndChatData.ajaxUrl,
         type: 'POST',
         data: {
             action: 'dnd_send_message',
             _ajax_nonce: dndChatData.nonce,
             message: message // Die Variable 'message' sollte hier den Text enthalten
         },
         dataType: 'json'
     });

        // AJAX Call zum Senden aktivieren!
        $.ajax({
            url: dndChatData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dnd_send_message', // PHP AJAX Action Hook
                _ajax_nonce: dndChatData.nonce, // Sicherheits-Nonce
                message: message // Die rohe Nachricht oder der /roll Befehl
            },
            dataType: 'json', // Wir erwarten JSON zurück
            success: function (response) {
                // console.log('Send success:', response); // Debug
                if (response.success) {
                    messageInput.val(''); // Input leeren bei Erfolg
                    // Nachricht wurde gesendet, Polling wird sie bald abholen.
                    // fetchNewMessages(); // Optional: Man könnte hier auch sofort pollen, um Latenz zu verringern
                } else {
                    // Zeige den Fehler aus der Server-Antwort
                    const errorMsg = response.data && response.data.message ? response.data.message : dndChatData.text.error;
                    chatStatus.text(errorMsg).fadeIn(); // Fehler anzeigen, nicht ausblenden
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                // Allgemeiner AJAX-Fehler
                console.error('Send error:', textStatus, errorThrown, jqXHR.responseText); // Mehr Details loggen
                chatStatus.text(dndChatData.text.error + ' (AJAX Error)').fadeIn();
            },
            complete: function () {
                // Wird immer ausgeführt, nach success oder error
                isLoading = false;
                sendButton.prop('disabled', false).text(dndChatData.text.sendMessage);
            }
        });
       // Den temporären Teil entfernen:
       // console.log('AJAX Call zum Senden würde jetzt erfolgen für:', message);
       // setTimeout(function() { ... }, 500);
    }

jQuery(document).ready(function ($) {
    console.log('D&D Chat JS loaded.', dndChatData);

    // Elemente zwischenspeichern
    const chatContainer = $('#dnd-chat-container'); // Container für Existenzprüfung
    const chatLog = $('#dnd-chat-log');
    const messageInput = $('#dnd-chat-message-input');
    const sendButton = $('#dnd-chat-send-button');
    const chatStatus = $('#dnd-chat-status');

    let lastMessageId = 0; // ID der letzten erhaltenen Nachricht
    let pollingIntervalId = null;
    let isLoading = false; // Verhindert parallele Lade-/Sende-Anfragen
    let isScrollingPaused = false; // Für User-Scrolling

    // --- Funktion zum Formatieren des Timestamps (lokale Zeit) ---
    function formatTimestamp(timestampGMT) {
        if (!timestampGMT) return '';
        try {
            const date = new Date(timestampGMT + 'Z'); // 'Z' kennzeichnet UTC/GMT
            // Einfache Formatierung H:MM (oder anpassen nach Bedarf)
            return date.toLocaleTimeString(navigator.language, { hour: '2-digit', minute: '2-digit' });
        } catch (e) {
            console.error("Timestamp format error:", e);
            return '';
        }
    }

    // --- Funktion zum Scrollen zum Ende (wenn nicht pausiert) ---
    function scrollToBottom() {
        if (!isScrollingPaused) {
            // Eine kleine Verzögerung gibt dem Browser Zeit, das Layout zu rendern
            setTimeout(() => {
                 chatLog.scrollTop(chatLog[0].scrollHeight);
            }, 50);
        }
    }

     // --- Funktion zum Hinzufügen von Nachrichten zum Chat-Log ---
    function addMessageToLog(messageData, isPrepending = false) {
        // messageData: { id: ..., user_id: ..., user_name: ..., content: ..., type: ..., timestamp_gmt: ..., roll_details: ..., is_own: ... }

        const messageId = 'dnd-message-' + messageData.id;
        // Verhindern, dass dieselbe Nachricht mehrfach hinzugefügt wird
        if ($('#' + messageId).length > 0) {
            // console.log('Nachricht ' + messageData.id + ' bereits vorhanden.');
            return;
        }

        const messageDiv = $('<div></div>') // Verwende Div statt P für bessere Struktur
                            .addClass('chat-message-wrapper') // Wrapper für Meta + Content
                            .addClass('chat-' + messageData.type)
                            .attr('id', messageId); // Eindeutige ID setzen

        if (messageData.is_own) {
            messageDiv.addClass('own-message');
        }

        // Zeitstempel formatieren
        const formattedTime = formatTimestamp(messageData.timestamp_gmt);

        // --- Erzeuge HTML basierend auf Typ ---
        let messageHTML = '';
        if (messageData.type === 'roll') {
            // Bei Würfen verwenden wir den vorformatierten Inhalt aus der DB
            messageHTML = messageData.content; // Enthält bereits User, "würfelt", Input und Ergebnis
             // Füge Tooltip mit Details hinzu, wenn vorhanden
             if (messageData.roll_details && messageData.roll_details.rolls) {
                const detailsText = `Input: ${messageData.roll_details.input}\nRolls: [${messageData.roll_details.rolls.join(', ')}]\nModifier: ${messageData.roll_details.modifier}\nTotal: ${messageData.roll_details.total}`;
                messageDiv.attr('title', detailsText); // Einfacher HTML-Tooltip
            }

        } else if (messageData.type === 'message') {
             // Bei normalen Nachrichten ersetzen wir den Timestamp-Platzhalter
             // Der Rest (Username, Content) ist bereits im messageData.content
             messageHTML = messageData.content.replace('%%TIMESTAMP%%', formattedTime);
        } else { // z.B. 'system'
            messageHTML = `<span class="message-content">${messageData.content}</span>`; // Einfacher Inhalt
        }


        messageDiv.html(messageHTML); // Füge das generierte HTML ein

        if (isPrepending) {
            chatLog.prepend(messageDiv); // Beim Nachladen älterer Nachrichten oben einfügen
        } else {
            chatLog.append(messageDiv); // Neue Nachrichten unten anhängen
            scrollToBottom(); // Nur bei neuen Nachrichten scrollen
        }

        // ID der letzten *angezeigten* Nachricht merken
        if (messageData.id > lastMessageId) {
            lastMessageId = messageData.id;
        }
    }


    // --- Funktion zum Senden einer Nachricht ---
    // (Der Code hier bleibt wie im vorherigen Schritt, da er ja funktionierte)
    function sendMessage() {
        const message = messageInput.val().trim();
        if (message === '' || isLoading) {
            if(message === '') chatStatus.text(dndChatData.text.emptyMessage).fadeIn().delay(2000).fadeOut();
            return;
        }

        isLoading = true;
        sendButton.prop('disabled', true).text(dndChatData.text.sending);
        chatStatus.empty(); // Status leeren

        $.ajax({
            url: dndChatData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dnd_send_message',
                _ajax_nonce: dndChatData.nonce,
                message: message
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    messageInput.val('');
                    fetchNewMessages(true); // Wichtig: Sofort neue Nachrichten holen nach Senden! (true = force)
                } else {
                    const errorMsg = response.data && response.data.message ? response.data.message : dndChatData.text.error;
                    chatStatus.text(errorMsg).fadeIn();
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('Send error:', textStatus, errorThrown, jqXHR.responseText);
                chatStatus.text(dndChatData.text.error + ' (AJAX Error)').fadeIn();
            },
            complete: function () {
                isLoading = false;
                sendButton.prop('disabled', false).text(dndChatData.text.sendMessage);
            }
        });
    }

    // --- Funktion zum Abrufen neuer Nachrichten ---
    function fetchNewMessages(force = false) {
        // Verhindere Polling, wenn gerade gesendet/geladen wird, außer es wird forciert (nach dem Senden)
        if (isLoading && !force) {
             // console.log('Polling übersprungen, da isLoading=true');
            return;
        }

        // Markiere als ladend (auch für Polling), um parallele Anfragen zu verhindern
        isLoading = true;

        // console.log('Rufe neue Nachrichten ab seit ID:', lastMessageId); // Verwende lastMessageId
        $.ajax({
            url: dndChatData.ajaxUrl,
            type: 'GET', // GET ist ok für Abfragen
            data: {
                action: 'dnd_get_new_messages', // PHP AJAX Action Hook
                _ajax_nonce: dndChatData.nonce,
                since_id: lastMessageId // ID der letzten Nachricht übergeben
            },
            dataType: 'json',
            success: function (response) {
                // console.log('Fetch success:', response); // Debug
                if (response.success && response.data) {
                    if (response.data.messages && response.data.messages.length > 0) {
                        response.data.messages.forEach(function (msg) {
                            addMessageToLog(msg); // Fügt unten an
                        });
                         // Wichtig: Aktualisiere lastMessageId nur wenn neue Nachrichten kamen
                         // Die höchste ID aus der Antwort setzen. addMessageToLog aktualisiert sie auch, aber dies ist sicherer.
                         if (response.data.last_id > lastMessageId) {
                            lastMessageId = response.data.last_id;
                         }
                    }
                    // Falls response.data.last_id existiert, könnten wir lastMessageId auch damit aktualisieren,
                    // selbst wenn keine *neuen* Nachrichten kamen (falls serverseitig IDs übersprungen wurden).
                     if (response.data.last_id && response.data.last_id > lastMessageId) {
                         lastMessageId = response.data.last_id;
                         // console.log('Last ID updated to (even without new messages):', lastMessageId);
                     }

                } else if (!response.success) {
                     console.error('Fehler beim Abrufen von Nachrichten:', response.data);
                     // Hier ggf. Polling stoppen oder Fehler anzeigen? Vorsicht bei dauerhaften Fehlern.
                     // chatStatus.text(dndChatData.text.error + ' (Fetch Error)').fadeIn();
                     // clearInterval(pollingIntervalId); // Polling anhalten bei Fehler?
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                 console.error('AJAX Fehler beim Abrufen von Nachrichten:', textStatus, errorThrown, jqXHR.responseText);
                 // Hier ggf. Polling stoppen oder Fehler anzeigen?
                 // chatStatus.text(dndChatData.text.error + ' (AJAX Fetch Error)').fadeIn();
                 // clearInterval(pollingIntervalId); // Polling anhalten bei Fehler?
            },
            complete: function() {
                // Markiere als nicht mehr ladend, damit nächstes Polling starten kann
                isLoading = false;
            }
        });
    }

     // --- Funktion zum initialen Laden von Nachrichten ---
    function initialLoad() {
        isLoading = true;
        chatLog.html('<p class="chat-system">' + dndChatData.text.loading + '</p>'); // Ladeanzeige

        // AJAX Call aktivieren!
         $.ajax({
            url: dndChatData.ajaxUrl,
            type: 'GET',
            data: {
                action: 'dnd_get_initial_messages', // Eigener Action Hook
                _ajax_nonce: dndChatData.nonce,
                count: dndChatData.initialLoadCount
            },
             dataType: 'json',
            success: function (response) {
                chatLog.empty(); // Ladeanzeige entfernen
                // console.log('Initial load success:', response); // Debug
                if (response.success && response.data) {
                    if (response.data.messages && response.data.messages.length > 0) {
                         response.data.messages.forEach(function (msg) {
                            addMessageToLog(msg); // Fügt unten an
                        });
                        // Setze lastMessageId auf die höchste ID der initial geladenen Nachrichten
                        lastMessageId = response.data.last_id;
                        console.log('Initial load complete. Last message ID:', lastMessageId);
                        scrollToBottom(); // Nach initialem Laden zum Ende scrollen
                    } else {
                         // Keine Nachrichten bisher
                         chatLog.html('<p class="chat-system">' + dndChatData.text.noMessages + '</p>');
                    }
                    // Polling starten, NACHDEM initiale Nachrichten geladen sind
                    startPolling();
                } else {
                    // Fehler beim initialen Laden
                    const errorMsg = response.data && response.data.message ? response.data.message : dndChatData.text.error;
                    chatStatus.text(errorMsg + ' (Initial Load Error)').fadeIn();
                     console.error('Fehler beim initialen Laden:', response);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                 chatLog.empty();
                 chatStatus.text(dndChatData.text.error + ' (AJAX Initial Load Error)').fadeIn();
                 console.error('AJAX Fehler beim initialen Laden:', textStatus, errorThrown, jqXHR.responseText);
            },
            complete: function() {
                isLoading = false;
            }
        });
         // Den temporären Teil entfernen:
         // console.log('AJAX Call zum initialen Laden würde jetzt erfolgen...');
         // setTimeout(function() { ... }, 1000);
    }


    // --- Funktion zum Starten des Pollings ---
    function startPolling() {
        if (pollingIntervalId === null) {
            // Rufe sofort einmal ab, dann im Intervall
            fetchNewMessages();
            pollingIntervalId = setInterval(fetchNewMessages, dndChatData.pollInterval);
            console.log('Polling gestartet mit Intervall:', dndChatData.pollInterval);
        }
    }

     // --- Event Listener für Scrolling ---
     chatLog.on('scroll', function() {
        // Wenn der User nach oben scrollt (nicht mehr ganz unten ist), pausiere automatisches Scrollen
        // Toleranz von 10px
        if (chatLog.scrollTop() + chatLog.innerHeight() < chatLog[0].scrollHeight - 10) {
            if (!isScrollingPaused) {
                 // console.log("Scrolling paused");
                 isScrollingPaused = true;
            }
        } else {
             if (isScrollingPaused) {
                 // console.log("Scrolling resumed");
                 isScrollingPaused = false;
            }
        }
     });


    // --- Event Listener (Senden) ---
    // (Bleiben wie im vorherigen Schritt)
    sendButton.on('click', sendMessage);
    messageInput.on('keypress', function (e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    // --- Initialisierung ---
    // Prüfen ob der Chat Container auf der Seite ist
    if (chatContainer.length > 0) {
        initialLoad(); // Lade initiale Nachrichten und starte dann Polling
    } else {
        console.log("D&D Chat Container nicht gefunden, Initialisierung übersprungen.");
    }

});

});