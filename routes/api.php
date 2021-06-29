<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\JWTAuthController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\ArticlesController;

use App\Http\Controllers\UtilController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('register', [JWTAuthController::class,'register'])->name('api.jwt.register');
Route::post('login', [JWTAuthController::class,'login'])->name('api.jwt.login');

Route::group(['middleware' => 'auth:api'], function(){
    /**
     * JWT Auth
     */
    Route::get('user', [JWTAuthController::class,'user'])->name('api.jwt.user');
    Route::get('refresh', [JWTAuthController::class,'refresh'])->name('api.jwt.refresh');
    Route::get('logout', [JWTAuthController::class,'logout'])->name('api.jwt.logout');

    /**
     * Users
     */
    Route::get('users', [UsersController::class,'userList'])->name('api.user.list');
    Route::get('users/{user_seq}', [UsersController::class,'userInfo'])->name('api.user.info');
    Route::put('users', [UsersController::class,'userUpdate'])->name('api.user.update');
    Route::patch('users', [UsersController::class,'profileImageUpload'])->name('api.user.profileImageUpload');

    /**
     * Articles
     */
    Route::get('articles', [ArticlesController::class,'articleList'])->name('api.article.list');
    Route::post('articles', [ArticlesController::class,'articleCreate'])->name('api.article.create');

});

/**
 * Error
 */
Route::get('unauthorized', function() {
    return response()->json([
        'status' => 'error',
        'message' => 'The api key is invalid.'
    ], 401);
})->name('api.jwt.unauthorized');