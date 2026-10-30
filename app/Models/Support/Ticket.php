<?php

namespace App\Models\Support;

use App\Models\User;
use App\Traits\UsesSupportConnection;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use UsesSupportConnection;

    protected $table = 'tickets';

    protected $fillable = [
        'company_id',
        'created_by',
        'subject',
        'description',
        'category',
        'priority',
        'status',
        'response',
        'assigned_to',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'codmov');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
