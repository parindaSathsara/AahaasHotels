<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tbo', function (Blueprint $table) {
            $table->id();
            $table->integer('city_code')->nullable();
            $table->integer('hotel_code');
            $table->string('hotel_name', 255);
            $table->string('hotel_category')->nullable();
            $table->string('star_rating');
            $table->string('hotel_description');
            $table->string('hotel_promotion')->nullable();
            $table->string('hotel_policy')->nullable();
            $table->double('published_price');
            $table->text('hotel_picture');
            $table->string('hotel_address');
            $table->string('hotel_contact_no')->nullable();
            $table->string('hotel_map')->nullable();
            $table->double('latitude');
            $table->double('longitude');
            $table->string('hotel_location')->nullable();
            $table->integer('supplier_price')->nullable();
            $table->string('room_details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbo');
    }
};
