<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Printer extends Model
{
    use UsesTenantConnection, SoftDeletes;

    protected $table = 'printers';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];
}
