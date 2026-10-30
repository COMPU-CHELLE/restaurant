<?php

namespace App\Models\Support;

use App\Traits\UsesSupportConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use UsesSupportConnection, SoftDeletes;

    protected $table = 'permissions';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    public function module()
    {
        return $this->belongsTo(Module::class, 'codmod', 'codmov');
    }

    public function level()
    {
        return $this->belongsTo(Level::class, 'codniv', 'codmov');
    }

    public function item()
    {
        return $this->belongsTo(Level::class, 'codite', 'codmov');
    }
}
