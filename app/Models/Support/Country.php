<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Support\Currency;
use App\Models\Support\Zone;
use App\Traits\UsesSupportConnection;

class Country extends Model
{
    use UsesSupportConnection, SoftDeletes;

    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];
    protected $table = 'countries';

    public function zones()
    {
        return $this->hasMany(Zone::class, 'codcoi', 'codmov');
    }

    public function currencies()
    {
        return $this->hasMany(Currency::class, 'codcoi', 'codmov');
    }
}

