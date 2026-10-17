<?php

namespace App\Models\Support;

use App\Traits\UsesSupportConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Level extends Model
{
    use UsesSupportConnection, SoftDeletes;

    protected $table = 'levels';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];
}
