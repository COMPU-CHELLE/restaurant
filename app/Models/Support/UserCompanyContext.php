<?php

namespace App\Models\Support;

use App\Traits\UsesSupportConnection;
use Illuminate\Database\Eloquent\Model;

class UserCompanyContext extends Model
{
    use UsesSupportConnection;

    protected $table = 'user_company_context';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'codmov');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'codmov');
    }
}
