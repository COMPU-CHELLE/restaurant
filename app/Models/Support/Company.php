<?php

namespace App\Models\Support;

use App\Traits\UsesSupportConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use UsesSupportConnection, SoftDeletes;

    protected $table = 'companies';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    protected function casts(): array
    {
        return [
            'provisioned_at' => 'datetime',
        ];
    }

    public function contexts()
    {
        return $this->hasMany(UserCompanyContext::class, 'company_id', 'codmov');
    }
}
