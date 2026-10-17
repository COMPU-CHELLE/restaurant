<?php

return [
    /*
    | Hosts oficiales de Factus. La URL base de cada configuración debe apuntar a uno de ellos:
    | así soporte no puede mandar credenciales a un servidor cualquiera por error o mala fe.
    */
    'allowed_hosts' => [
        'api.factus.com.co',
        'api-sandbox.factus.com.co',
    ],

    /* URL base sugerida según el ambiente. */
    'defaults' => [
        'sandbox' => 'https://api-sandbox.factus.com.co',
        'production' => 'https://api.factus.com.co',
    ],
];
