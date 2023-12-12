<?php

namespace App\Models\Hotels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelRoomDailyInventory extends Model
{
    use HasFactory;

    protected $table = "hotel_room_daily_inventories";

    protected $fillable = [
        'id',
        'hotel_id',
        'inventory_id',
        'room_category_id',
        'date',
        'daily_allotment',
        'used',
        'balance'

    ];
}
