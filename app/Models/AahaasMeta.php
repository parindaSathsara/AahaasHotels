<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class AahaasMeta extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = "aahaas_meta";

    protected $fillable = [
        'hotel_code',
        'hotel_name',
    ];

}
