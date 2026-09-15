<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Order extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['requested_date' => 'date', 'delivery_date' => 'date', 'total' => 'decimal:2', 'advance' => 'decimal:2']; }
    public function client() { return $this->belongsTo(User::class, 'client_id'); }
    public function service() { return $this->belongsTo(Service::class); }
    public function material() { return $this->belongsTo(Material::class); }
    public function aiDesign() { return $this->belongsTo(AiDesign::class); }
    public function tasks() { return $this->hasMany(ProductionTask::class)->orderBy('sequence'); }
    public function history() { return $this->hasMany(OrderStatusHistory::class)->latest(); }
    public function getProgressAttribute(): int { return match ($this->status) { 'pending' => 10, 'confirmed' => 25, 'in_progress' => 55, 'quality' => 75, 'ready' => 90, 'delivered' => 100, default => 0 }; }
    public function getBalanceAttribute(): float { return max(0, (float) $this->total - (float) $this->advance); }
}
