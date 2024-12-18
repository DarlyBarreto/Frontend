<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'skin_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skin()
    {
        return $this->belongsTo(Skin::class);
    }
}
