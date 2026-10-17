<?php

namespace App\Traits;

/** Modelos de la base central (support). */
trait UsesSupportConnection
{
    public function getConnectionName()
    {
        return 'mysql';
    }
}
