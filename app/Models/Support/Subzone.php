<?php

namespace App\Models\Support;

use App\Traits\UsesSupportConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subzone extends Model
{
    use UsesSupportConnection, SoftDeletes;

    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];
    protected $table = 'subzones';

    protected $fillable = [
        'code', 'factus_id', 'name', 'state', 'codzon', 'host', 'codope', 'basdat'
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'codzon', 'codmov');
    }

    /**
     * Acceso directo al id de Factus
     */
    public function getFactusIdAttribute($value)
    {
        return $value;
    }
}
