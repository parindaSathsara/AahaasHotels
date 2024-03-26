<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use HasFactory;
    protected $table = "hotels";

    protected $fillable = [
        'hotel_name',
        'hotel_description',
        'hotel_level',
        'category1',
        'longtitude',
        'latitude',
        'provider',
        'hotel_address',
        'trip_advisor_link',
        'hotel_image',
        'country',
        'city',
        'micro_location',
        'hotel_status',
        'startdate',
        'enddate',
        'vendor_id',
        'preferred_status',
        'created_at',
        'updated_at',
    ];
}
