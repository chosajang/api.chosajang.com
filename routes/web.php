<?php

use App\Http\CommonLib;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/error', function(){
    return CommonLib::errorCode(100);
});

Route::post('/login', [AuthController::class,'login']);

Route::middleware('apiAuth')->group( function() {

    Route::get('/logout', [AuthController::class,'logout']);
    Route::get('/loginCheck',[AuthController::class,'loginCheck']);

    Route::prefix('users')->group(function(){
        Route::get('/{user_seq}',[UserController::class,'info']);
    });
});
