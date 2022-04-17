<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('login', 'App\Http\Controllers\Api\UserController@login');

Route::group(['middleware' => ['jwt.verify']], function() {
    Route::resource('invoices', 'App\Http\Controllers\Api\InvoiceController');

    //Products
    Route::get('products', 'App\Http\Controllers\Api\ProductController@index');
    Route::post('products', 'App\Http\Controllers\Api\ProductController@store');

    Route::post('log-out', 'App\Http\Controllers\Api\UserController@logOut');
    Route::post('token-validate', 'App\Http\Controllers\Api\UserController@validateToken');
});
