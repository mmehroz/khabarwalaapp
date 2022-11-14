<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\khabarwalaappController;

/*
|---------------------------------------------------------------------	-----
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
// Route::middleware('login.check')->group(function(){
// });
Route::any('/signup', [khabarwalaappController::class, 'signup']);
Route::any('/login', [khabarwalaappController::class, 'login']);
Route::get('/interest', [khabarwalaappController::class, 'interest']);
Route::any('/userinterest', [khabarwalaappController::class, 'userinterest']);
Route::get('/profile', [khabarwalaappController::class, 'profile']);
Route::any('/profilepicture', [khabarwalaappController::class, 'profilepicture']);
Route::get('/userlist', [khabarwalaappController::class, 'userlist']);
Route::any('/follow', [khabarwalaappController::class, 'follow']);
Route::get('/following', [khabarwalaappController::class, 'following']);
Route::get('/followers', [khabarwalaappController::class, 'followers']);
Route::any('/apppost', [khabarwalaappController::class, 'apppost']);
Route::get('/getpost', [khabarwalaappController::class, 'getpost']);
Route::any('/like', [khabarwalaappController::class, 'like']);
Route::any('/comment', [khabarwalaappController::class, 'comment']);
Route::get('/getpostcomment', [khabarwalaappController::class, 'getpostcomment']);
Route::any('/editprofile', [khabarwalaappController::class, 'editprofile']);
Route::get('/postreporttypes', [khabarwalaappController::class, 'postreporttypes']);
Route::any('/reportpost', [khabarwalaappController::class, 'reportpost']);
Route::any('/blockuser', [khabarwalaappController::class, 'blockuser']);
Route::get('/blockuserlist', [khabarwalaappController::class, 'blockuserlist']);
Route::get('/popularuserlist', [khabarwalaappController::class, 'popularuserlist']);
Route::any('/searchuser', [khabarwalaappController::class, 'searchuser']);
Route::get('/userpost', [khabarwalaappController::class, 'userpost']);
Route::get('/deletepost', [khabarwalaappController::class, 'deletepost']);