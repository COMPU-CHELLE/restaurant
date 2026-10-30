<?php

namespace App\Models\Tenant;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class FactusDocumentsEvent extends Model
{
    use UsesTenantConnection;
    protected $table = 'factus_documents_events';
    protected $primaryKey = 'codmov';
    protected $guarded = ['codmov'];
    protected function casts(): array { return ['payload' => 'array']; }
}
