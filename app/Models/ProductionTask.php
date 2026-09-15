<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductionTask extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['started_at' => 'datetime', 'completed_at' => 'datetime']; }
    public function order() { return $this->belongsTo(Order::class); }
    public function worker() { return $this->belongsTo(User::class, 'assigned_to'); }
}
