<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class ProfilePermission extends Model
{
    use UsesTenantConnection;

    protected $table = 'profile_permissions';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'profile_id', 'codmov');
    }
}
