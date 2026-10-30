<?php

namespace App\Models\Support;

use App\Traits\UsesSupportConnection;
use Illuminate\Database\Eloquent\Model;

/** Bitácora de auditoría (no se audita a sí misma). */
class Soflog extends Model
{
    use UsesSupportConnection;

    protected $table = 'soflog';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'fecope' => 'date',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'codemp', 'codmov');
    }
}
