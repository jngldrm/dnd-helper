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

    // --- NEU: Variablen für die Befehlshistorie ---
    let messageHistory = []; // Array zum Speichern der gesendeten Nachrichten
    let historyIndex = -1; // Aktueller Index in der Historie (-1 = keine Historie ausgewählt)
    const MAX_HISTORY_SIZE = 50; // Maximale Anzahl zu speichernder Befehle

    // Temporärer Speicher für die aktuelle Eingabe, wenn der User die History durchgeht
    let currentInputBuffer = '';
    // --- Ende NEU ---


    // --- Funktion zum Formatieren des Timestamps (lokale Zeit) ---
    function formatTimestamp(timestampGMT) {
        if (!timestampGMT) return '';
        try {
            const date = new Date(timestampGMT + 'Z'); // 'Z' kennzeichnet UTC/GMT
            return date.toLocaleTimeString(navigator.language, { hour: '2-digit', minute: '2-digit' });
        } catch (e) {
            console.error("Timestamp format error:", e);
            return '';
        }
    }

    // --- Funktion zum Scrollen zum Ende (wenn nicht pausiert) ---
    function scrollToBottom() {
        if (!isScrollingPaused) {
            setTimeout(() => {
                 chatLog.scrollTop(chatLog[0].scrollHeight);
            }, 50);
        }
    }

     // --- Funktion zum Hinzufügen von Nachrichten zum Chat-Log ---
    function addMessageToLog(messageData, isPrepending = false) {
        const messageId = 'dnd-message-' + messageData.id;
        if ($('#' + messageId).length > 0) {
            return;
        }

        const messageDiv = $('<div></div>')
                            .addClass('chat-message-wrapper')
                            .addClass('chat-' + messageData.type)
                            .attr('id', messageId);

        if (messageData.is_own) {
            messageDiv.addClass('own-message');
        }

        const formattedTime = formatTimestamp(messageData.timestamp_gmt);
        let messageHTML = '';

        if (messageData.type === 'roll') {
            messageHTML = messageData.content;
             if (messageData.roll_details && messageData.roll_details.rolls) {
                const detailsText = `Input: ${messageData.roll_details.input}\nRolls: [${messageData.roll_details.rolls.join(', ')}]\nModifier: ${messageData.roll_details.modifier}\nTotal: ${messageData.roll_details.total}`;
                messageDiv.attr('title', detailsText);
            }
        } else if (messageData.type === 'message') {
             messageHTML = messageData.content.replace('%%TIMESTAMP%%', formattedTime);
        } else {
            messageHTML = `<span class="message-content">${messageData.content}</span>`;
        }

        messageDiv.html(messageHTML);

        if (isPrepending) {
            chatLog.prepend(messageDiv);
        } else {
            chatLog.append(messageDiv);
            scrollToBottom();
        }

        if (!isPrepending && messageData.id > lastMessageId) { // Update only for new messages at the bottom
             lastMessageId = messageData.id;
        } else if (isPrepending && messageData.id < lastMessageId) {
             // If prepending, the lastMessageId remains the highest ID we've seen overall
        } else if (messageData.id > lastMessageId) {
            // Catch cases where initial load might not set it correctly
            lastMessageId = messageData.id;
        }
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
        chatStatus.empty();

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
                    messageInput.val(''); // Input leeren

                    // --- NEU: Nachricht zur Historie hinzufügen ---
                    // Nur zur Historie hinzufügen, wenn es nicht die gleiche wie die letzte ist
                    if (messageHistory.length === 0 || messageHistory[messageHistory.length - 1] !== message) {
                        messageHistory.push(message);
                        // Historie auf maximale Größe begrenzen
                        if (messageHistory.length > MAX_HISTORY_SIZE) {
                            messageHistory.shift(); // Ältesten Eintrag entfernen
                        }
                    }
                    historyIndex = -1; // Historien-Navigation zurücksetzen
                    currentInputBuffer = ''; // Buffer leeren
                    // --- Ende NEU ---

                    fetchNewMessages(true); // Wichtig: Sofort neue Nachrichten holen
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
        if (isLoading && !force) {
            return;
        }
        isLoading = true; // Set loading true even for polling

        $.ajax({
            url: dndChatData.ajaxUrl,
            type: 'GET',
            data: {
                action: 'dnd_get_new_messages',
                _ajax_nonce: dndChatData.nonce,
                since_id: lastMessageId
            },
            dataType: 'json',
            success: function (response) {
                if (response.success && response.data) {
                    let newMessagesAdded = false;
                    if (response.data.messages && response.data.messages.length > 0) {
                        response.data.messages.forEach(function (msg) {
                            addMessageToLog(msg);
                        });
                        newMessagesAdded = true;
                         // lastMessageId wird in addMessageToLog aktualisiert, aber wir nehmen die höchste ID vom Server
                         if (response.data.last_id > lastMessageId) {
                             lastMessageId = response.data.last_id;
                         }
                    } else if (response.data.last_id && response.data.last_id > lastMessageId) {
                        // Update ID even if no messages returned, in case of gaps etc.
                        lastMessageId = response.data.last_id;
                    }
                } else if (!response.success) {
                     console.error('Fehler beim Abrufen von Nachrichten:', response.data);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                 console.error('AJAX Fehler beim Abrufen von Nachrichten:', textStatus, errorThrown, jqXHR.responseText);
            },
            complete: function() {
                isLoading = false; // Reset loading flag after request completes
            }
        });
    }

     // --- Funktion zum initialen Laden von Nachrichten ---
    function initialLoad() {
        isLoading = true;
        chatLog.html('<p class="chat-system">' + dndChatData.text.loading + '</p>');

         $.ajax({
            url: dndChatData.ajaxUrl,
            type: 'GET',
            data: {
                action: 'dnd_get_initial_messages',
                _ajax_nonce: dndChatData.nonce,
                count: dndChatData.initialLoadCount
            },
             dataType: 'json',
            success: function (response) {
                chatLog.empty();
                if (response.success && response.data) {
                     if (response.data.messages && response.data.messages.length > 0) {
                         // Sort messages by ID ascending just in case they arrive out of order
                         response.data.messages.sort((a, b) => a.id - b.id);
                         response.data.messages.forEach(function (msg) {
                            addMessageToLog(msg); // Adds to bottom
                        });
                        // Set lastMessageId to the highest ID from the initial load
                        lastMessageId = response.data.last_id;
                        console.log('Initial load complete. Last message ID:', lastMessageId);
                        scrollToBottom();
                    } else {
                         chatLog.html('<p class="chat-system">' + dndChatData.text.noMessages + '</p>');
                         // If no messages, lastMessageId remains 0, which is correct
                    }
                    startPolling();
                } else {
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
    }


    // --- Funktion zum Starten des Pollings ---
    function startPolling() {
        if (pollingIntervalId === null) {
            fetchNewMessages();
            pollingIntervalId = setInterval(fetchNewMessages, dndChatData.pollInterval);
            console.log('Polling gestartet mit Intervall:', dndChatData.pollInterval);
        }
    }

     // --- Event Listener für Scrolling ---
     chatLog.on('scroll', function() {
        if (chatLog.scrollTop() + chatLog.innerHeight() < chatLog[0].scrollHeight - 10) {
            if (!isScrollingPaused) {
                 isScrollingPaused = true;
            }
        } else {
             if (isScrollingPaused) {
                 isScrollingPaused = false;
                 // Optional: Sofort scrollen, wenn der User wieder ganz unten ist
                 // scrollToBottom();
            }
        }
     });


    // --- Event Listener (Senden) ---
    sendButton.on('click', sendMessage);
    messageInput.on('keypress', function (e) {
        // Send on Enter key
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    // --- NEU: Event Listener für Pfeiltasten im Input ---
    messageInput.on('keydown', function(e) {
        const key = e.key || e.keyCode;

        // Pfeil nach oben (ArrowUp oder 38)
        if (key === 'ArrowUp' || key === 38) {
            e.preventDefault(); // Verhindert Cursor-Bewegung an Anfang/Ende

            if (messageHistory.length === 0) return; // Nichts tun, wenn keine Historie

            if (historyIndex === -1) {
                // Wenn wir am Anfang der Navigation sind, den aktuellen Input speichern
                currentInputBuffer = messageInput.val();
                historyIndex = messageHistory.length - 1; // Starte mit dem letzten Eintrag
                messageInput.val(messageHistory[historyIndex]);
            } else if (historyIndex > 0) {
                // Gehe einen Eintrag zurück
                historyIndex--;
                messageInput.val(messageHistory[historyIndex]);
            }
            // Cursor ans Ende setzen
            this.selectionStart = this.selectionEnd = messageInput.val().length;
        }
        // Pfeil nach unten (ArrowDown oder 40)
        else if (key === 'ArrowDown' || key === 40) {
            e.preventDefault(); // Verhindert Cursor-Bewegung an Anfang/Ende

             if (historyIndex === -1) return; // Nichts tun, wenn nicht in der History navigiert wird

            if (historyIndex < messageHistory.length - 1) {
                // Gehe einen Eintrag vorwärts
                historyIndex++;
                messageInput.val(messageHistory[historyIndex]);
            } else if (historyIndex === messageHistory.length - 1) {
                // Wenn am letzten Eintrag, stelle den ursprünglichen Input wieder her
                historyIndex = -1; // Verlasse den History-Modus
                messageInput.val(currentInputBuffer);
                currentInputBuffer = ''; // Buffer leeren
            }
             // Cursor ans Ende setzen
             this.selectionStart = this.selectionEnd = messageInput.val().length;
        } else {
             // Bei jeder anderen Taste die History-Navigation zurücksetzen
             // (außer vielleicht Shift, Ctrl, Alt etc. - aber das ist für jetzt ok)
             // historyIndex = -1;
             // currentInputBuffer = '';
             // ^-- Deaktiviert: Besser nur zurücksetzen, wenn tatsächlich gesendet wird oder Pfeil runter bis zum Ende
        }
    });
    // --- Ende NEU ---

    // --- Initialisierung ---
    if (chatContainer.length > 0) {
        initialLoad();
    } else {
        console.log("D&D Chat Container nicht gefunden, Initialisierung übersprungen.");
    }

});