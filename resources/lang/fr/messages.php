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

    'failed' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
    'password' => 'Le mot de passe fourni est incorrect.',
    'throttle' => 'Trop de tentatives de connexion. Veuillez réessayer dans :seconds secondes.',
    'verify_store_info' => 'Pour vérifier le magasin, cliquez sur le bouton œil dans le tableau des magasins',
    'quantity_step_size_gte_minimum_order_quantity' => 'La taille de l\'étape de quantité doit être supérieure ou égale à la quantité minimale de commande.',
    'quantity_step_size_lte_total_allowed_quantity' => 'La taille de l\'étape de quantité doit être inférieure ou égale à la quantité totale autorisée.',
    'minimum_order_quantity_lte_total_allowed_quantity' => 'La quantité minimale de commande doit être inférieure ou égale à la quantité totale autorisée.',
    'google_api_key_not_found' => 'Clé API Google introuvable. Veuillez l\'ajouter depuis Paramètres > Authentification > Clé API Google',
    'created_successfully' => 'Zone de livraison créée avec succès.',
    'creation_error' => 'Une erreur s\'est produite lors de la création de la zone de livraison.',
    'invalid_boundary_json' => 'Format JSON de limite invalide.',
    'internal_server_error' => 'Erreur interne du serveur',

    // General Messages
    'something_went_wrong' => 'Quelque chose s\'est mal passé. Veuillez réessayer',
    'invalid_coordinates' => 'Coordonnées invalides ! Veuillez sélectionner une adresse valide',
    'required' => 'Ce champ est obligatoire.',
    'integer' => 'Ce champ doit être un nombre entier.',
    'string' => 'Ce champ doit être une chaîne de caractères.',
    'max' => 'Ce champ dépasse la longueur maximale autorisée.',
    'min' => 'Ce champ est inférieur à la valeur minimale autorisée.',

    // Cart Messages
    'cart_is_empty' => 'Votre panier est vide',
    'cart_item_not_found' => 'Article du panier non trouvé',
    'item_added_to_cart_successfully' => 'Article ajouté au panier avec succès',
    'item_removed_from_cart_successfully' => 'Article retiré du panier avec succès',

    // Order Messages
    'order_created_successfully' => 'Commande créée avec succès',
    'order_not_found' => 'Commande non trouvée',
    'order_retrieved_successfully' => 'Commande récupérée avec succès',
    'orders_retrieved_successfully' => 'Commandes récupérées avec succès',

    // Language Names
    'languages' => [
        'english' => 'Anglais',
        'spanish' => 'Espagnol',
        'french' => 'Français',
        'german' => 'Allemand',
        'chinese' => 'Chinois',
    ],
];
