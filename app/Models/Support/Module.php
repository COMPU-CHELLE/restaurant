<?php

namespace App\Models\Support;

use App\Traits\UsesSupportConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use UsesSupportConnection, SoftDeletes;

    protected $table = 'modules';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];
}
