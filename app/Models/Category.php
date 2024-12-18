<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_active'];

    protected $attributes = [
        'is_active' => true
    ];

    public function skins()
    {
        return $this->hasMany(Skin::class);
    }
}
