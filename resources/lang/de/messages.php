<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'Diese Anmeldeinformationen stimmen nicht mit unseren Aufzeichnungen überein.',
    'password' => 'Das angegebene Passwort ist falsch.',
    'throttle' => 'Zu viele Anmeldeversuche. Bitte versuchen Sie es erneut in :seconds Sekunden.',
    'verify_store_info' => 'Um den Shop zu überprüfen, klicken Sie auf die Augenschaltfläche in der Shop-Tabelle',
    'quantity_step_size_gte_minimum_order_quantity' => 'Die Mengenabstufung muss größer oder gleich der Mindestbestellmenge sein.',
    'quantity_step_size_lte_total_allowed_quantity' => 'Die Mengenabstufung muss kleiner oder gleich der insgesamt zulässigen Menge sein.',
    'minimum_order_quantity_lte_total_allowed_quantity' => 'Die Mindestbestellmenge muss kleiner oder gleich der insgesamt zulässigen Menge sein.',
    'google_api_key_not_found' => 'Google API-Schlüssel nicht gefunden. Bitte fügen Sie ihn unter Einstellungen > Authentifizierung > Google API-Schlüssel hinzu',
    'created_successfully' => 'Lieferzone erfolgreich erstellt.',
    'creation_error' => 'Bei der Erstellung der Lieferzone ist ein Fehler aufgetreten.',
    'invalid_boundary_json' => 'Ungültiges Grenz-JSON-Format.',
    'internal_server_error' => 'Interner Serverfehler',

    // General Messages
    'something_went_wrong' => 'Etwas ist schief gelaufen. Bitte versuchen Sie es erneut',
    'invalid_coordinates' => 'Ungültige Koordinaten! Bitte wählen Sie eine gültige Adresse',
    'required' => 'Dieses Feld ist erforderlich.',
    'integer' => 'Dieses Feld muss eine ganze Zahl sein.',
    'string' => 'Dieses Feld muss eine Zeichenkette sein.',
    'max' => 'Dieses Feld überschreitet die maximal zulässige Länge.',
    'min' => 'Dieses Feld liegt unter dem minimal zulässigen Wert.',

    // Cart Messages
    'cart_is_empty' => 'Ihr Warenkorb ist leer',
    'cart_item_not_found' => 'Warenkorbartikel nicht gefunden',
    'item_added_to_cart_successfully' => 'Artikel erfolgreich zum Warenkorb hinzugefügt',
    'item_removed_from_cart_successfully' => 'Artikel erfolgreich aus dem Warenkorb entfernt',

    // Order Messages
    'order_created_successfully' => 'Bestellung erfolgreich erstellt',
    'order_not_found' => 'Bestellung nicht gefunden',
    'order_retrieved_successfully' => 'Bestellung erfolgreich abgerufen',
    'orders_retrieved_successfully' => 'Bestellungen erfolgreich abgerufen',

    // Language Names
    'languages' => [
        'english' => 'Englisch',
        'spanish' => 'Spanisch',
        'french' => 'Französisch',
        'german' => 'Deutsch',
        'chinese' => 'Chinesisch',
    ],
];
