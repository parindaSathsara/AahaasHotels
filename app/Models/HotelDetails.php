<?php

namespace App\Models\Hotels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelDetails extends Model
{
    use HasFactory;

    protected $table = "hotel_details";

    protected $fillable = [
        'id',
        'hotel_id',
        'driver_accomadation',
        'lift_status',
        'vehicle_approchable',
        'ac_status',
        'covid_safe'
    ];
}
