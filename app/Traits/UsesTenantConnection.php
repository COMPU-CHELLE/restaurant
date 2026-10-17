<?php

namespace App\Traits;

trait UsesTenantConnection
{
    protected $connection = 'tenant';
}
