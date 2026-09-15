<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Material extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['active' => 'boolean', 'stock' => 'decimal:2', 'unit_price' => 'decimal:2']; }
    public function getLowStockAttribute(): bool { return $this->stock <= $this->minimum_stock; }
}
