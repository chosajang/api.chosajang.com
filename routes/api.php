<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\JWTAuthController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\ArticlesController;
use App\Http\Controllers\DashboardController;

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
     * DashBoard
     */
    Route::get('dashboard', [DashboardController::class,'dashboard'])->name('api.dashboard');

    /**
     * Users
     */
    Route::get('users', [UsersController::class,'userList'])->name('api.user.list');
    Route::get('users/{user_seq}', [UsersController::class,'userRead'])->name('api.user.read');
    Route::put('users', [UsersController::class,'userUpdate'])->name('api.user.update');
    Route::patch('users/profileImage', [UsersController::class,'profileImageUpload'])->name('api.user.profileImageUpload');
    Route::patch('users/passwordChange', [UsersController::class,'passwordChange'])->name('api.user.passwordChange');

    /**
     * Articles
     */
    Route::get('articles', [ArticlesController::class,'articleList'])->name('api.article.list');
    Route::get('articles/{article_seq}', [ArticlesController::class,'articleRead'])->name('api.article.read');
    Route::post('articles', [ArticlesController::class,'articleCreate'])->name('api.article.create');
    Route::put('articles', [ArticlesController::class,'articleUpdate'])->name('api.article.update');
    Route::post('articles/editorUpload', [ArticlesController::class,'articleEditorUpload'])->name('api.article.editorUpload');
    Route::patch('articles/delete', [ArticlesController::class,'articleDelete'])->name('api.article.delete');

});

/**
 * Error
 */
Route::get('unauthorized', function() {
    return response()->json([
        'status' => 'error',
        'message' => 'API Key가 유효기간 종료되었거나, 존재하지 않습니다'
    ], 401);
})->name('api.jwt.unauthorized');