<?php

namespace App\Models\Support;

use App\Traits\UsesSupportConnection;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    use UsesSupportConnection;

    protected $table = 'user_roles';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'codmov');
    }
}
