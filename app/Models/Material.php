<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Material extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['active' => 'boolean', 'stock' => 'decimal:2', 'unit_price' => 'decimal:2']; }
    public function getLowStockAttribute(): bool { return $this->stock <= $this->minimum_stock; }
    public function getTextureTypeAttribute(): string
    {
        $name = mb_strtolower($this->name);

        return match (true) {
            str_contains($name, 'terciopelo') => 'velvet',
            str_contains($name, 'lino') => 'linen',
            str_contains($name, 'chenille') => 'chenille',
            str_contains($name, 'bouclé'), str_contains($name, 'boucle') => 'boucle',
            str_contains($name, 'microfibra'), str_contains($name, 'microsuede') => 'microfiber',
            str_contains($name, 'jacquard') => 'jacquard',
            str_contains($name, 'poliéster') => 'polyester',
            $this->category === 'Sintético', $this->category === 'Cuero' => 'leather',
            default => 'linen',
        };
    }
}
