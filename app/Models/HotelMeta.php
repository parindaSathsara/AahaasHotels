<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelMeta extends Model
{
    use HasFactory;


    protected $table = 'aahaas_hotel_meta';

    // public $timestamps = false;

    protected $fillable = [
        'hotelCode',
        'ahs_HotelId',
        'hotelName',
        'hotelDescription',
        'city_code',
        'country',
        'countryCode',
        'latitude',
        'longitude',
        'catgory',
        'boards',
        'address',
        'postalCode',
        'city',
        'email',
        'web',
        'class',
        'tripAdvisor',
        'facilities',
        'images',
        'rating',
        'provider',
        'microLocation',
        'published_price',
        'driverAcc',
        'liftStatus',
        'vehicleApproach',
        'accountStatus',
    ];
}
