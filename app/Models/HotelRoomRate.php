<?php

namespace App\Models\Hotels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelRoomRate extends Model
{
    use HasFactory;

    protected $table = "hotel_room_daily_inventories";

    protected $fillable = [
        'id',
        'hotel_id',
        'market_nationality',
        'currency',
        'adult_rate',
        'child_with_bed_rate',
        'child_without_bed_rate',
        'child_foc_age',
        'child_with_no_bed_age',
        'child_with_bed_age',
        'adult_age',
        'book_by_days',
        'meal_plan',
        'room_category_id',
        'room_type_id',
        'booking_start_date',
        'booking_end_date',
        'payment_type',
        'blackout_dates',
        'blackout_days',
    ];
}
