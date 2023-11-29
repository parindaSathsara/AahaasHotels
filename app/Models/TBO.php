<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TBO extends Model
{
    use HasFactory;
    protected $table = "tbo";

    protected $fillable = [
        'city_code',
        'hotel_code',
        'hotel_name',
        'hotel_category',
        'star_rating',
        'hotel_description',
        'hotel_promotion',
        'hotel_policy',
        'published_price',
        'hotel_picture',
        'hotel_address',
        'hotel_contact_no',
        'hotel_map',
        'latitude',
        'longitude',
        'hotel_location',
        'supplier_price',
        'room_details',
    ];
}
