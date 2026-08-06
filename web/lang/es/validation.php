<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'El :atributo debe ser aceptado.',
    'accepted_if' => 'El :atributo debe aceptarse cuando :otro es :valor.',
    'active_url' => 'El :atributo no es una URL válida.',
    'after' => 'El :atributo debe ser una fecha posterior a :date.',
    'after_or_equal' => 'El :atributo debe ser una fecha posterior o igual a :fecha.',
    'alpha' => 'El atributo :solo debe contener letras.',
    'alpha_dash' => 'El :atributo solo debe contener letras, números, guiones y guiones bajos.',
    'alpha_num' => 'El atributo :solo debe contener letras y números.',
    'array' => 'El :atributo debe ser una matriz.',
    'before' => 'El :atributo debe ser una fecha anterior a :fecha.',
    'before_or_equal' => 'El :atributo debe ser una fecha anterior o igual a :fecha.',
    'between' => [
        'array' => 'El :atributo debe tener entre :min y :max elementos.',
        'file' => 'El :atributo debe estar entre :min y :max kilobytes.',
        'numeric' => 'El :atributo debe estar entre :min y :max.',
        'string' => 'El :atributo debe tener entre :min y :max caracteres.',
    ],
    'boolean' => 'El campo :atributo debe ser verdadero o falso.',
    'confirmed' => 'La confirmación de :atributo no coincide.',
    'current_password' => 'La contraseña es incorrecta.',
    'date' => 'El :atributo no es una fecha válida.',
    'date_equals' => 'El :atributo debe ser una fecha igual a :fecha.',
    'date_format' => 'El :atributo no coincide con el formato :formato.',
    'declined' => 'El :atributo debe ser rechazado.',
    'declined_if' => 'El :atributo debe rechazarse cuando :otro es :valor.',
    'different' => 'El :atributo y :otro deben ser diferentes.',
    'digits' => 'El :atributo debe ser :dígitos dígitos.',
    'digits_between' => 'El :atributo debe estar entre :min y :max dígitos.',
    'dimensions' => 'El :atributo tiene dimensiones de imagen no válidas.',
    'distinct' => 'El campo :atributo tiene un valor duplicado.',
    'email' => 'El :atributo debe ser una dirección de correo electrónico válida.',
    'ends_with' => 'El :atributo debe terminar con uno de los siguientes: :valores.',
    'enum' => 'El :atributo seleccionado no es válido.',
    'exists' => 'El :atributo seleccionado no es válido.',
    'file' => 'El :atributo debe ser un archivo.',
    'filled' => 'El campo :atributo debe tener un valor.',
    'gt' => [
        'array' => 'El :atributo debe tener más de :elementos de valor.',
        'file' => 'El :atributo debe ser mayor que :valor kilobytes.',
        'numeric' => 'El :atributo debe ser mayor que :valor.',
        'string' => 'El :atributo debe ser mayor que los :valores.',
    ],
    'gte' => [
        'array' => 'El :atributo debe tener :elementos de valor o más.',
        'file' => 'El :atributo debe ser mayor o igual que :valor en kilobytes.',
        'numeric' => 'El :atributo debe ser mayor o igual que :valor.',
        'string' => 'El :atributo debe ser mayor o igual que :valor caracteres.',
    ],
    'image' => 'El :atributo debe ser una imagen.',
    'in' => 'El :atributo seleccionado no es válido.',
    'in_array' => 'El campo :atributo no existe en :otro.',
    'integer' => 'El :atributo debe ser un número entero.',
    'ip' => 'El :atributo debe ser una dirección IP válida.',
    'ipv4' => 'El :atributo debe ser una dirección IPv4 válida.',
    'ipv6' => 'El :atributo debe ser una dirección IPv6 válida.',
    'json' => 'El :atributo debe ser una cadena JSON válida.',
    'lt' => [
        'array' => 'El :atributo debe tener menos de :elementos de valor.',
        'file' => 'El :atributo debe tener menos de :valor kilobytes.',
        'numeric' => 'El :atributo debe ser menor que :valor.',
        'string' => 'El :atributo debe tener menos de :valor de caracteres.',
    ],
    'lte' => [
        'array' => 'El :atributo no debe tener más de :elementos de valor.',
        'file' => 'El :atributo debe ser menor o igual que :valor en kilobytes.',
        'numeric' => 'El :atributo debe ser menor o igual que :valor.',
        'string' => 'El :atributo debe ser menor o igual que :valor caracteres.',
    ],
    'mac_address' => 'El :atributo debe ser una dirección MAC válida.',
    'max' => [
        'array' => 'El :atributo no debe tener más de :máx elementos.',
        'file' => 'El :atributo no debe ser mayor que :max kilobytes.',
        'numeric' => 'El :atributo no debe ser mayor que :max.',
        'string' => 'El :atributo no debe tener más de :máx caracteres.',
    ],
    'mimes' => 'El :atributo debe ser un archivo de tipo: :valores.',
    'mimetypes' => 'El :atributo debe ser un archivo de tipo: :valores.',
    'min' => [
        'array' => 'El :atributo debe tener al menos :elementos mínimos.',
        'file' => 'El :atributo debe tener al menos :min kilobytes.',
        'numeric' => 'El :atributo debe ser al menos :min.',
        'string' => 'El :atributo debe tener al menos :min caracteres.',
    ],
    'multiple_of' => 'El :atributo debe ser un múltiplo de :valor.',
    'not_in' => 'El :atributo seleccionado no es válido.',
    'not_regex' => 'El formato de :atributo no es válido.',
    'numeric' => 'El :atributo debe ser un número.',
    'password' => 'La contraseña es incorrecta.',
    'present' => 'El campo :atributo debe estar presente.',
    'prohibited' => 'El campo :atributo está prohibido.',
    'prohibited_if' => 'El campo :atributo está prohibido cuando :otro es :valor.',
    'prohibited_unless' => 'El campo :atributo está prohibido a menos que :otro esté en :valores.',
    'prohibits' => 'El campo :atributo prohíbe que :other esté presente.',
    'regex' => 'El formato de :atributo no es válido.',
    'required' => 'El campo :atributo es obligatorio.',
    'required_array_keys' => 'El campo :atributo debe contener entradas para: :valores.',
    'required_if' => 'El campo :atributo es obligatorio cuando :otro es :valor.',
    'required_unless' => 'El campo :atributo es obligatorio a menos que :otro esté en :valores.',
    'required_with' => 'El campo :atributo es obligatorio cuando :valores está presente.',
    'required_with_all' => 'El campo :atributo es obligatorio cuando los :valores están presentes.',
    'required_without' => 'El campo :atributo es obligatorio cuando :valores no está presente.',
    'required_without_all' => 'El campo :atributo es obligatorio cuando ninguno de los :valores está presente.',
    'same' => 'El :atributo y :otro deben coincidir.',
    'size' => [
        'array' => 'El :atributo debe contener :elementos de tamaño.',
        'file' => 'El :atributo debe ser :size kilobytes.',
        'numeric' => 'El :atributo debe ser :tamaño.',
        'string' => 'El :atributo debe tener :tamaño de caracteres.',
    ],
    'starts_with' => 'El :atributo debe comenzar con uno de los siguientes: :valores.',
    'string' => 'El :atributo debe ser una cadena.',
    'timezone' => 'El :atributo debe ser una zona horaria válida.',
    'unique' => 'El :atributo ya ha sido tomado.',
    'uploaded' => 'El :atributo no se pudo cargar.',
    'url' => 'El :atributo debe ser una URL válida.',
    'uuid' => 'El :atributo debe ser un UUID válido.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'mensaje personalizado',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'dashboard' => 'Panel',
        'profile' => 'Perfil',
        'hub_id' => 'Centro',
        'image_id' => 'Imagen',
        'driving_license_image_id' => 'Licencia de conducir',
        'image' => 'imagen',
        'name' => 'Nombre',
        'title' => 'Título',
        'status' => 'Estado',
        'add' => 'Agregar',
        'edit' => 'Editar',
        'view' => 'Vista',
        'description' => 'Descripción',
        'delivery_charge' => 'Gastos de envío',
        'pickup_charge' => 'Cargo de recogida',
        'return_charge' => 'Cargo de devolución',
        'opening_balance' => 'Saldo de apertura',
        'email' => 'Correo electrónico',
        'username' => 'Nombre de usuario',
        'phone' => 'Teléfono',
        'address' => 'DIRECCIÓN',
        'user' => 'Usuaria',
        'category' => 'categoría',
        'delivery_category' => 'La Categoría ya ha sido tomada.',
        'user_assigned' => 'La Usuario ya asignada',
        'user_exists' => 'La Usuario ya existe.',
    ],

];
