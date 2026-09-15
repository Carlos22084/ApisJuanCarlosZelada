<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Service extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['active' => 'boolean', 'base_price' => 'decimal:2']; }
    public function orders() { return $this->hasMany(Order::class); }
}
