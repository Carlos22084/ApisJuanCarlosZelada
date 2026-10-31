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
    public function getProgressAttribute(): int
    {
        if (array_key_exists('tasks_count', $this->attributes)
            && array_key_exists('completed_tasks_count', $this->attributes)) {
            $totalTasks = (int) $this->attributes['tasks_count'];
            $completedTasks = (int) $this->attributes['completed_tasks_count'];
        } elseif ($this->relationLoaded('tasks')) {
            $totalTasks = $this->tasks->count();
            $completedTasks = $this->tasks->where('status', 'completed')->count();
        } else {
            $totalTasks = $this->tasks()->count();
            $completedTasks = $this->tasks()->where('status', 'completed')->count();
        }

        if ($totalTasks === 0) {
            return 0;
        }

        return min(100, (int) round(($completedTasks / $totalTasks) * 100));
    }
    public function getBalanceAttribute(): float { return max(0, (float) $this->total - (float) $this->advance); }
}
