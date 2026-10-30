<?php

namespace App\Models\Support;

use App\Traits\UsesSupportConnection;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use UsesSupportConnection;

    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];
    protected $table = 'currencies';

    public function country()
    {
        return $this->belongsTo(Country::class, 'codcoi', 'codmov');
    }
}
