<?php

namespace App\Models\Support;

use App\Traits\UsesSupportConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Credenciales de Factus por empresa y ambiente.
 * Diferencia con Chellnova: client_secret, password y los tokens se guardan CIFRADOS.
 */
class FactusService extends Model
{
    use UsesSupportConnection, SoftDeletes;

    protected $table = 'factus_services';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];

    protected $hidden = ['client_secret', 'password', 'token', 'refresh_token'];

    protected function casts(): array
    {
        return [
            'client_secret' => 'encrypted',
            'password' => 'encrypted',
            'token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'codemp', 'codmov');
    }

    public function tokenExpired(): bool
    {
        return ! $this->token_expires_at || now()->gte($this->token_expires_at);
    }

    public function hasRefreshToken(): bool
    {
        return ! empty($this->refresh_token);
    }
}
