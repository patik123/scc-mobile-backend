<?php

use App\Http\Controllers\Obvescanje;
use App\Http\Controllers\SiteRequests;
use App\Http\Controllers\UntisApi;
use App\Http\Controllers\EviWeb;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Authentication Routes ...
Route::middleware(['check_auth'])->group(function () {
    // standard routes
    Route::get('sites/prehrana', [SiteRequests::class, 'getPrehranaWebsite']);
    Route::get('sites/url_proxy', [SiteRequests::class, 'getSchoolSite']);
    Route::get('sites/validate_token', [SiteRequests::class, 'validateJWTToken']);
    
    // UNTIS FUNCTIONS (API)
    Route::get('untis/get_classes', [UntisApi::class, 'get_classes']);
    Route::get('untis/get_teachers', [UntisApi::class, 'get_teachers']);
    Route::get('untis/get_rooms', [UntisApi::class, 'get_rooms']);
    Route::get('untis/get_subjects', [UntisApi::class, 'get_subjects']);
    Route::get('untis/get_time_grid', [UntisApi::class, 'get_time_grid']);
    Route::get('untis/get_status_data', [UntisApi::class, 'get_status_data']);
    Route::get('untis/get_departments', [UntisApi::class, 'get_departments']);
    Route::get('untis/get_holidays', [UntisApi::class, 'get_holidays']);
    Route::get('untis/get_current_year', [UntisApi::class, 'get_current_year']);
    Route::get('untis/get_latest_update_time', [UntisApi::class, 'get_latest_update_time']);
    Route::get('untis/get_class_timetable', [UntisApi::class, 'get_class_timetable']);
    Route::get('untis/get_teacher_timetable', [UntisApi::class, 'get_teacher_timetable']);
    Route::get('untis/search', [UntisApi::class, 'search']);

    // Obveščanje
    Route::post('obvestila/create_obvestilo', [Obvescanje::class, 'create_obvestilo']);
    Route::get('obvestila/get_obvestila_global', [Obvescanje::class, 'get_obvestila_global']);


    // EviWeb
    Route::post('eviweb/encrypt_user_credits', [EviWeb::class, 'encrypt_user_credits']);
    Route::post('eviweb/login', [EviWeb::class, 'evi_login']);
    Route::post('eviweb/redovalnica', [EviWeb::class, 'evi_redovalnica']);
    Route::post('eviweb/testi', [EviWeb::class, 'evi_testi']);



});

// UJAME VSE STRANI KI NISO NA VOLJO
Route::any('{catchall}', function () {
    return view('welcome');
})->where('catchall', '.*');
