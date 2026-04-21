<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

Route::get('/location-info',[ApiController::class,'locationInfo']);
Route::get('/prayer-times',[ApiController::class,'prayerTimes']);
Route::get('/prohibited-prayer-times',[ApiController::class,'prohibitedPrayerTimes']);
Route::get('/qibla',[ApiController::class,'qibla']);
Route::get('/ramadan-times',[ApiController::class,'ramadanTimes']);
Route::get('/surah-list',[ApiController::class,'surahList']);
Route::get('/surah-details', [ApiController::class, 'surahDetails']);
Route::get('/surah-arabic',[ApiController::class,'surahArabic']);