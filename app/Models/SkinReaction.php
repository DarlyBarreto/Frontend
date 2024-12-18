<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkinReaction extends Model
{
    protected $fillable = ['user_id', 'skin_id', 'type'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skin()
    {
        return $this->belongsTo(Skin::class);
    }
}
