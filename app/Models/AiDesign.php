<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiDesign extends Model
{
    protected $guarded = [];
    public function user() { return $this->belongsTo(User::class); }
    public function material() { return $this->belongsTo(Material::class); }
    public function order() { return $this->hasOne(Order::class); }
}
