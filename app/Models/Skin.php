<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Skin extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image_path',
        'user_id',
        'category_id',
        'likes',
        'dislikes',
        'is_active'
    ];

    protected $attributes = [
        'is_active' => true
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function reactions()
    {
        return $this->hasMany(SkinReaction::class);
    }
}
