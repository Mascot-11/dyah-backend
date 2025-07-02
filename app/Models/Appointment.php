<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Appointment extends Model
{
    use HasFactory,  Notifiable;

    protected $fillable = [
        'user_id',
        'artist_id',
        'appointment_datetime',
        'description',
            'phone_number',
        'image_url',
        'status',
    ];

    // Relationship with user (who made the appointment)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship with artist (assigned to the appointment)
    public function artist()
    {
        return $this->belongsTo(User::class, 'artist_id');
    }
}
