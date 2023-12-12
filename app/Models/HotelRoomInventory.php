<?php

namespace App\Models\Hotels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelRoomInventory extends Model
{
    use HasFactory;

    protected $table = "hotel_room_daily_inventories";

    protected $fillable = [
        'id',
        'rate_id',
        'booking_start_date',
        'booking_end_date',
        'allotment',
        'stop_sale_date'

    ];
}
