<?php

namespace App\Models\Support;

use App\Traits\UsesSupportConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Zone extends Model
{
    use UsesSupportConnection, SoftDeletes;

    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];
    protected $table = 'zones';

    public function country()
    {
        return $this->belongsTo(Country::class, 'codcoi', 'codmov');
    }

    public function subzones()
    {
        return $this->hasMany(Subzone::class, 'codzon', 'codmov');
    }

    public function getFactusIdAttribute($value)
    {
        return $value;
    }
}
