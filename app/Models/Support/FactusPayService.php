<?php
namespace App\Models\Support;
use App\Traits\UsesSupportConnection; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class FactusPayService extends Model { use UsesSupportConnection, SoftDeletes; protected $table='factus_pay_services'; protected $primaryKey='codmov'; protected $guarded=['codmov']; protected $hidden=['password','token']; protected function casts(): array { return ['password'=>'encrypted','token'=>'encrypted','active'=>'boolean']; } }
