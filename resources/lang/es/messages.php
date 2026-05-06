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

    'failed' => 'Estas credenciales no coinciden con nuestros registros.',
    'password' => 'La contraseña proporcionada es incorrecta.',
    'throttle' => 'Demasiados intentos de inicio de sesión. Por favor, inténtelo de nuevo en :seconds segundos.',
    'verify_store_info' => 'Para verificar la tienda, haga clic en el botón de ojo en la tabla de tiendas',
    'quantity_step_size_gte_minimum_order_quantity' => 'El tamaño del paso de cantidad debe ser mayor o igual que la cantidad mínima de pedido.',
    'quantity_step_size_lte_total_allowed_quantity' => 'El tamaño del paso de cantidad debe ser menor o igual que la cantidad total permitida.',
    'minimum_order_quantity_lte_total_allowed_quantity' => 'La cantidad mínima de pedido debe ser menor o igual que la cantidad total permitida.',
    'google_api_key_not_found' => 'No se encontró la clave API de Google. Agréguela desde Configuración > Autenticación > Clave API de Google',
    'created_successfully' => 'Zona de entrega creada con éxito.',
    'creation_error' => 'Se produjo un error al crear la zona de entrega.',
    'invalid_boundary_json' => 'Formato JSON de límite no válido.',
    'internal_server_error' => 'Error interno del servidor',

    // General Messages
    'something_went_wrong' => 'Algo salió mal. Por favor, inténtelo de nuevo',
    'invalid_coordinates' => '¡Coordenadas no válidas! Por favor, seleccione una dirección válida',
    'required' => 'Este campo es obligatorio.',
    'integer' => 'Este campo debe ser un número entero.',
    'string' => 'Este campo debe ser una cadena de texto.',
    'max' => 'Este campo excede la longitud máxima permitida.',
    'min' => 'Este campo está por debajo del valor mínimo permitido.',

    // Cart Messages
    'cart_is_empty' => 'Su carrito está vacío',
    'cart_item_not_found' => 'Artículo del carrito no encontrado',
    'item_added_to_cart_successfully' => 'Artículo añadido al carrito con éxito',
    'item_removed_from_cart_successfully' => 'Artículo eliminado del carrito con éxito',

    // Order Messages
    'order_created_successfully' => 'Pedido creado con éxito',
    'order_not_found' => 'Pedido no encontrado',
    'order_retrieved_successfully' => 'Pedido recuperado con éxito',
    'orders_retrieved_successfully' => 'Pedidos recuperados con éxito',

    // Language Names
    'languages' => [
        'english' => 'Inglés',
        'spanish' => 'Español',
        'french' => 'Francés',
        'german' => 'Alemán',
        'chinese' => 'Chino',
    ],
];
