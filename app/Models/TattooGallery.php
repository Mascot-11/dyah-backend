<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TattooGallery extends Model
{
    use HasFactory;

    protected $fillable = [
        'image_url',
        'description',
        'artist_id',
    ];
    public function artist()
    {
        return $this->belongsTo(User::class, 'artist_id');
        // Replace User::class with Artist::class if you have separate model
    }
}
