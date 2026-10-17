<?php

namespace App\Models\Support;

use App\Traits\UsesSupportConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use UsesSupportConnection, SoftDeletes;

    protected $table = 'roles';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    public function userRoles()
    {
        return $this->hasMany(UserRole::class, 'role_id', 'codmov');
    }
}
