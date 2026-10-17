<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Profile extends Model
{
    use UsesTenantConnection, SoftDeletes;

    protected $table = 'profiles';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    public function permissions()
    {
        return $this->hasMany(ProfilePermission::class, 'profile_id', 'codmov');
    }
}
